<?php

namespace Tests\Unit;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\MemberRelationship;
use App\Models\User;
use App\Services\TreeRelativeMemberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TreeRelativeMemberServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_adding_a_child_requires_the_member_gender(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $member = FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $user->id,
            'gender' => null,
        ]);

        $this->expectException(ValidationException::class);

        app(TreeRelativeMemberService::class)->create($user, $member, [
            'relation' => 'child',
            'full_name' => 'Anak Baru',
        ]);
    }

    public function test_parent_relation_uses_the_relative_gender(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $member = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id]);

        $relative = app(TreeRelativeMemberService::class)->create($user, $member, [
            'relation' => 'parent',
            'full_name' => 'Ayah Baru',
            'gender' => 'male',
        ]);

        $this->assertDatabaseHas('member_relationships', [
            'family_id' => $family->id,
            'source_member_id' => $relative->id,
            'target_member_id' => $member->id,
            'relationship_type' => 'father',
        ]);
    }

    public function test_adding_a_child_with_co_parent_links_both_biological_parents(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $husband = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id, 'gender' => 'male']);
        $wife = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id, 'gender' => 'female']);
        $this->marry($family, $husband, $wife);

        $child = app(TreeRelativeMemberService::class)->create($user, $husband, [
            'relation' => 'child',
            'full_name' => 'Anak Bersama',
            'gender' => 'female',
            'co_parent_uuid' => $wife->uuid,
        ]);

        $this->assertDatabaseHas('member_relationships', [
            'family_id' => $family->id,
            'source_member_id' => $husband->id,
            'target_member_id' => $child->id,
            'relationship_type' => 'father',
        ]);
        $this->assertDatabaseHas('member_relationships', [
            'family_id' => $family->id,
            'source_member_id' => $wife->id,
            'target_member_id' => $child->id,
            'relationship_type' => 'mother',
        ]);
    }

    public function test_co_parent_must_belong_to_the_same_family(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $otherFamily = Family::factory()->create(['created_by' => $user->id]);
        $husband = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id, 'gender' => 'male']);
        $outsider = FamilyMember::factory()->create(['family_id' => $otherFamily->id, 'created_by' => $user->id, 'gender' => 'female']);

        $this->expectException(ValidationException::class);

        app(TreeRelativeMemberService::class)->create($user, $husband, [
            'relation' => 'child',
            'full_name' => 'Anak',
            'gender' => 'female',
            'co_parent_uuid' => $outsider->uuid,
        ]);
    }

    public function test_co_parent_must_have_a_different_gender(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $member = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id, 'gender' => 'male']);
        $otherMale = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id, 'gender' => 'male']);
        $this->marry($family, $member, $otherMale, MemberRelationship::TYPE_HUSBAND);

        $this->expectException(ValidationException::class);

        app(TreeRelativeMemberService::class)->create($user, $member, [
            'relation' => 'child',
            'full_name' => 'Anak',
            'gender' => 'female',
            'co_parent_uuid' => $otherMale->uuid,
        ]);
    }

    public function test_co_parent_must_be_linked_as_a_spouse(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $husband = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id, 'gender' => 'male']);
        $wife = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id, 'gender' => 'female']);

        $this->expectException(ValidationException::class);

        app(TreeRelativeMemberService::class)->create($user, $husband, [
            'relation' => 'child',
            'full_name' => 'Anak',
            'gender' => 'female',
            'co_parent_uuid' => $wife->uuid,
        ]);
    }

    public function test_co_parent_is_rejected_for_non_child_relations(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $member = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id, 'gender' => 'male']);
        $spouse = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id, 'gender' => 'female']);

        $this->expectException(ValidationException::class);

        app(TreeRelativeMemberService::class)->create($user, $member, [
            'relation' => 'spouse',
            'full_name' => 'Pasangan',
            'gender' => 'female',
            'co_parent_uuid' => $spouse->uuid,
        ]);
    }

    private function marry(Family $family, FamilyMember $first, FamilyMember $second, string $firstType = MemberRelationship::TYPE_HUSBAND): void
    {
        MemberRelationship::query()->create([
            'family_id' => $family->id,
            'source_member_id' => $first->id,
            'target_member_id' => $second->id,
            'relationship_type' => $firstType,
        ]);
        MemberRelationship::query()->create([
            'family_id' => $family->id,
            'source_member_id' => $second->id,
            'target_member_id' => $first->id,
            'relationship_type' => $firstType === MemberRelationship::TYPE_HUSBAND
                ? MemberRelationship::TYPE_WIFE
                : MemberRelationship::TYPE_HUSBAND,
        ]);
    }
}
