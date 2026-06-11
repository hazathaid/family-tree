<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyBranch;
use App\Models\FamilyUserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FamilyBranchApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_update_view_and_delete_branch(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create([
            'family_id' => $family->id,
            'user_id' => $owner->id,
        ]);
        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/v1/families/'.$family->uuid.'/branches', [
            'name' => 'Cabang Jakarta',
            'description' => 'Keluarga wilayah Jakarta',
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Cabang Jakarta');

        $branchUuid = $response->json('data.uuid');

        $this->getJson('/api/v1/families/'.$family->uuid.'/branches/'.$branchUuid)
            ->assertOk()
            ->assertJsonPath('data.name', 'Cabang Jakarta');

        $this->putJson('/api/v1/families/'.$family->uuid.'/branches/'.$branchUuid, [
            'name' => 'Cabang Jabodetabek',
            'description' => 'Keluarga wilayah Jabodetabek',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Cabang Jabodetabek');

        $branch = FamilyBranch::query()->where('uuid', $branchUuid)->firstOrFail();

        $this->deleteJson('/api/v1/families/'.$family->uuid.'/branches/'.$branchUuid)
            ->assertOk()
            ->assertJsonPath('message', 'Family branch deleted');

        $this->assertSoftDeleted('family_branches', ['id' => $branch->id]);
    }

    public function test_member_can_view_but_cannot_create_branch(): void
    {
        [$owner, $member] = User::factory()->count(2)->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        $branch = FamilyBranch::factory()->create(['family_id' => $family->id]);
        FamilyUserRole::factory()->create([
            'family_id' => $family->id,
            'user_id' => $member->id,
            'role' => FamilyUserRole::ROLE_MEMBER,
        ]);
        Sanctum::actingAs($member);

        $this->getJson('/api/v1/families/'.$family->uuid.'/branches/'.$branch->uuid)
            ->assertOk()
            ->assertJsonPath('data.uuid', $branch->uuid);

        $this->postJson('/api/v1/families/'.$family->uuid.'/branches', [
            'name' => 'Cabang Baru',
        ])->assertForbidden();
    }
}
