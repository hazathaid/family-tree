<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyBranch;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FamilyMemberApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_view_update_list_and_delete_member(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        $branch = FamilyBranch::factory()->create(['family_id' => $family->id]);
        FamilyUserRole::factory()->owner()->create([
            'family_id' => $family->id,
            'user_id' => $owner->id,
        ]);
        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/v1/family-members', [
            'family_uuid' => $family->uuid,
            'family_branch_uuid' => $branch->uuid,
            'full_name' => 'Siti Aminah',
            'nickname' => 'Siti',
            'gender' => 'female',
            'birth_date' => '1980-01-10',
            'birth_place' => 'Bandung',
            'is_alive' => true,
            'biography' => 'Pendiri arsip keluarga.',
        ])->assertCreated()
            ->assertJsonPath('data.full_name', 'Siti Aminah')
            ->assertJsonPath('data.family_uuid', $family->uuid)
            ->assertJsonPath('data.family_branch_uuid', $branch->uuid);

        $memberUuid = $response->json('data.uuid');

        $this->getJson('/api/v1/family-members/'.$memberUuid)
            ->assertOk()
            ->assertJsonPath('data.nickname', 'Siti');

        $this->getJson('/api/v1/family-members')
            ->assertOk()
            ->assertJsonFragment(['uuid' => $memberUuid]);

        $this->putJson('/api/v1/family-members/'.$memberUuid, [
            'family_branch_uuid' => $branch->uuid,
            'full_name' => 'Siti Aminah Rahman',
            'nickname' => 'Aminah',
            'gender' => 'female',
            'birth_date' => '1980-01-10',
            'birth_place' => 'Bandung',
            'is_alive' => false,
            'death_date' => '2024-05-01',
            'death_place' => 'Jakarta',
            'biography' => 'Riwayat keluarga diperbarui.',
        ])->assertOk()
            ->assertJsonPath('data.full_name', 'Siti Aminah Rahman')
            ->assertJsonPath('data.is_alive', false)
            ->assertJsonPath('data.death_date', '2024-05-01');

        $member = FamilyMember::query()->where('uuid', $memberUuid)->firstOrFail();

        $this->deleteJson('/api/v1/family-members/'.$memberUuid)
            ->assertOk()
            ->assertJsonPath('message', 'Family member deleted');

        $this->assertSoftDeleted('family_members', ['id' => $member->id]);
    }

    public function test_member_can_view_but_cannot_create_member(): void
    {
        [$owner, $user] = User::factory()->count(2)->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        $member = FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $owner->id,
        ]);
        FamilyUserRole::factory()->create([
            'family_id' => $family->id,
            'user_id' => $user->id,
            'role' => FamilyUserRole::ROLE_MEMBER,
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/family-members/'.$member->uuid)
            ->assertOk()
            ->assertJsonPath('data.uuid', $member->uuid);

        $this->postJson('/api/v1/family-members', [
            'family_uuid' => $family->uuid,
            'full_name' => 'Budi Santoso',
        ])->assertForbidden();
    }

    public function test_deceased_member_requires_valid_death_date(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create([
            'family_id' => $family->id,
            'user_id' => $owner->id,
        ]);
        Sanctum::actingAs($owner);

        $this->postJson('/api/v1/family-members', [
            'family_uuid' => $family->uuid,
            'full_name' => 'Ahmad',
            'birth_date' => '1980-01-01',
            'is_alive' => false,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['death_date']);

        $this->postJson('/api/v1/family-members', [
            'family_uuid' => $family->uuid,
            'full_name' => 'Ahmad',
            'birth_date' => '1980-01-01',
            'is_alive' => false,
            'death_date' => '1979-12-31',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['death_date']);
    }

    public function test_owner_can_upload_member_photo_and_thumbnail_is_created(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        $member = FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $owner->id,
        ]);
        FamilyUserRole::factory()->owner()->create([
            'family_id' => $family->id,
            'user_id' => $owner->id,
        ]);
        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/v1/family-members/'.$member->uuid.'/photo', [
            'photo' => UploadedFile::fake()->createWithContent('profile.png', $this->tinyPng()),
        ])->assertOk()
            ->assertJsonPath('message', 'Family member photo uploaded');

        Storage::disk('public')->assertExists($response->json('data.profile_photo'));
        Storage::disk('public')->assertExists($response->json('data.profile_photo_thumbnail'));
    }

    private function tinyPng(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=') ?: '';
    }
}
