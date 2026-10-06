<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MemberDocumentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_upload_list_show_and_delete_a_document(): void
    {
        Storage::fake('public');
        [$owner, $family] = $this->familyUser(FamilyUserRole::ROLE_OWNER);
        $member = $this->member($family, $owner);
        Sanctum::actingAs($owner);

        $uuid = $this->postJson('/api/v1/family-members/'.$member->uuid.'/documents', [
            'title' => 'Kartu Keluarga',
            'category' => 'family_card',
            'document_date' => '2021-05-01',
            'file' => UploadedFile::fake()->create('kk.pdf', 200, 'application/pdf'),
        ])->assertCreated()->assertJsonPath('data.title', 'Kartu Keluarga')->json('data.uuid');

        $this->assertDatabaseHas('activity_logs', ['activity_type' => ActivityLog::MEMBER_DOCUMENT_UPLOADED]);

        $this->getJson('/api/v1/family-members/'.$member->uuid.'/documents')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uuid', $uuid);

        $this->getJson('/api/v1/member-documents/'.$uuid)
            ->assertOk()
            ->assertJsonPath('data.uuid', $uuid)
            ->assertJsonPath('data.member_uuid', $member->uuid);

        $this->get('/api/v1/member-documents/'.$uuid.'/download')->assertOk();

        $this->deleteJson('/api/v1/member-documents/'.$uuid)->assertOk();
        $this->assertSoftDeleted('member_documents', ['uuid' => $uuid]);
    }

    public function test_linked_member_can_manage_only_their_own_documents(): void
    {
        Storage::fake('public');
        [$owner, $family] = $this->familyUser(FamilyUserRole::ROLE_OWNER);
        $ownMember = $this->member($family, $owner);
        $otherMember = $this->member($family, $owner);

        $linked = User::factory()->create();
        FamilyUserRole::factory()->create(['family_id' => $family->id, 'user_id' => $linked->id, 'role' => FamilyUserRole::ROLE_MEMBER]);
        $ownMember->forceFill(['user_id' => $linked->id])->save();

        Sanctum::actingAs($linked);

        $this->postJson('/api/v1/family-members/'.$ownMember->uuid.'/documents', [
            'title' => 'Ijazah',
            'file' => UploadedFile::fake()->create('ijazah.pdf', 100, 'application/pdf'),
        ])->assertCreated();

        $this->postJson('/api/v1/family-members/'.$otherMember->uuid.'/documents', [
            'title' => 'Milik Orang Lain',
            'file' => UploadedFile::fake()->create('lain.pdf', 100, 'application/pdf'),
        ])->assertForbidden();
    }

    public function test_outsider_cannot_access_member_documents(): void
    {
        Storage::fake('public');
        [, $family] = $this->familyUser(FamilyUserRole::ROLE_OWNER);
        $member = $this->member($family, User::factory()->create());
        $outsider = User::factory()->create();
        Sanctum::actingAs($outsider);

        $this->getJson('/api/v1/family-members/'.$member->uuid.'/documents')->assertForbidden();
        $this->postJson('/api/v1/family-members/'.$member->uuid.'/documents', [
            'title' => 'Nope',
            'file' => UploadedFile::fake()->create('nope.pdf', 100, 'application/pdf'),
        ])->assertForbidden();
    }

    public function test_upload_validates_type_and_size(): void
    {
        Storage::fake('public');
        [$owner, $family] = $this->familyUser(FamilyUserRole::ROLE_OWNER);
        $member = $this->member($family, $owner);
        Sanctum::actingAs($owner);

        $this->postJson('/api/v1/family-members/'.$member->uuid.'/documents', [
            'title' => 'Skrip',
            'file' => UploadedFile::fake()->create('evil.exe', 100, 'application/octet-stream'),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');

        $this->postJson('/api/v1/family-members/'.$member->uuid.'/documents', [
            'file' => UploadedFile::fake()->create('big.pdf', 30000, 'application/pdf'),
        ])->assertUnprocessable()->assertJsonValidationErrors(['file', 'title']);
    }

    /**
     * @return array{0: User, 1: Family}
     */
    private function familyUser(string $role): array
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

    private function member(Family $family, User $user): FamilyMember
    {
        return FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $user->id,
            'full_name' => 'Anggota '.fake()->unique()->numberBetween(1, 99999),
        ]);
    }
}
