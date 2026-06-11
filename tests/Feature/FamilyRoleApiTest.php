<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyUserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FamilyRoleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_invite_member_and_assign_role(): void
    {
        [$owner, $invitee] = User::factory()->count(2)->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create([
            'family_id' => $family->id,
            'user_id' => $owner->id,
        ]);
        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/v1/families/'.$family->uuid.'/roles/invite', [
            'email' => $invitee->email,
            'role' => FamilyUserRole::ROLE_MEMBER,
        ])->assertCreated()
            ->assertJsonPath('data.role', FamilyUserRole::ROLE_MEMBER);

        $membershipUuid = $response->json('data.uuid');

        $this->patchJson('/api/v1/families/'.$family->uuid.'/roles/'.$membershipUuid, [
            'role' => FamilyUserRole::ROLE_ADMIN,
        ])->assertOk()
            ->assertJsonPath('data.role', FamilyUserRole::ROLE_ADMIN);

        $this->assertDatabaseHas('family_user_roles', [
            'family_id' => $family->id,
            'user_id' => $invitee->id,
            'role' => FamilyUserRole::ROLE_ADMIN,
        ]);
    }

    public function test_last_owner_cannot_be_removed_or_demoted(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        $membership = FamilyUserRole::factory()->owner()->create([
            'family_id' => $family->id,
            'user_id' => $owner->id,
        ]);
        Sanctum::actingAs($owner);

        $this->patchJson('/api/v1/families/'.$family->uuid.'/roles/'.$membership->uuid, [
            'role' => FamilyUserRole::ROLE_ADMIN,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('role');

        $this->deleteJson('/api/v1/families/'.$family->uuid.'/roles/'.$membership->uuid)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role');

        $this->assertDatabaseHas('family_user_roles', [
            'id' => $membership->id,
            'role' => FamilyUserRole::ROLE_OWNER,
            'deleted_at' => null,
        ]);
    }

    public function test_admin_cannot_manage_family_roles(): void
    {
        [$owner, $admin, $invitee] = User::factory()->count(3)->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create([
            'family_id' => $family->id,
            'user_id' => $owner->id,
        ]);
        FamilyUserRole::factory()->admin()->create([
            'family_id' => $family->id,
            'user_id' => $admin->id,
        ]);
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/families/'.$family->uuid.'/roles/invite', [
            'email' => $invitee->email,
            'role' => FamilyUserRole::ROLE_MEMBER,
        ])->assertForbidden();
    }
}
