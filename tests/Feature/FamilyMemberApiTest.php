<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyBranch;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\MemberRelationship;
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
            'religion' => 'islam',
            'birth_date' => '1980-01-10',
            'birth_place' => 'Bandung',
            'is_alive' => true,
            'biography' => 'Pendiri arsip keluarga.',
        ])->assertCreated()
            ->assertJsonPath('data.full_name', 'Siti Aminah')
            ->assertJsonPath('data.religion', 'islam')
            ->assertJsonPath('data.memorial_prefix', '')
            ->assertJsonPath('data.family_uuid', $family->uuid)
            ->assertJsonPath('data.family_branch_uuid', $branch->uuid);

        $memberUuid = $response->json('data.uuid');

        $this->getJson('/api/v1/family-members/'.$memberUuid)
            ->assertOk()
            ->assertJsonPath('data.nickname', 'Siti');

        $this->getJson('/api/v1/family-members?family_uuid='.$family->uuid)
            ->assertOk()
            ->assertJsonFragment(['uuid' => $memberUuid]);

        $this->putJson('/api/v1/family-members/'.$memberUuid, [
            'family_branch_uuid' => $branch->uuid,
            'full_name' => 'Siti Aminah Rahman',
            'nickname' => 'Aminah',
            'gender' => 'female',
            'religion' => 'islam',
            'birth_date' => '1980-01-10',
            'birth_place' => 'Bandung',
            'is_alive' => false,
            'death_date' => '2024-05-01',
            'death_place' => 'Jakarta',
            'biography' => 'Riwayat keluarga diperbarui.',
        ])->assertOk()
            ->assertJsonPath('data.full_name', 'Siti Aminah Rahman')
            ->assertJsonPath('data.is_alive', false)
            ->assertJsonPath('data.memorial_prefix', 'Almh. ')
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

    public function test_directory_filters_sorts_and_paginates_with_family_isolation(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        $otherFamily = Family::factory()->create();
        $branch = FamilyBranch::factory()->create(['family_id' => $family->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $owner->id]);
        FamilyMember::factory()->create(['family_id' => $family->id, 'family_branch_id' => $branch->id, 'full_name' => 'Budi Hidup', 'gender' => 'male', 'is_alive' => true]);
        FamilyMember::factory()->deceased()->create(['family_id' => $family->id, 'family_branch_id' => $branch->id, 'full_name' => 'Budi Wafat', 'gender' => 'male']);
        FamilyMember::factory()->create(['family_id' => $otherFamily->id, 'full_name' => 'Budi Rahasia', 'gender' => 'male', 'is_alive' => true]);
        Sanctum::actingAs($owner);

        $this->getJson('/api/v1/family-members?'.http_build_query([
            'family_uuid' => $family->uuid,
            'search' => 'Budi',
            'gender' => 'male',
            'is_alive' => true,
            'branch_uuid' => $branch->uuid,
            'sort' => 'name_desc',
            'limit' => 1,
        ]))->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.full_name', 'Budi Hidup')
            ->assertJsonPath('data.total', 1)
            ->assertJsonMissing(['full_name' => 'Budi Rahasia']);
    }

    public function test_directory_requires_an_authorized_family_uuid(): void
    {
        $user = User::factory()->create();
        $foreignFamily = Family::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/family-members?family_uuid='.$foreignFamily->uuid)
            ->assertForbidden();
    }

    public function test_member_detail_exposes_relationship_to_viewer_from_linked_member(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $user->id]);

        $father = $this->member($family, 'Ayah Dedi', 'male', '1970-01-01', $user);
        $mother = $this->member($family, 'Ibu Rini', 'female', '1972-01-01', $user);
        $self = $this->member($family, 'Saya Arif', 'male', '1995-01-01', $user);
        $self->update(['user_id' => $user->id]);

        $this->relationship($father, $self, 'father');
        $this->relationship($mother, $self, 'mother');

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/family-members/'.$father->uuid)
            ->assertOk()
            ->assertJsonPath('data.relationship_to_viewer', 'Ayah');

        $this->getJson('/api/v1/family-members/'.$mother->uuid)
            ->assertOk()
            ->assertJsonPath('data.relationship_to_viewer', 'Ibu');

        $this->getJson('/api/v1/family-members/'.$self->uuid)
            ->assertOk()
            ->assertJsonPath('data.relationship_to_viewer', 'Saya');
    }

    public function test_member_detail_relationship_to_viewer_is_null_without_linked_member(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        $member = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $owner->id]);
        Sanctum::actingAs($owner);

        $this->getJson('/api/v1/family-members/'.$member->uuid)
            ->assertOk()
            ->assertJsonPath('data.relationship_to_viewer', null);
    }

    private function member(Family $family, string $name, string $gender, string $birthDate, User $user): FamilyMember
    {
        return FamilyMember::factory()->create([
            'family_id' => $family->id,
            'full_name' => $name,
            'gender' => $gender,
            'birth_date' => $birthDate,
            'created_by' => $user->id,
        ]);
    }

    private function relationship(FamilyMember $source, FamilyMember $target, string $type): MemberRelationship
    {
        return MemberRelationship::factory()->create([
            'family_id' => $source->family_id,
            'source_member_id' => $source->id,
            'target_member_id' => $target->id,
            'relationship_type' => $type,
        ]);
    }

    private function tinyPng(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=') ?: '';
    }
}
