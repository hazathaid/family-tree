<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyLink;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\MemberRelationship;
use App\Models\User;
use App\Services\FamilyLinkService;
use App\Services\WebOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebFamilyLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_with_access_to_both_families_can_request_a_link(): void
    {
        [$ownerA, $familyA] = $this->family();
        [$ownerB, $familyB] = $this->family();
        FamilyUserRole::factory()->create(['family_id' => $familyB->id, 'user_id' => $ownerA->id, 'role' => FamilyUserRole::ROLE_MEMBER]);
        $memberA = $this->member($familyA, $ownerA);
        $memberB = $this->member($familyB, $ownerB);

        $this->active($ownerA, $familyA)->post(route('family-links.store'), [
            'member_uuid' => $memberA->uuid,
            'linked_member_uuid' => $memberB->uuid,
        ])->assertRedirect(route('family-links.index'));

        $this->assertDatabaseHas('family_links', [
            'family_id' => $familyA->id,
            'linked_family_id' => $familyB->id,
            'member_id' => $memberA->id,
            'linked_member_id' => $memberB->id,
            'status' => FamilyLink::STATUS_PENDING,
            'approved_by_family' => $ownerA->id,
        ]);
    }

    public function test_requester_must_be_owner_or_admin_of_the_local_family(): void
    {
        [$ownerA, $familyA] = $this->family();
        [$ownerB, $familyB] = $this->family();
        $memberUser = User::factory()->create();
        FamilyUserRole::factory()->create(['family_id' => $familyA->id, 'user_id' => $memberUser->id, 'role' => FamilyUserRole::ROLE_MEMBER]);
        FamilyUserRole::factory()->create(['family_id' => $familyB->id, 'user_id' => $memberUser->id, 'role' => FamilyUserRole::ROLE_MEMBER]);
        $memberA = $this->member($familyA, $ownerA);
        $memberB = $this->member($familyB, $ownerB);

        $this->active($memberUser, $familyA)->post(route('family-links.store'), [
            'member_uuid' => $memberA->uuid,
            'linked_member_uuid' => $memberB->uuid,
        ])->assertForbidden();

        $this->assertDatabaseCount('family_links', 0);
    }

    public function test_requester_cannot_target_a_family_they_cannot_access(): void
    {
        [$ownerA, $familyA] = $this->family();
        [$ownerB, $familyB] = $this->family();
        $memberA = $this->member($familyA, $ownerA);
        $memberB = $this->member($familyB, $ownerB);

        $this->active($ownerA, $familyA)->post(route('family-links.store'), [
            'member_uuid' => $memberA->uuid,
            'linked_member_uuid' => $memberB->uuid,
        ])->assertForbidden();
    }

    public function test_counter_family_manager_approves_a_pending_link(): void
    {
        [$ownerA, $familyA, $ownerB, $familyB, $link] = $this->pendingLink();

        $this->active($ownerB, $familyB)
            ->post(route('family-links.approve', $link))
            ->assertRedirect(route('family-links.index'));

        $link->refresh();
        $this->assertSame(FamilyLink::STATUS_ACTIVE, $link->status);
        $this->assertSame($ownerB->id, $link->approved_by_linked_family);
    }

    public function test_partner_tree_is_active_read_only_redacted_and_noindex(): void
    {
        [$ownerA, $familyA, $ownerB, $familyB, $link] = $this->pendingLink();
        app(FamilyLinkService::class)->approve($ownerB, $link);

        $livingFather = $this->member($familyB, $ownerB, ['gender' => 'male', 'is_alive' => true, 'birth_date' => '1950-01-01']);
        MemberRelationship::factory()->create([
            'family_id' => $familyB->id,
            'source_member_id' => $livingFather->id,
            'target_member_id' => $link->linked_member_id,
            'relationship_type' => MemberRelationship::TYPE_FATHER,
        ]);

        $response = $this->active($ownerA, $familyA)->get(route('family-links.partner-tree', $link->refresh()));

        $response->assertOk();
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $response->assertSee('Anggota Hidup');
        $response->assertSee($link->linkedMember->full_name);
    }

    public function test_pending_link_has_no_partner_tree(): void
    {
        [$ownerA, $familyA, $ownerB, $familyB, $link] = $this->pendingLink();

        $this->active($ownerA, $familyA)
            ->get(route('family-links.partner-tree', $link))
            ->assertNotFound();
    }

    public function test_either_manager_can_revoke_an_active_link(): void
    {
        [$ownerA, $familyA, $ownerB, $familyB, $link] = $this->pendingLink();
        app(FamilyLinkService::class)->approve($ownerB, $link);

        $this->active($ownerA, $familyA)
            ->post(route('family-links.revoke', $link->refresh()))
            ->assertRedirect(route('family-links.index'));

        $link->refresh();
        $this->assertSame(FamilyLink::STATUS_REVOKED, $link->status);
        $this->assertNotNull($link->revoked_at);
    }

    public function test_family_index_lists_links_for_the_active_family(): void
    {
        [$ownerA, $familyA, $ownerB, $familyB, $link] = $this->pendingLink();

        $this->active($ownerA, $familyA)
            ->get(route('family-links.index'))
            ->assertOk()
            ->assertSee($link->member->full_name)
            ->assertSee($link->linkedMember->full_name);
    }

    /** @return array{0: User, 1: Family} */
    private function family(): array
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $owner->id]);

        return [$owner, $family];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function member(Family $family, User $creator, array $attributes = []): FamilyMember
    {
        return FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $creator->id, ...$attributes]);
    }

    /**
     * @return array{0: User, 1: Family, 2: User, 3: Family, 4: FamilyLink}
     */
    private function pendingLink(): array
    {
        [$ownerA, $familyA] = $this->family();
        [$ownerB, $familyB] = $this->family();
        $memberA = $this->member($familyA, $ownerA);
        $memberB = $this->member($familyB, $ownerB);

        $link = FamilyLink::factory()->create([
            'family_id' => $familyA->id,
            'linked_family_id' => $familyB->id,
            'member_id' => $memberA->id,
            'linked_member_id' => $memberB->id,
            'status' => FamilyLink::STATUS_PENDING,
            'approved_by_family' => $ownerA->id,
        ]);

        return [$ownerA, $familyA, $ownerB, $familyB, $link];
    }

    private function active(User $user, Family $family): static
    {
        return $this->withSession([WebOnboardingService::ACTIVE_FAMILY_KEY => $family->uuid])->actingAs($user);
    }
}
