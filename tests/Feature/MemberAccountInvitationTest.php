<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\User;
use App\Notifications\MemberAccountInvitationNotification;
use App\Services\MemberAccountInvitationService;
use App\Services\WebOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MemberAccountInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_invites_member_and_recipient_creates_linked_account(): void
    {
        Notification::fake();
        [$owner, $family, $member] = $this->fixture(FamilyUserRole::ROLE_OWNER);

        $result = app(MemberAccountInvitationService::class)->invite($owner, $member, 'devi@example.test');

        Notification::assertSentOnDemand(MemberAccountInvitationNotification::class);

        $this->get(route('member-account-invitations.show', $result['plain_token']))
            ->assertOk()
            ->assertSee('Devi Firdaus Fauzi');

        $this->post(route('member-account-invitations.accept', $result['plain_token']), [
            'name' => 'Devi Firdaus Fauzi',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('dashboard'));

        $user = User::query()->where('email', 'devi@example.test')->firstOrFail();
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame($user->id, $member->refresh()->user_id);
        $this->assertDatabaseHas('family_user_roles', [
            'family_id' => $family->id,
            'user_id' => $user->id,
            'role' => FamilyUserRole::ROLE_MEMBER,
        ]);
        $this->assertDatabaseHas('member_account_invitations', ['id' => $result['invitation']->id]);
        $this->get(route('member-account-invitations.show', $result['plain_token']))->assertRedirect(route('dashboard'));
    }

    public function test_linked_member_can_update_only_own_profile_and_cannot_delete_it(): void
    {
        [$owner, $family, $member] = $this->fixture(FamilyUserRole::ROLE_OWNER);
        $linked = User::factory()->create();
        $member->update(['user_id' => $linked->id]);
        FamilyUserRole::factory()->create(['family_id' => $family->id, 'user_id' => $linked->id, 'role' => FamilyUserRole::ROLE_MEMBER]);
        $other = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id]);
        Sanctum::actingAs($linked);

        $payload = ['full_name' => 'Devi Baru', 'is_alive' => true];
        $this->putJson('/api/v1/family-members/'.$member->uuid, $payload)->assertOk();
        $this->putJson('/api/v1/family-members/'.$other->uuid, $payload)->assertForbidden();
        $this->deleteJson('/api/v1/family-members/'.$member->uuid)->assertForbidden();
    }

    public function test_regular_member_cannot_send_account_invitation(): void
    {
        [$user, $family, $member] = $this->fixture(FamilyUserRole::ROLE_MEMBER);

        $this->withSession([WebOnboardingService::ACTIVE_FAMILY_KEY => $family->uuid])
            ->actingAs($user)
            ->post(route('members.account-invitations.store', $member), ['email' => 'baru@example.test'])
            ->assertForbidden();
    }

    public function test_api_invitation_creates_account_and_returns_sanctum_token(): void
    {
        Notification::fake();
        [$owner, $family, $member] = $this->fixture(FamilyUserRole::ROLE_OWNER);
        Sanctum::actingAs($owner);

        $this->postJson('/api/v1/family-members/'.$member->uuid.'/account-invitations', [
            'email' => 'api-member@example.test',
        ])->assertCreated()
            ->assertJsonPath('data.email', 'api-member@example.test')
            ->assertJsonMissingPath('data.token');

        $plainToken = null;
        Notification::assertSentOnDemand(
            MemberAccountInvitationNotification::class,
            function (MemberAccountInvitationNotification $notification) use (&$plainToken): bool {
                $plainToken = $notification->plainToken;

                return true;
            },
        );

        $this->assertIsString($plainToken);
        auth()->forgetGuards();

        $this->postJson('/api/v1/member-account-invitations/'.$plainToken, [
            'name' => 'API Member',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'device_name' => 'Investor Demo',
        ])->assertCreated()
            ->assertJsonPath('data.user.email', 'api-member@example.test')
            ->assertJsonStructure(['data' => ['token']]);

        $this->assertDatabaseHas('family_user_roles', [
            'family_id' => $family->id,
            'role' => FamilyUserRole::ROLE_MEMBER,
        ]);
    }

    private function fixture(string $role): array
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        FamilyUserRole::factory()->create(['family_id' => $family->id, 'user_id' => $user->id, 'role' => $role]);
        $member = FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $user->id,
            'full_name' => 'Devi Firdaus Fauzi',
        ]);

        return [$user, $family, $member];
    }
}
