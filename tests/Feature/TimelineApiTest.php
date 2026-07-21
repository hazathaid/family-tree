<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Family;
use App\Models\FamilyUserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TimelineApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_creation_is_added_to_family_timeline(): void
    {
        [$user, $family] = $this->familyMember();
        Sanctum::actingAs($user);

        $memberUuid = $this->postJson('/api/v1/family-members', [
            'family_uuid' => $family->uuid,
            'full_name' => 'Budi Santoso',
            'gender' => 'male',
        ])->assertCreated()->json('data.uuid');

        $this->getJson('/api/v1/timeline?family_uuid='.$family->uuid)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.type', ActivityLog::MEMBER_CREATED)
            ->assertJsonPath('data.0.payload.subject_uuid', $memberUuid)
            ->assertJsonPath('data.0.message', 'Budi Santoso ditambahkan ke keluarga');
    }

    public function test_timeline_can_filter_activity_categories(): void
    {
        [$user, $family] = $this->familyMember();
        ActivityLog::factory()->create(['family_id' => $family->id, 'user_id' => $user->id, 'activity_type' => ActivityLog::ARTICLE_CREATED, 'payload' => ['title' => 'Sejarah']]);
        ActivityLog::factory()->create(['family_id' => $family->id, 'user_id' => $user->id, 'activity_type' => ActivityLog::PHOTO_UPLOADED]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/timeline?family_uuid='.$family->uuid.'&type=articles')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', ActivityLog::ARTICLE_CREATED);
    }

    public function test_user_cannot_read_another_family_timeline(): void
    {
        [$owner, $family] = $this->familyMember();
        ActivityLog::factory()->create(['family_id' => $family->id, 'user_id' => $owner->id]);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/timeline?family_uuid='.$family->uuid)
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_timeline_filter_is_validated(): void
    {
        [$user] = $this->familyMember();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/timeline?type=invalid')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Validation Error');
    }

    private function familyMember(): array
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        FamilyUserRole::factory()->create(['family_id' => $family->id, 'user_id' => $user->id, 'role' => FamilyUserRole::ROLE_ADMIN]);

        return [$user, $family];
    }
}
