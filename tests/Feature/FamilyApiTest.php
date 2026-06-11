<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyUserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FamilyApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_family_and_becomes_owner(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/families', [
            'name' => 'Keluarga Besar Ahmad',
            'description' => 'Trah Ahmad',
            'origin_city' => 'Bandung',
        ])->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Keluarga Besar Ahmad')
            ->assertJsonPath('data.slug', 'keluarga-besar-ahmad');

        $family = Family::query()->firstOrFail();

        $this->assertDatabaseHas('family_user_roles', [
            'family_id' => $family->id,
            'user_id' => $user->id,
            'role' => FamilyUserRole::ROLE_OWNER,
        ]);
        $this->assertTrue($user->refresh()->hasRole(FamilyUserRole::ROLE_OWNER));
    }

    public function test_owner_can_view_update_and_delete_family(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create([
            'family_id' => $family->id,
            'user_id' => $owner->id,
        ]);
        Sanctum::actingAs($owner);

        $this->getJson('/api/v1/families/'.$family->uuid)
            ->assertOk()
            ->assertJsonPath('data.uuid', $family->uuid);

        $this->putJson('/api/v1/families/'.$family->uuid, [
            'name' => 'Keluarga Besar Baru',
            'description' => 'Deskripsi baru',
            'origin_city' => 'Jakarta',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Keluarga Besar Baru')
            ->assertJsonPath('data.slug', 'keluarga-besar-baru');

        $this->deleteJson('/api/v1/families/'.$family->uuid)
            ->assertOk()
            ->assertJsonPath('message', 'Family deleted');

        $this->assertSoftDeleted('families', ['id' => $family->id]);
    }

    public function test_member_cannot_update_family(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        FamilyUserRole::factory()->create([
            'family_id' => $family->id,
            'user_id' => $member->id,
            'role' => FamilyUserRole::ROLE_MEMBER,
        ]);
        Sanctum::actingAs($member);

        $this->putJson('/api/v1/families/'.$family->uuid, [
            'name' => 'Tidak Boleh',
        ])->assertForbidden()
            ->assertJsonPath('success', false);
    }
}
