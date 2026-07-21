<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\MemberPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PhotoArchiveApiTest extends TestCase
{
    use RefreshDatabase;

    private function familyMember(): array
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        FamilyUserRole::factory()->create(['family_id' => $family->id, 'user_id' => $user->id, 'role' => FamilyUserRole::ROLE_MEMBER]);

        return [$user, $family];
    }

    public function test_member_can_manage_album_upload_photo_and_tag_member(): void
    {
        Storage::fake('public');
        [$user, $family] = $this->familyMember();
        $member = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id]);
        Sanctum::actingAs($user);

        $albumUuid = $this->postJson('/api/v1/photo-albums', ['family_uuid' => $family->uuid, 'name' => 'Lebaran'])
            ->assertCreated()->assertJsonPath('success', true)->json('data.uuid');
        $photoUuid = $this->post('/api/v1/member-photos', ['family_uuid' => $family->uuid, 'album_uuid' => $albumUuid, 'caption' => 'Keluarga', 'image' => UploadedFile::fake()->image('keluarga.png', 2400, 1600)])
            ->assertCreated()->assertJsonPath('data.album.uuid', $albumUuid)->json('data.uuid');
        $this->putJson('/api/v1/member-photos/'.$photoUuid.'/tags', ['member_uuids' => [$member->uuid]])
            ->assertOk()->assertJsonPath('data.tagged_members.0.uuid', $member->uuid);

        $photo = MemberPhoto::query()->where('uuid', $photoUuid)->firstOrFail();
        Storage::disk('public')->assertExists($photo->path);
        Storage::disk('public')->assertExists($photo->thumbnail_path);
        $this->getJson('/api/v1/member-photos?member_uuid='.$member->uuid)->assertOk()->assertJsonPath('data.0.uuid', $photoUuid);
    }

    public function test_outsider_cannot_view_family_photo(): void
    {
        [$owner, $family] = $this->familyMember();
        $photo = MemberPhoto::factory()->create(['family_id' => $family->id, 'uploaded_by' => $owner->id]);
        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/v1/member-photos/'.$photo->uuid)->assertForbidden();
    }

    public function test_tagging_rejects_member_from_another_family(): void
    {
        [$user, $family] = $this->familyMember();
        $photo = MemberPhoto::factory()->create(['family_id' => $family->id, 'uploaded_by' => $user->id]);
        $otherMember = FamilyMember::factory()->create();
        Sanctum::actingAs($user);
        $this->putJson('/api/v1/member-photos/'.$photo->uuid.'/tags', ['member_uuids' => [$otherMember->uuid]])->assertUnprocessable()->assertJsonPath('success', false);
    }

    public function test_upload_validation_uses_standard_envelope(): void
    {
        [$user] = $this->familyMember();
        Sanctum::actingAs($user);
        $this->postJson('/api/v1/member-photos', [])->assertUnprocessable()->assertJsonPath('message', 'Validation Error');
    }
}
