<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyLink;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\MemberRelationship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FamilyLinkApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_request_and_list_cross_family_links(): void
    {
        [$ownerA, $familyA] = $this->family(FamilyUserRole::ROLE_OWNER);
        [$ownerB, $familyB] = $this->family(FamilyUserRole::ROLE_OWNER);
        FamilyUserRole::factory()->create(['family_id' => $familyB->id, 'user_id' => $ownerA->id, 'role' => FamilyUserRole::ROLE_MEMBER]);
        $memberA = $this->member($familyA, $ownerA);
        $memberB = $this->member($familyB, $ownerB);

        Sanctum::actingAs($ownerA);

        $uuid = $this->postJson('/api/v1/families/'.$familyA->uuid.'/family-links', [
            'member_uuid' => $memberA->uuid,
            'linked_member_uuid' => $memberB->uuid,
        ])->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', FamilyLink::STATUS_PENDING)
            ->assertJsonPath('data.member.uuid', $memberA->uuid)
            ->assertJsonPath('data.linked_member.uuid', $memberB->uuid)
            ->json('data.uuid');

        $this->assertDatabaseHas('family_links', [
            'uuid' => $uuid,
            'family_id' => $familyA->id,
            'linked_family_id' => $familyB->id,
            'member_id' => $memberA->id,
            'linked_member_id' => $memberB->id,
            'status' => FamilyLink::STATUS_PENDING,
            'approved_by_family' => $ownerA->id,
        ]);

        $this->getJson('/api/v1/families/'.$familyA->uuid.'/family-links')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uuid', $uuid)
            ->assertJsonPath('data.0.family.uuid', $familyA->uuid)
            ->assertJsonPath('data.0.linked_family.uuid', $familyB->uuid)
            ->assertJsonPath('data.0.can_approve', false)
            ->assertJsonPath('data.0.can_revoke', true);

        $this->getJson('/api/v1/family-links/'.$uuid)
            ->assertOk()
            ->assertJsonPath('data.uuid', $uuid);
    }

    public function test_regular_member_cannot_request_a_link(): void
    {
        [$ownerA, $familyA] = $this->family(FamilyUserRole::ROLE_OWNER);
        [$ownerB, $familyB] = $this->family(FamilyUserRole::ROLE_OWNER);
        $memberUser = User::factory()->create();
        FamilyUserRole::factory()->create(['family_id' => $familyA->id, 'user_id' => $memberUser->id, 'role' => FamilyUserRole::ROLE_MEMBER]);
        FamilyUserRole::factory()->create(['family_id' => $familyB->id, 'user_id' => $memberUser->id, 'role' => FamilyUserRole::ROLE_MEMBER]);
        $memberA = $this->member($familyA, $ownerA);
        $memberB = $this->member($familyB, $ownerB);

        Sanctum::actingAs($memberUser);

        $this->postJson('/api/v1/families/'.$familyA->uuid.'/family-links', [
            'member_uuid' => $memberA->uuid,
            'linked_member_uuid' => $memberB->uuid,
        ])->assertForbidden();

        $this->assertDatabaseCount('family_links', 0);
    }

    public function test_requester_cannot_target_a_family_they_cannot_access(): void
    {
        [$ownerA, $familyA] = $this->family(FamilyUserRole::ROLE_OWNER);
        [, $familyB] = $this->family(FamilyUserRole::ROLE_OWNER);
        $memberA = $this->member($familyA, $ownerA);
        $memberB = $this->member($familyB, User::factory()->create());

        Sanctum::actingAs($ownerA);

        $this->postJson('/api/v1/families/'.$familyA->uuid.'/family-links', [
            'member_uuid' => $memberA->uuid,
            'linked_member_uuid' => $memberB->uuid,
        ])->assertForbidden();

        $this->assertDatabaseCount('family_links', 0);
    }

    public function test_local_member_must_belong_to_the_route_family(): void
    {
        [$ownerA, $familyA] = $this->family(FamilyUserRole::ROLE_OWNER);
        [$ownerB, $familyB] = $this->family(FamilyUserRole::ROLE_OWNER);
        FamilyUserRole::factory()->create(['family_id' => $familyB->id, 'user_id' => $ownerA->id, 'role' => FamilyUserRole::ROLE_MEMBER]);
        $foreignMember = $this->member($familyB, $ownerB);
        $memberB = $this->member($familyB, $ownerB);

        Sanctum::actingAs($ownerA);

        $this->postJson('/api/v1/families/'.$familyA->uuid.'/family-links', [
            'member_uuid' => $foreignMember->uuid,
            'linked_member_uuid' => $memberB->uuid,
        ])->assertNotFound();
    }

    public function test_counter_family_owner_approves_and_local_owner_cannot(): void
    {
        [$ownerA, $familyA, $ownerB, $familyB, $link] = $this->pendingLink();

        Sanctum::actingAs($ownerA);
        $this->postJson('/api/v1/family-links/'.$link->uuid.'/approve')->assertForbidden();

        Sanctum::actingAs($ownerB);
        $this->postJson('/api/v1/family-links/'.$link->uuid.'/approve')
            ->assertOk()
            ->assertJsonPath('data.status', FamilyLink::STATUS_ACTIVE)
            ->assertJsonPath('data.can_approve', false);

        $this->assertSame($ownerB->id, $link->refresh()->approved_by_linked_family);
    }

    public function test_either_manager_can_revoke_an_active_link(): void
    {
        [$ownerA, $familyA, $ownerB, $familyB, $link] = $this->pendingLink();

        Sanctum::actingAs($ownerB);
        $this->postJson('/api/v1/family-links/'.$link->uuid.'/approve')->assertOk();

        Sanctum::actingAs($ownerA);
        $this->postJson('/api/v1/family-links/'.$link->uuid.'/revoke')
            ->assertOk()
            ->assertJsonPath('data.status', FamilyLink::STATUS_REVOKED);

        $this->assertNotNull($link->refresh()->revoked_at);
        $this->assertSame(FamilyLink::STATUS_REVOKED, $link->status);
    }

    public function test_candidates_are_limited_to_other_families_the_user_can_access(): void
    {
        [$ownerA, $familyA] = $this->family(FamilyUserRole::ROLE_OWNER);
        [$ownerB, $familyB] = $this->family(FamilyUserRole::ROLE_OWNER);
        [$ownerC, $familyC] = $this->family(FamilyUserRole::ROLE_OWNER);
        FamilyUserRole::factory()->create(['family_id' => $familyB->id, 'user_id' => $ownerA->id, 'role' => FamilyUserRole::ROLE_MEMBER]);
        $this->member($familyA, $ownerA, ['full_name' => 'Siti Aminah']);
        $candidate = $this->member($familyB, $ownerB, ['full_name' => 'Siti Aminah']);
        $this->member($familyC, $ownerC, ['full_name' => 'Siti Aminah']);

        Sanctum::actingAs($ownerA);

        $this->getJson('/api/v1/families/'.$familyA->uuid.'/family-links/candidates?search=Siti')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uuid', $candidate->uuid)
            ->assertJsonPath('data.0.family.uuid', $familyB->uuid)
            ->assertJsonPath('data.0.family.name', $familyB->name);
    }

    public function test_partner_tree_is_active_redacted_read_only_and_hides_internal_ids(): void
    {
        [$ownerA, $familyA, $ownerB, $familyB, $link] = $this->pendingLink();

        Sanctum::actingAs($ownerB);
        $this->postJson('/api/v1/family-links/'.$link->uuid.'/approve')->assertOk();

        $livingFather = $this->member($familyB, $ownerB, [
            'full_name' => 'Ayah Rahasia',
            'gender' => 'male',
            'is_alive' => true,
            'birth_date' => '1950-01-01',
        ]);
        MemberRelationship::factory()->create([
            'family_id' => $familyB->id,
            'source_member_id' => $livingFather->id,
            'target_member_id' => $link->linked_member_id,
            'relationship_type' => MemberRelationship::TYPE_FATHER,
        ]);

        Sanctum::actingAs($ownerA);
        $response = $this->getJson('/api/v1/family-links/'.$link->uuid.'/partner-tree?family_uuid='.$familyA->uuid.'&depth=2')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertJsonPath('data.read_only', true)
            ->assertJsonPath('data.redacted', true)
            ->assertJsonPath('data.root_member_uuid', $link->linkedMember->uuid)
            ->assertJsonMissingPath('data.nodes.0.id');

        $response->assertJsonFragment([
            'uuid' => $link->linkedMember->uuid,
            'name' => $link->linkedMember->full_name,
            'is_redacted' => false,
        ]);
        $response->assertJsonFragment([
            'name' => 'Anggota Hidup',
            'is_redacted' => true,
        ]);
        $response->assertJsonPath('data.bridge_member_uuid', $link->member->uuid);
        $response->assertJsonMissing(['name' => 'Ayah Rahasia']);
    }

    public function test_regular_member_cannot_search_link_candidates(): void
    {
        [$ownerA, $familyA] = $this->family(FamilyUserRole::ROLE_OWNER);
        [$ownerB, $familyB] = $this->family(FamilyUserRole::ROLE_OWNER);
        $memberUser = User::factory()->create();
        FamilyUserRole::factory()->create(['family_id' => $familyA->id, 'user_id' => $memberUser->id, 'role' => FamilyUserRole::ROLE_MEMBER]);
        FamilyUserRole::factory()->create(['family_id' => $familyB->id, 'user_id' => $memberUser->id, 'role' => FamilyUserRole::ROLE_MEMBER]);
        $this->member($familyB, $ownerB, ['full_name' => 'Siti Aminah']);

        Sanctum::actingAs($memberUser);

        $this->getJson('/api/v1/families/'.$familyA->uuid.'/family-links/candidates?search=Siti')
            ->assertForbidden();
    }

    public function test_partner_tree_defaults_to_a_family_side_the_user_belongs_to(): void
    {
        [$ownerA, $familyA, $ownerB, $familyB, $link] = $this->pendingLink();

        Sanctum::actingAs($ownerB);
        $this->postJson('/api/v1/family-links/'.$link->uuid.'/approve')->assertOk();

        Sanctum::actingAs($ownerA);
        $this->getJson('/api/v1/family-links/'.$link->uuid.'/partner-tree')
            ->assertOk()
            ->assertJsonPath('data.root_member_uuid', $link->linkedMember->uuid)
            ->assertJsonPath('data.read_only', true);
    }

    public function test_pending_link_has_no_partner_tree(): void
    {
        [$ownerA, $familyA, $ownerB, $familyB, $link] = $this->pendingLink();

        Sanctum::actingAs($ownerA);

        $this->getJson('/api/v1/family-links/'.$link->uuid.'/partner-tree?family_uuid='.$familyA->uuid)
            ->assertNotFound();
    }

    public function test_partner_tree_rejects_a_family_side_the_user_does_not_belong_to(): void
    {
        [$ownerA, $familyA, $ownerB, $familyB, $link] = $this->pendingLink();

        Sanctum::actingAs($ownerB);
        $this->postJson('/api/v1/family-links/'.$link->uuid.'/approve')->assertOk();

        FamilyUserRole::query()
            ->where('family_id', $familyB->id)
            ->where('user_id', $ownerA->id)
            ->delete();

        Sanctum::actingAs($ownerA);
        $this->getJson('/api/v1/family-links/'.$link->uuid.'/partner-tree?family_uuid='.$familyB->uuid)
            ->assertForbidden();
    }

    public function test_outsider_cannot_list_or_view_links(): void
    {
        [, $familyA, , , $link] = $this->pendingLink();
        $outsider = User::factory()->create();
        Sanctum::actingAs($outsider);

        $this->getJson('/api/v1/families/'.$familyA->uuid.'/family-links')->assertForbidden();
        $this->getJson('/api/v1/family-links/'.$link->uuid)->assertForbidden();
    }

    /** @return array{0: User, 1: Family} */
    private function family(string $role): array
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

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function member(Family $family, User $creator, array $attributes = []): FamilyMember
    {
        return FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $creator->id,
            ...$attributes,
        ]);
    }

    /**
     * @return array{0: User, 1: Family, 2: User, 3: Family, 4: FamilyLink}
     */
    private function pendingLink(): array
    {
        [$ownerA, $familyA] = $this->family(FamilyUserRole::ROLE_OWNER);
        [$ownerB, $familyB] = $this->family(FamilyUserRole::ROLE_OWNER);
        FamilyUserRole::factory()->create(['family_id' => $familyB->id, 'user_id' => $ownerA->id, 'role' => FamilyUserRole::ROLE_MEMBER]);
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
}
