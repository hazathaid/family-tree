<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TreeRelativeMemberApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_add_relatives_from_tree_and_flag_is_exposed_in_tree_response(): void
    {
        [$owner, $family] = $this->userWithFamily(FamilyUserRole::ROLE_OWNER);
        $member = FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $owner->id,
            'gender' => 'male',
        ]);

        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/v1/family-members/{$member->uuid}/relatives", [
            'relation' => 'child',
            'full_name' => 'Anak API',
            'gender' => 'female',
            'is_alive' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.full_name', 'Anak API')
            ->assertJsonPath('data.family_uuid', $family->uuid);

        $this->assertDatabaseHas('family_members', [
            'family_id' => $family->id,
            'full_name' => 'Anak API',
            'created_by' => $owner->id,
        ]);
        $this->assertDatabaseHas('member_relationships', [
            'family_id' => $family->id,
            'source_member_id' => $member->id,
            'relationship_type' => 'father',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'family_id' => $family->id,
            'user_id' => $owner->id,
            'activity_type' => ActivityLog::TREE_RELATIVE_CREATED,
        ]);

        $this->getJson('/api/v1/tree/generate?'.http_build_query([
            'member_uuid' => $member->uuid,
            'mode' => 'full',
            'depth' => 1,
            'layout' => 'vertical',
        ]))
            ->assertOk()
            ->assertJsonFragment([
                'uuid' => $member->uuid,
                'can_add_relative' => true,
            ]);
    }

    public function test_member_can_only_add_relative_from_own_linked_node(): void
    {
        [$memberUser, $family] = $this->userWithFamily(FamilyUserRole::ROLE_MEMBER);
        $ownMember = FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $memberUser->id,
            'user_id' => $memberUser->id,
            'gender' => 'female',
        ]);
        $otherMember = FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $memberUser->id,
            'gender' => 'male',
        ]);

        Sanctum::actingAs($memberUser);

        $this->postJson("/api/v1/family-members/{$ownMember->uuid}/relatives", [
            'relation' => 'child',
            'full_name' => 'Anak Saya',
            'gender' => 'male',
            'is_alive' => true,
        ])->assertCreated();

        $this->postJson("/api/v1/family-members/{$otherMember->uuid}/relatives", [
            'relation' => 'child',
            'full_name' => 'Tidak Boleh',
            'gender' => 'male',
            'is_alive' => true,
        ])->assertForbidden();
    }

    public function test_outsider_cannot_add_relative(): void
    {
        [$owner, $family] = $this->userWithFamily(FamilyUserRole::ROLE_OWNER);
        $member = FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $owner->id,
            'gender' => 'male',
        ]);

        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/v1/family-members/{$member->uuid}/relatives", [
            'relation' => 'child',
            'full_name' => 'Orang Luar',
            'gender' => 'female',
            'is_alive' => true,
        ])->assertForbidden();
    }

    private function userWithFamily(string $role): array
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        FamilyUserRole::factory()->create([
            'family_id' => $family->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);

        return [$user, $family];
    }
}
