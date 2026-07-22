<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyUserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_owner_can_upload_validated_family_assets_and_member_cannot(): void
    {
        Storage::fake('public');
        [$owner, $member] = User::factory()->count(2)->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $owner->id]);
        FamilyUserRole::factory()->create(['family_id' => $family->id, 'user_id' => $member->id, 'role' => FamilyUserRole::ROLE_MEMBER]);
        Sanctum::actingAs($owner);

        $this->postJson('/api/v1/families/'.$family->uuid.'/assets', [
            'logo' => UploadedFile::fake()->image('logo.jpg', 200, 200),
            'cover_image' => UploadedFile::fake()->image('cover.png', 1200, 400),
        ])->assertOk()
            ->assertJsonPath('data.privacy', 'members_only')
            ->assertJsonStructure(['data' => ['logo_url', 'cover_image_url']]);

        $family->refresh();
        Storage::disk('public')->assertExists($family->logo);
        Storage::disk('public')->assertExists($family->cover_image);
        $this->assertDatabaseHas('activity_logs', ['family_id' => $family->id, 'activity_type' => 'FAMILY_ASSETS_UPDATED']);

        Sanctum::actingAs($member);
        $this->postJson('/api/v1/families/'.$family->uuid.'/assets', [
            'logo' => UploadedFile::fake()->image('blocked.jpg'),
        ])->assertForbidden();
    }

    public function test_family_assets_reject_invalid_files(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $owner->id]);
        Sanctum::actingAs($owner);

        $this->postJson('/api/v1/families/'.$family->uuid.'/assets', [
            'logo' => UploadedFile::fake()->create('payload.pdf', 10, 'application/pdf'),
        ])->assertUnprocessable()->assertJsonValidationErrors('logo');
    }
}
