<?php

namespace Tests\Unit;

use App\Models\ActivityLog;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\FamilyViewGrant;
use App\Models\User;
use App\Notifications\FamilyViewGrantNotification;
use App\Services\FamilyViewGrantService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FamilyViewGrantServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_creates_grant_for_existing_user_and_notifies_them(): void
    {
        Notification::fake();
        [$owner, $family] = $this->familyWithOwner(FamilyUserRole::ROLE_OWNER);
        $anchor = $this->member($family, $owner);
        $grantee = User::factory()->create();

        $result = app(FamilyViewGrantService::class)->create($owner, $family, $anchor, strtoupper($grantee->email));

        $grant = $result['grant'];
        $this->assertSame($grantee->id, $grant->grantee_user_id);
        $this->assertSame(strtolower($grantee->email), $grant->invited_email);
        $this->assertFalse($grant->can_view_living);
        $this->assertTrue($grant->isActive());
        $this->assertNotEmpty($result['plain_token']);
        $this->assertDatabaseHas('activity_logs', [
            'family_id' => $family->id,
            'activity_type' => ActivityLog::FAMILY_VIEW_GRANT_CREATED,
        ]);
        Notification::assertSentTo($grantee, FamilyViewGrantNotification::class);
    }

    public function test_owner_creates_token_grant_for_email_without_account(): void
    {
        Notification::fake();
        [$owner, $family] = $this->familyWithOwner(FamilyUserRole::ROLE_OWNER);
        $anchor = $this->member($family, $owner);

        $result = app(FamilyViewGrantService::class)->create($owner, $family, $anchor, 'future@example.test');

        $this->assertNull($result['grant']->grantee_user_id);
        $this->assertSame('future@example.test', $result['grant']->invited_email);
        Notification::assertSentOnDemand(
            FamilyViewGrantNotification::class,
            fn (FamilyViewGrantNotification $notification, array $channels, object $notifiable): bool => $notifiable->routes['mail'] === 'future@example.test'
                && $notification->plainToken !== null,
        );
    }

    public function test_admin_can_create_but_member_cannot(): void
    {
        Notification::fake();
        [$admin, $family] = $this->familyWithOwner(FamilyUserRole::ROLE_ADMIN);
        $anchor = $this->member($family, $admin);
        $grantee = User::factory()->create();

        app(FamilyViewGrantService::class)->create($admin, $family, $anchor, $grantee->email);
        $this->assertDatabaseCount('family_view_grants', 1);

        [$member, $memberFamily] = $this->familyWithOwner(FamilyUserRole::ROLE_MEMBER);
        $memberAnchor = $this->member($memberFamily, $member);

        $this->expectException(AuthorizationException::class);
        app(FamilyViewGrantService::class)->create($member, $memberFamily, $memberAnchor, $grantee->email);
    }

    public function test_anchor_must_belong_to_the_granted_family(): void
    {
        Notification::fake();
        [$owner, $family] = $this->familyWithOwner(FamilyUserRole::ROLE_OWNER);
        [$otherOwner, $otherFamily] = $this->familyWithOwner(FamilyUserRole::ROLE_OWNER);
        $foreignAnchor = $this->member($otherFamily, $otherOwner);

        $this->expectException(ValidationException::class);
        app(FamilyViewGrantService::class)->create($owner, $family, $foreignAnchor, 'guest@example.test');
    }

    public function test_authorize_only_accepts_active_grants_for_the_grantee(): void
    {
        [$owner, $family] = $this->familyWithOwner(FamilyUserRole::ROLE_OWNER);
        $anchor = $this->member($family, $owner);
        $grantee = User::factory()->create();
        $grant = FamilyViewGrant::factory()->create([
            'family_id' => $family->id,
            'anchor_member_id' => $anchor->id,
            'granted_by' => $owner->id,
            'grantee_user_id' => $grantee->id,
            'invited_email' => $grantee->email,
        ]);

        $authorized = app(FamilyViewGrantService::class)->authorize($grantee, $family);
        $this->assertSame($grant->id, $authorized->id);

        $grant->forceFill(['revoked_at' => now()])->save();
        $this->expectException(AuthorizationException::class);
        app(FamilyViewGrantService::class)->authorize($grantee, $family);
    }

    public function test_authorize_rejects_expired_and_other_user_grants(): void
    {
        [$owner, $family] = $this->familyWithOwner(FamilyUserRole::ROLE_OWNER);
        $anchor = $this->member($family, $owner);
        $grantee = User::factory()->create();
        FamilyViewGrant::factory()->expired()->create([
            'family_id' => $family->id,
            'anchor_member_id' => $anchor->id,
            'granted_by' => $owner->id,
            'grantee_user_id' => $grantee->id,
            'invited_email' => $grantee->email,
        ]);

        try {
            app(FamilyViewGrantService::class)->authorize($grantee, $family);
            $this->fail('Expired grant must be rejected.');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }

        FamilyViewGrant::query()->update(['expires_at' => now()->addDay()]);
        $this->expectException(AuthorizationException::class);
        app(FamilyViewGrantService::class)->authorize(User::factory()->create(), $family);
    }

    public function test_grantee_accepts_token_and_wrong_email_is_rejected(): void
    {
        Notification::fake();
        [$owner, $family] = $this->familyWithOwner(FamilyUserRole::ROLE_OWNER);
        $anchor = $this->member($family, $owner);
        $grantee = User::factory()->create(['email' => 'partner@example.test']);
        $result = app(FamilyViewGrantService::class)->create($owner, $family, $anchor, 'future@example.test');

        $this->expectException(ValidationException::class);
        app(FamilyViewGrantService::class)->accept($grantee, $result['plain_token']);
    }

    public function test_accept_binds_the_matching_user_and_logs_activity(): void
    {
        Notification::fake();
        [$owner, $family] = $this->familyWithOwner(FamilyUserRole::ROLE_OWNER);
        $anchor = $this->member($family, $owner);
        $grantee = User::factory()->create();
        $result = app(FamilyViewGrantService::class)->create($owner, $family, $anchor, $grantee->email);

        $accepted = app(FamilyViewGrantService::class)->accept($grantee, $result['plain_token']);

        $this->assertSame($grantee->id, $accepted->grantee_user_id);
        $this->assertDatabaseHas('activity_logs', [
            'family_id' => $family->id,
            'activity_type' => ActivityLog::FAMILY_VIEW_GRANT_ACCEPTED,
        ]);
    }

    public function test_revoke_is_idempotent_and_manager_only(): void
    {
        Notification::fake();
        [$owner, $family] = $this->familyWithOwner(FamilyUserRole::ROLE_OWNER);
        $anchor = $this->member($family, $owner);
        $grantee = User::factory()->create();
        $result = app(FamilyViewGrantService::class)->create($owner, $family, $anchor, $grantee->email);

        $revoked = app(FamilyViewGrantService::class)->revoke($owner, $result['grant']);
        $this->assertNotNull($revoked->revoked_at);
        $this->assertSame($revoked->id, app(FamilyViewGrantService::class)->revoke($owner, $revoked)->id);

        $this->expectException(AuthorizationException::class);
        app(FamilyViewGrantService::class)->revoke($grantee, $revoked);
    }

    public function test_mark_accessed_updates_timestamp_and_rate_limits_activity_logs(): void
    {
        [$owner, $family] = $this->familyWithOwner(FamilyUserRole::ROLE_OWNER);
        $anchor = $this->member($family, $owner);
        $grantee = User::factory()->create();
        $grant = FamilyViewGrant::factory()->create([
            'family_id' => $family->id,
            'anchor_member_id' => $anchor->id,
            'granted_by' => $owner->id,
            'grantee_user_id' => $grantee->id,
            'invited_email' => $grantee->email,
        ]);

        app(FamilyViewGrantService::class)->markAccessed($grantee, $grant);
        app(FamilyViewGrantService::class)->markAccessed($grantee, $grant->refresh());

        $this->assertNotNull($grant->refresh()->last_accessed_at);
        $this->assertSame(1, ActivityLog::query()
            ->where('activity_type', ActivityLog::FAMILY_VIEW_ACCESSED)
            ->count());
    }

    public function test_expire_due_counts_expired_grants(): void
    {
        [$owner, $family] = $this->familyWithOwner(FamilyUserRole::ROLE_OWNER);
        $anchor = $this->member($family, $owner);
        FamilyViewGrant::factory()->expired()->create([
            'family_id' => $family->id,
            'anchor_member_id' => $anchor->id,
            'granted_by' => $owner->id,
        ]);

        $this->assertSame(1, app(FamilyViewGrantService::class)->expireDue());
    }

    /** @return array{0: User, 1: Family} */
    private function familyWithOwner(string $role): array
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        FamilyUserRole::factory()->create([
            'family_id' => $family->id,
            'user_id' => $owner->id,
            'role' => $role,
        ]);

        return [$owner, $family];
    }

    private function member(Family $family, User $creator): FamilyMember
    {
        return FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $creator->id,
        ]);
    }
}
