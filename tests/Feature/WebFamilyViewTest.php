<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\FamilyViewGrant;
use App\Models\MemberRelationship;
use App\Models\User;
use App\Notifications\FamilyViewGrantNotification;
use App\Services\FamilyViewGrantService;
use App\Services\WebOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WebFamilyViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_grantee_accepts_and_views_redacted_overview_and_tree(): void
    {
        Notification::fake();
        [$owner, $family] = $this->familyWithOwner();
        [$anchor, $livingFather, $deceasedMother] = $this->familyTree($family, $owner);
        $grantee = User::factory()->create();
        $result = app(FamilyViewGrantService::class)->create($owner, $family, $anchor, $grantee->email);

        $this->actingAs($grantee)
            ->get(route('family-view.accept', ['token' => $result['plain_token']]))
            ->assertRedirect(route('family-view.show', $family));

        $this->assertSame($grantee->id, $result['grant']->refresh()->grantee_user_id);

        $response = $this->actingAs($grantee)->get(route('family-view.show', $family));
        $response->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee($family->name)
            ->assertSee('Anggota Hidup')
            ->assertSee($deceasedMother->full_name)
            ->assertDontSee($livingFather->full_name);

        $this->actingAs($grantee)
            ->get(route('family-view.tree', $family))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Bagan Silsilah Baca-Saja')
            ->assertSee('Anggota Hidup')
            ->assertDontSee($livingFather->full_name);

        $this->assertSame(1, ActivityLog::query()
            ->where('activity_type', ActivityLog::FAMILY_VIEW_ACCESSED)
            ->count());
    }

    public function test_outsider_and_wrong_email_cannot_open_the_view(): void
    {
        Notification::fake();
        [$owner, $family] = $this->familyWithOwner();
        [$anchor] = $this->familyTree($family, $owner);
        $grantee = User::factory()->create();
        $other = User::factory()->create();
        $result = app(FamilyViewGrantService::class)->create($owner, $family, $anchor, $grantee->email);

        $this->actingAs($other)->get(route('family-view.show', $family))->assertForbidden();

        $this->actingAs($other)
            ->get(route('family-view.accept', ['token' => $result['plain_token']]))
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('token');

        $this->actingAs($grantee)
            ->get(route('family-view.accept', ['token' => 'invalid-token']))
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('token');
    }

    public function test_owner_can_grant_and_revoke_while_member_cannot(): void
    {
        Notification::fake();
        [$owner, $family] = $this->familyWithOwner();
        [$anchor] = $this->familyTree($family, $owner);
        $member = User::factory()->create();
        FamilyUserRole::factory()->create([
            'family_id' => $family->id,
            'user_id' => $member->id,
            'role' => FamilyUserRole::ROLE_MEMBER,
        ]);

        $this->active($owner, $family)->post(route('settings.family-view-grants.store'), [
            'anchor_member_uuid' => $anchor->uuid,
            'email' => 'future-family@example.test',
            'expires_in_days' => 7,
        ])->assertRedirect();

        $grant = FamilyViewGrant::query()->firstOrFail();
        $this->assertSame($family->id, $grant->family_id);
        $this->assertSame($anchor->id, $grant->anchor_member_id);
        $this->assertNull($grant->grantee_user_id);
        Notification::assertSentOnDemand(FamilyViewGrantNotification::class);

        $this->active($member, $family)->post(route('settings.family-view-grants.store'), [
            'anchor_member_uuid' => $anchor->uuid,
            'email' => 'blocked@example.test',
        ])->assertForbidden();

        $this->active($owner, $family)
            ->delete(route('settings.family-view-grants.revoke', $grant), ['confirm' => 1])
            ->assertRedirect();

        $this->assertNotNull($grant->refresh()->revoked_at);
    }

    public function test_revoked_and_expired_grants_cannot_open_the_view(): void
    {
        [$owner, $family] = $this->familyWithOwner();
        [$anchor] = $this->familyTree($family, $owner);
        $grantee = User::factory()->create();

        $revoked = FamilyViewGrant::factory()->revoked()->create([
            'family_id' => $family->id,
            'anchor_member_id' => $anchor->id,
            'granted_by' => $owner->id,
            'grantee_user_id' => $grantee->id,
            'invited_email' => $grantee->email,
        ]);
        $this->actingAs($grantee)->get(route('family-view.show', $family))->assertForbidden();

        $revoked->forceFill(['revoked_at' => null])->save();
        $revoked->forceFill(['expires_at' => now()->subDay()])->save();
        $this->actingAs($grantee)->get(route('family-view.show', $family))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_and_accept_requires_authentication(): void
    {
        [$owner, $family] = $this->familyWithOwner();
        [$anchor] = $this->familyTree($family, $owner);

        $this->get(route('family-view.show', $family))->assertRedirect(route('login'));
        $this->get(route('family-view.accept', ['token' => 'anything']))->assertRedirect(route('login'));
    }

    /** @return array{0: User, 1: Family} */
    private function familyWithOwner(): array
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $owner->id]);

        return [$owner, $family];
    }

    /**
     * @return array{0: FamilyMember, 1: FamilyMember, 2: FamilyMember}
     */
    private function familyTree(Family $family, User $owner): array
    {
        $anchor = FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $owner->id,
            'full_name' => 'Siti Aminah',
            'is_alive' => true,
        ]);
        $livingFather = FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $owner->id,
            'full_name' => 'Ayah Rahasia',
            'is_alive' => true,
            'gender' => 'male',
        ]);
        $deceasedMother = FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $owner->id,
            'full_name' => 'Ibu Kenangan',
            'is_alive' => false,
            'gender' => 'female',
            'death_date' => '2020-01-01',
        ]);
        MemberRelationship::factory()->create([
            'family_id' => $family->id,
            'source_member_id' => $livingFather->id,
            'target_member_id' => $anchor->id,
            'relationship_type' => MemberRelationship::TYPE_FATHER,
        ]);
        MemberRelationship::factory()->create([
            'family_id' => $family->id,
            'source_member_id' => $deceasedMother->id,
            'target_member_id' => $anchor->id,
            'relationship_type' => MemberRelationship::TYPE_MOTHER,
        ]);

        return [$anchor, $livingFather, $deceasedMother];
    }

    private function active(User $user, Family $family): static
    {
        return $this->withSession([WebOnboardingService::ACTIVE_FAMILY_KEY => $family->uuid])->actingAs($user);
    }
}
