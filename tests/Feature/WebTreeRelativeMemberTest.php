<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\MemberRelationship;
use App\Models\User;
use App\Services\WebOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebTreeRelativeMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_add_parent_spouse_and_child_from_tree_and_creator_is_recorded(): void
    {
        [$owner, $family] = $this->userWithFamily(FamilyUserRole::ROLE_OWNER);
        $member = FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $owner->id,
            'gender' => 'male',
        ]);

        foreach ([
            ['parent', 'Ibu Baru', 'female'],
            ['spouse', 'Pasangan Baru', 'female'],
            ['child', 'Anak Baru', 'male'],
        ] as [$relation, $name, $gender]) {
            $this->active($owner, $family)->post(route('tree.relatives.store', $member), [
                'relation' => $relation,
                'full_name' => $name,
                'gender' => $gender,
                'is_alive' => '1',
            ])->assertRedirect(route('tree.index', ['root' => $member->uuid]));
        }

        $this->assertDatabaseHas('family_members', ['family_id' => $family->id, 'full_name' => 'Ibu Baru', 'created_by' => $owner->id]);
        $this->assertDatabaseHas('family_members', ['family_id' => $family->id, 'full_name' => 'Pasangan Baru', 'created_by' => $owner->id]);
        $this->assertDatabaseHas('family_members', ['family_id' => $family->id, 'full_name' => 'Anak Baru', 'created_by' => $owner->id]);
        $this->assertDatabaseCount('member_relationships', 4); // spouse has an inverse edge
        $this->assertDatabaseHas('activity_logs', ['family_id' => $family->id, 'user_id' => $owner->id, 'activity_type' => ActivityLog::TREE_RELATIVE_CREATED]);
    }

    public function test_linked_member_can_add_relatives_only_from_own_member_node(): void
    {
        [$user, $family] = $this->userWithFamily(FamilyUserRole::ROLE_MEMBER);
        $ownMember = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id, 'user_id' => $user->id, 'gender' => 'female']);
        $otherMember = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id]);

        $this->active($user, $family)->post(route('tree.relatives.store', $ownMember), [
            'relation' => 'child', 'full_name' => 'Anak Saya', 'gender' => 'male', 'is_alive' => '1',
        ])->assertRedirect();

        $this->active($user, $family)->post(route('tree.relatives.store', $otherMember), [
            'relation' => 'child', 'full_name' => 'Tidak Boleh', 'gender' => 'male', 'is_alive' => '1',
        ])->assertForbidden();
    }

    public function test_owner_can_add_child_with_a_specific_co_parent(): void
    {
        [$owner, $family] = $this->userWithFamily(FamilyUserRole::ROLE_OWNER);
        $husband = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'male']);
        $wife = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'female']);
        $this->marry($family, $husband, $wife);

        $this->active($owner, $family)->post(route('tree.relatives.store', $husband), [
            'relation' => 'child',
            'co_parent_uuid' => $wife->uuid,
            'full_name' => 'Anak Bersama',
            'gender' => 'female',
            'is_alive' => '1',
        ])->assertRedirect(route('tree.index', ['root' => $husband->uuid]));

        $child = FamilyMember::query()->where('family_id', $family->id)->where('full_name', 'Anak Bersama')->firstOrFail();

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

    public function test_tree_modal_can_fetch_spouses_for_co_parent_selection(): void
    {
        [$owner, $family] = $this->userWithFamily(FamilyUserRole::ROLE_OWNER);
        $husband = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'male']);
        $firstWife = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'female']);
        $secondWife = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'female']);
        $this->marry($family, $husband, $firstWife);
        $this->marry($family, $husband, $secondWife);

        $this->active($owner, $family)->getJson(route('tree.members.spouses', $husband))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['spouse_uuid' => $firstWife->uuid, 'label' => 'Istri'])
            ->assertJsonFragment(['spouse_uuid' => $secondWife->uuid, 'label' => 'Istri']);
    }

    public function test_owner_can_record_the_end_of_a_marriage(): void
    {
        [$owner, $family] = $this->userWithFamily(FamilyUserRole::ROLE_OWNER);
        $husband = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'male']);
        $wife = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'female']);
        $this->marry($family, $husband, $wife);
        $relationship = MemberRelationship::query()
            ->where('family_id', $family->id)
            ->where('source_member_id', $husband->id)
            ->where('target_member_id', $wife->id)
            ->where('relationship_type', MemberRelationship::TYPE_HUSBAND)
            ->firstOrFail();

        $this->active($owner, $family)->post(route('members.spouses.end', [$husband, $relationship->uuid]), [
            'end_date' => '2024-06-01',
            'end_reason' => 'divorced',
        ])->assertRedirect(route('members.show', $husband));

        $this->assertDatabaseHas('member_relationships', [
            'family_id' => $family->id,
            'source_member_id' => $husband->id,
            'target_member_id' => $wife->id,
            'relationship_type' => MemberRelationship::TYPE_HUSBAND,
            'end_reason' => 'divorced',
        ]);
        $this->assertDatabaseHas('member_relationships', [
            'family_id' => $family->id,
            'source_member_id' => $wife->id,
            'target_member_id' => $husband->id,
            'relationship_type' => MemberRelationship::TYPE_WIFE,
            'end_reason' => 'divorced',
        ]);
    }

    public function test_owner_can_correct_and_reopen_a_marriage_end(): void
    {
        [$owner, $family] = $this->userWithFamily(FamilyUserRole::ROLE_OWNER);
        $husband = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'male']);
        $wife = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'female']);
        $this->marry($family, $husband, $wife);
        $relationship = MemberRelationship::query()
            ->where('family_id', $family->id)
            ->where('source_member_id', $husband->id)
            ->where('target_member_id', $wife->id)
            ->where('relationship_type', MemberRelationship::TYPE_HUSBAND)
            ->firstOrFail();

        $this->active($owner, $family)->post(route('members.spouses.end', [$husband, $relationship->uuid]), [
            'end_date' => '2024-06-01',
            'end_reason' => 'divorced',
        ])->assertRedirect(route('members.show', $husband));

        $this->active($owner, $family)->get(route('members.show', $husband))
            ->assertOk()
            ->assertSee('Mantan Istri')
            ->assertSee('Koreksi')
            ->assertSee('Batalkan perceraian');

        $this->active($owner, $family)->patch(route('members.spouses.correct', [$husband, $relationship->uuid]), [
            'end_date' => '2023-12-31',
            'end_reason' => 'widowed',
        ])->assertRedirect(route('members.show', $husband));

        $relationship->refresh();
        $this->assertSame('2023-12-31', $relationship->end_date?->toDateString());
        $this->assertSame('widowed', $relationship->end_reason);
        $this->assertDatabaseHas('member_relationships', [
            'source_member_id' => $wife->id,
            'target_member_id' => $husband->id,
            'end_reason' => 'widowed',
        ]);

        $this->active($owner, $family)->post(route('members.spouses.reopen', [$husband, $relationship->uuid]))
            ->assertRedirect(route('members.show', $husband));

        $relationship->refresh();
        $this->assertNull($relationship->end_date);
        $this->assertNull($relationship->end_reason);
        $this->assertDatabaseHas('member_relationships', [
            'source_member_id' => $wife->id,
            'target_member_id' => $husband->id,
            'end_date' => null,
            'end_reason' => null,
        ]);
    }

    public function test_member_cannot_correct_or_reopen_a_marriage_end(): void
    {
        [$owner, $family] = $this->userWithFamily(FamilyUserRole::ROLE_OWNER);
        [$memberUser] = $this->userWithFamilyInFamily(FamilyUserRole::ROLE_MEMBER, $family);
        $husband = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'male']);
        $wife = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'female']);
        $this->marry($family, $husband, $wife);
        $relationship = MemberRelationship::query()
            ->where('family_id', $family->id)
            ->where('source_member_id', $husband->id)
            ->where('target_member_id', $wife->id)
            ->where('relationship_type', MemberRelationship::TYPE_HUSBAND)
            ->firstOrFail();

        $this->active($owner, $family)->post(route('members.spouses.end', [$husband, $relationship->uuid]), [
            'end_date' => '2024-06-01',
            'end_reason' => 'divorced',
        ])->assertRedirect(route('members.show', $husband));

        $this->active($memberUser, $family)->patch(route('members.spouses.correct', [$husband, $relationship->uuid]), [
            'end_date' => '2023-12-31',
            'end_reason' => 'widowed',
        ])->assertForbidden();

        $this->active($memberUser, $family)->post(route('members.spouses.reopen', [$husband, $relationship->uuid]))
            ->assertForbidden();
    }

    public function test_marriage_end_correction_rejects_a_relationship_from_another_member(): void
    {
        [$owner, $family] = $this->userWithFamily(FamilyUserRole::ROLE_OWNER);
        $husband = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'male']);
        $wife = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'female']);
        $outsider = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id]);
        $this->marry($family, $husband, $wife);
        $relationship = MemberRelationship::query()
            ->where('family_id', $family->id)
            ->where('source_member_id', $husband->id)
            ->where('target_member_id', $wife->id)
            ->where('relationship_type', MemberRelationship::TYPE_HUSBAND)
            ->firstOrFail();

        $this->active($owner, $family)->post(route('members.spouses.end', [$husband, $relationship->uuid]), [
            'end_date' => '2024-06-01',
            'end_reason' => 'divorced',
        ]);

        $this->active($owner, $family)->patch(route('members.spouses.correct', [$outsider, $relationship->uuid]), [
            'end_date' => '2023-12-31',
            'end_reason' => 'widowed',
        ])->assertNotFound();
    }

    public function test_marriage_end_correction_rejects_a_date_before_the_marriage_start(): void
    {
        [$owner, $family] = $this->userWithFamily(FamilyUserRole::ROLE_OWNER);
        $husband = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'male']);
        $wife = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'female']);
        $this->marry($family, $husband, $wife);
        $relationship = MemberRelationship::query()
            ->where('family_id', $family->id)
            ->where('source_member_id', $husband->id)
            ->where('target_member_id', $wife->id)
            ->where('relationship_type', MemberRelationship::TYPE_HUSBAND)
            ->firstOrFail();
        $relationship->update([
            'start_date' => '2010-01-01',
            'end_date' => '2024-06-01',
            'end_reason' => 'divorced',
        ]);

        $this->active($owner, $family)->patch(route('members.spouses.correct', [$husband, $relationship->uuid]), [
            'end_date' => '2009-12-31',
            'end_reason' => 'divorced',
        ])->assertSessionHasErrors('end_date');
    }

    private function marry(Family $family, FamilyMember $husband, FamilyMember $wife): void
    {
        MemberRelationship::query()->create([
            'family_id' => $family->id,
            'source_member_id' => $husband->id,
            'target_member_id' => $wife->id,
            'relationship_type' => MemberRelationship::TYPE_HUSBAND,
        ]);
        MemberRelationship::query()->create([
            'family_id' => $family->id,
            'source_member_id' => $wife->id,
            'target_member_id' => $husband->id,
            'relationship_type' => MemberRelationship::TYPE_WIFE,
        ]);
    }

    private function userWithFamily(string $role): array
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        FamilyUserRole::factory()->create(['family_id' => $family->id, 'user_id' => $user->id, 'role' => $role]);

        return [$user, $family];
    }

    private function userWithFamilyInFamily(string $role, Family $family): array
    {
        $user = User::factory()->create();
        FamilyUserRole::factory()->create(['family_id' => $family->id, 'user_id' => $user->id, 'role' => $role]);

        return [$user];
    }

    private function active(User $user, Family $family): static
    {
        return $this->withSession([WebOnboardingService::ACTIVE_FAMILY_KEY => $family->uuid])->actingAs($user);
    }
}
