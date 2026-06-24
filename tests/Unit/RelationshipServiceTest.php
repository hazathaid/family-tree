<?php

namespace Tests\Unit;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\User;
use App\Services\RelationshipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RelationshipServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_relationship_rejects_second_biological_father(): void
    {
        [$family, $firstFather, $secondFather, $child] = $this->familyWithMembers(3);

        app(RelationshipService::class)->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $firstFather->uuid,
            'target_member_uuid' => $child->uuid,
            'relationship_type' => 'father',
        ]);

        $this->expectException(ValidationException::class);

        app(RelationshipService::class)->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $secondFather->uuid,
            'target_member_uuid' => $child->uuid,
            'relationship_type' => 'father',
        ]);
    }

    public function test_create_relationship_rejects_second_biological_mother(): void
    {
        [$family, $firstMother, $secondMother, $child] = $this->familyWithMembers(3);

        app(RelationshipService::class)->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $firstMother->uuid,
            'target_member_uuid' => $child->uuid,
            'relationship_type' => 'mother',
        ]);

        $this->expectException(ValidationException::class);

        app(RelationshipService::class)->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $secondMother->uuid,
            'target_member_uuid' => $child->uuid,
            'relationship_type' => 'mother',
        ]);
    }

    public function test_create_relationship_rejects_circular_parent_graph(): void
    {
        [$family, $grandparent, $parent, $child] = $this->familyWithMembers(3);

        app(RelationshipService::class)->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $grandparent->uuid,
            'target_member_uuid' => $parent->uuid,
            'relationship_type' => 'father',
        ]);
        app(RelationshipService::class)->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $parent->uuid,
            'target_member_uuid' => $child->uuid,
            'relationship_type' => 'father',
        ]);

        $this->expectException(ValidationException::class);

        app(RelationshipService::class)->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $child->uuid,
            'target_member_uuid' => $grandparent->uuid,
            'relationship_type' => 'father',
        ]);
    }

    public function test_update_relationship_preserves_graph_integrity(): void
    {
        [$family, $grandparent, $parent, $child] = $this->familyWithMembers(3);
        app(RelationshipService::class)->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $grandparent->uuid,
            'target_member_uuid' => $parent->uuid,
            'relationship_type' => 'father',
        ]);
        app(RelationshipService::class)->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $parent->uuid,
            'target_member_uuid' => $child->uuid,
            'relationship_type' => 'father',
        ]);
        $relationship = app(RelationshipService::class)->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $child->uuid,
            'target_member_uuid' => $grandparent->uuid,
            'relationship_type' => 'husband',
        ]);

        $this->expectException(ValidationException::class);

        app(RelationshipService::class)->update($relationship, [
            'relationship_type' => 'father',
        ]);
    }

    public function test_husband_relationship_creates_and_deletes_wife_inverse(): void
    {
        [$family, $husband, $wife] = $this->familyWithMembers(2);

        $relationship = app(RelationshipService::class)->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $husband->uuid,
            'target_member_uuid' => $wife->uuid,
            'relationship_type' => 'husband',
        ]);

        $this->assertDatabaseHas('member_relationships', [
            'family_id' => $family->id,
            'source_member_id' => $wife->id,
            'target_member_id' => $husband->id,
            'relationship_type' => 'wife',
            'deleted_at' => null,
        ]);

        app(RelationshipService::class)->delete($relationship);

        $this->assertSoftDeleted('member_relationships', [
            'family_id' => $family->id,
            'source_member_id' => $wife->id,
            'target_member_id' => $husband->id,
            'relationship_type' => 'wife',
        ]);
    }

    public function test_relationship_rejects_member_from_another_family(): void
    {
        [$family, $source] = $this->familyWithMembers(1);
        [, $target] = $this->familyWithMembers(1);

        $this->expectException(ValidationException::class);

        app(RelationshipService::class)->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $source->uuid,
            'target_member_uuid' => $target->uuid,
            'relationship_type' => 'father',
        ]);
    }

    private function familyWithMembers(int $additionalMembers): array
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $members = FamilyMember::factory()
            ->count($additionalMembers)
            ->create([
                'family_id' => $family->id,
                'created_by' => $user->id,
            ])
            ->all();

        return [$family, ...$members];
    }
}
