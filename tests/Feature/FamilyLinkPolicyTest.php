<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyLink;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyLinkPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_and_admin_can_request_a_link_member_cannot(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $owner->id]);
        FamilyUserRole::factory()->admin()->create(['family_id' => $family->id, 'user_id' => $admin->id]);
        FamilyUserRole::factory()->create(['family_id' => $family->id, 'user_id' => $member->id, 'role' => FamilyUserRole::ROLE_MEMBER]);
        $target = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id]);

        $this->assertTrue($owner->can('request', [FamilyLink::class, $target]));
        $this->assertTrue($admin->can('request', [FamilyLink::class, $target]));
        $this->assertFalse($member->can('request', [FamilyLink::class, $target]));
    }

    public function test_only_counter_family_manager_can_approve(): void
    {
        [$link, $ownerA, $familyA, $ownerB, $familyB] = $this->pendingLink();

        $this->assertFalse($ownerA->can('approve', $link));
        $this->assertTrue($ownerB->can('approve', $link));

        $outsider = User::factory()->create();
        FamilyUserRole::factory()->create(['family_id' => $familyB->id, 'user_id' => $outsider->id, 'role' => FamilyUserRole::ROLE_MEMBER]);
        $this->assertFalse($outsider->can('approve', $link));
    }

    public function test_either_family_manager_can_revoke_but_outsiders_cannot(): void
    {
        [$link, $ownerA, $familyA, $ownerB, $familyB] = $this->pendingLink();
        $outsider = User::factory()->create();

        $this->assertTrue($ownerA->can('revoke', $link));
        $this->assertTrue($ownerB->can('revoke', $link));
        $this->assertFalse($outsider->can('revoke', $link));
    }

    public function test_members_of_either_family_can_view_the_link(): void
    {
        [$link, $ownerA, $familyA, $ownerB, $familyB] = $this->pendingLink();
        $memberA = User::factory()->create();
        FamilyUserRole::factory()->create(['family_id' => $familyA->id, 'user_id' => $memberA->id, 'role' => FamilyUserRole::ROLE_MEMBER]);
        $outsider = User::factory()->create();

        $this->assertTrue($memberA->can('view', $link));
        $this->assertTrue($ownerB->can('view', $link));
        $this->assertFalse($outsider->can('view', $link));
    }

    /**
     * @return array{0: FamilyLink, 1: User, 2: Family, 3: User, 4: Family}
     */
    private function pendingLink(): array
    {
        $ownerA = User::factory()->create();
        $familyA = Family::factory()->create(['created_by' => $ownerA->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $familyA->id, 'user_id' => $ownerA->id]);

        $ownerB = User::factory()->create();
        $familyB = Family::factory()->create(['created_by' => $ownerB->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $familyB->id, 'user_id' => $ownerB->id]);

        $memberA = FamilyMember::factory()->create(['family_id' => $familyA->id, 'created_by' => $ownerA->id]);
        $memberB = FamilyMember::factory()->create(['family_id' => $familyB->id, 'created_by' => $ownerB->id]);

        $link = FamilyLink::factory()->create([
            'family_id' => $familyA->id,
            'linked_family_id' => $familyB->id,
            'member_id' => $memberA->id,
            'linked_member_id' => $memberB->id,
            'status' => FamilyLink::STATUS_PENDING,
        ]);

        return [$link, $ownerA, $familyA, $ownerB, $familyB];
    }
}
