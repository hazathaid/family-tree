<?php

namespace Tests\Unit;

use App\Models\ActivityLog;
use App\Models\Family;
use App\Models\FamilyLink;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\User;
use App\Services\FamilyLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FamilyLinkServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_creates_pending_link_and_logs_activity(): void
    {
        [$ownerA, $familyA] = $this->familyWithOwner();
        [$ownerB, $familyB] = $this->familyWithOwner();
        $memberA = $this->member($familyA, $ownerA);
        $memberB = $this->member($familyB, $ownerB);

        $link = app(FamilyLinkService::class)->request($ownerA, $memberA, $memberB);

        $this->assertSame(FamilyLink::STATUS_PENDING, $link->status);
        $this->assertSame($familyA->id, $link->family_id);
        $this->assertSame($familyB->id, $link->linked_family_id);
        $this->assertSame($memberA->id, $link->member_id);
        $this->assertSame($memberB->id, $link->linked_member_id);
        $this->assertSame($ownerA->id, $link->approved_by_family);
        $this->assertNull($link->approved_by_linked_family);

        $this->assertDatabaseHas('activity_logs', [
            'family_id' => $familyA->id,
            'activity_type' => ActivityLog::FAMILY_LINK_REQUESTED,
        ]);
    }

    public function test_request_rejects_members_of_the_same_family(): void
    {
        [$owner, $family] = $this->familyWithOwner();
        $memberA = $this->member($family, $owner);
        $memberB = $this->member($family, $owner);

        $this->expectException(ValidationException::class);

        app(FamilyLinkService::class)->request($owner, $memberA, $memberB);
    }

    public function test_request_rejects_when_a_member_already_has_open_link(): void
    {
        [$ownerA, $familyA] = $this->familyWithOwner();
        [$ownerB, $familyB] = $this->familyWithOwner();
        [$ownerC, $familyC] = $this->familyWithOwner();
        $memberA = $this->member($familyA, $ownerA);
        $memberB = $this->member($familyB, $ownerB);
        $memberC = $this->member($familyC, $ownerC);

        $service = app(FamilyLinkService::class);
        $service->request($ownerA, $memberA, $memberB);

        $this->expectException(ValidationException::class);

        $service->request($ownerA, $memberA, $memberC);
    }

    public function test_approve_by_counter_family_activates_link(): void
    {
        [$ownerA, $familyA] = $this->familyWithOwner();
        [$ownerB, $familyB] = $this->familyWithOwner();
        $memberA = $this->member($familyA, $ownerA);
        $memberB = $this->member($familyB, $ownerB);

        $service = app(FamilyLinkService::class);
        $link = $service->request($ownerA, $memberA, $memberB);

        $activated = $service->approve($ownerB, $link);

        $this->assertSame(FamilyLink::STATUS_ACTIVE, $activated->status);
        $this->assertSame($ownerB->id, $activated->approved_by_linked_family);
        $this->assertDatabaseHas('activity_logs', [
            'family_id' => $familyA->id,
            'activity_type' => ActivityLog::FAMILY_LINK_APPROVED,
        ]);
    }

    public function test_approve_rejects_non_pending_link(): void
    {
        [$ownerA, $familyA] = $this->familyWithOwner();
        [$ownerB, $familyB] = $this->familyWithOwner();
        $memberA = $this->member($familyA, $ownerA);
        $memberB = $this->member($familyB, $ownerB);

        $service = app(FamilyLinkService::class);
        $link = $service->request($ownerA, $memberA, $memberB);
        $service->approve($ownerB, $link);

        $this->expectException(ValidationException::class);

        $service->approve($ownerB, $link->refresh());
    }

    public function test_approve_expires_a_stale_link(): void
    {
        [$ownerA, $familyA] = $this->familyWithOwner();
        [$ownerB, $familyB] = $this->familyWithOwner();
        $memberA = $this->member($familyA, $ownerA);
        $memberB = $this->member($familyB, $ownerB);

        $service = app(FamilyLinkService::class);
        $link = $service->request($ownerA, $memberA, $memberB);
        $link->forceFill(['expires_at' => now()->subDay()])->save();

        try {
            $service->approve($ownerB, $link->refresh());
            $this->fail('Expected ValidationException for an expired link.');
        } catch (ValidationException) {
            $this->assertSame(FamilyLink::STATUS_EXPIRED, $link->refresh()->status);
        }
    }

    public function test_revoke_ends_link_and_is_idempotent(): void
    {
        [$ownerA, $familyA] = $this->familyWithOwner();
        [$ownerB, $familyB] = $this->familyWithOwner();
        $memberA = $this->member($familyA, $ownerA);
        $memberB = $this->member($familyB, $ownerB);

        $service = app(FamilyLinkService::class);
        $link = $service->request($ownerA, $memberA, $memberB);
        $service->approve($ownerB, $link);

        $revoked = $service->revoke($ownerA, $link->refresh());

        $this->assertSame(FamilyLink::STATUS_REVOKED, $revoked->status);
        $this->assertNotNull($revoked->revoked_at);
        $this->assertSame($revoked->id, $service->revoke($ownerA, $revoked)->id);
        $this->assertDatabaseHas('activity_logs', [
            'family_id' => $familyA->id,
            'activity_type' => ActivityLog::FAMILY_LINK_REVOKED,
        ]);
    }

    public function test_expire_due_marks_open_links_expired(): void
    {
        [$ownerA, $familyA] = $this->familyWithOwner();
        [$ownerB, $familyB] = $this->familyWithOwner();
        $memberA = $this->member($familyA, $ownerA);
        $memberB = $this->member($familyB, $ownerB);

        $link = FamilyLink::factory()->create([
            'family_id' => $familyA->id,
            'linked_family_id' => $familyB->id,
            'member_id' => $memberA->id,
            'linked_member_id' => $memberB->id,
            'status' => FamilyLink::STATUS_PENDING,
            'expires_at' => now()->subHour(),
        ]);

        $this->assertSame(1, app(FamilyLinkService::class)->expireDue());
        $this->assertSame(FamilyLink::STATUS_EXPIRED, $link->refresh()->status);
    }

    public function test_active_link_between_members_resolves_both_directions(): void
    {
        [$ownerA, $familyA] = $this->familyWithOwner();
        [$ownerB, $familyB] = $this->familyWithOwner();
        $memberA = $this->member($familyA, $ownerA);
        $memberB = $this->member($familyB, $ownerB);

        $service = app(FamilyLinkService::class);
        $link = $service->request($ownerA, $memberA, $memberB);
        $service->approve($ownerB, $link);

        $this->assertNotNull($service->activeLinkBetween($memberA, $memberB));
        $this->assertNotNull($service->activeLinkBetween($memberB, $memberA));
    }

    /** @return array{0: User, 1: Family} */
    private function familyWithOwner(): array
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $owner->id]);

        return [$owner, $family];
    }

    private function member(Family $family, User $creator): FamilyMember
    {
        return FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $creator->id]);
    }
}
