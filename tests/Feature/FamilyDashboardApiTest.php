<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\Event;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\MemberPhoto;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FamilyDashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_family_dashboard_returns_statistics(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        FamilyUserRole::factory()->owner()->create([
            'family_id' => $family->id,
            'user_id' => $user->id,
        ]);
        Sanctum::actingAs($user);

        DB::table('family_members')->insert([
            [
                'id' => 1,
                'uuid' => (string) Str::uuid(),
                'family_id' => $family->id,
                'full_name' => 'Living Member',
                'is_alive' => true,
                'death_date' => null,
                'created_by' => $user->id,
            ],
            [
                'id' => 2,
                'uuid' => (string) Str::uuid(),
                'family_id' => $family->id,
                'full_name' => 'Deceased Member',
                'is_alive' => false,
                'death_date' => '2024-01-01',
                'created_by' => $user->id,
            ],
        ]);
        Article::factory()->count(2)->create([
            'family_id' => $family->id,
            'author_id' => $user->id,
        ]);
        MemberPhoto::factory()->create([
            'family_id' => $family->id,
            'uploaded_by' => $user->id,
        ]);
        Event::factory()->create(['family_id' => $family->id, 'organizer_id' => $user->id]);

        $this->getJson('/api/v1/families/'.$family->uuid.'/dashboard')
            ->assertOk()
            ->assertJsonPath('data.total_members', 2)
            ->assertJsonPath('data.living_members', 1)
            ->assertJsonPath('data.deceased_members', 1)
            ->assertJsonPath('data.total_articles', 2)
            ->assertJsonPath('data.total_photos', 1)
            ->assertJsonPath('data.total_events', 1);
    }

    public function test_dashboard_returns_bounded_rich_sections_isolated_to_family_and_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id, 'origin_city' => 'Bandung']);
        $otherFamily = Family::factory()->create(['created_by' => $other->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $user->id]);
        $member = FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $user->id,
            'birth_date' => now()->addDays(2)->subYears(30),
            'is_alive' => true,
        ]);
        FamilyMember::factory()->create(['family_id' => $otherFamily->id, 'created_by' => $other->id]);
        ActivityLog::factory()->create(['family_id' => $family->id, 'user_id' => $user->id]);
        Event::factory()->create(['family_id' => $family->id, 'organizer_id' => $user->id, 'event_date' => now()->addDay()]);
        Notification::factory()->create([
            'user_id' => $user->id,
            'data' => ['family_uuid' => $family->uuid],
            'is_read' => false,
        ]);
        Notification::factory()->create([
            'user_id' => $other->id,
            'data' => ['family_uuid' => $family->uuid],
            'is_read' => false,
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/families/'.$family->uuid.'/dashboard')
            ->assertOk()
            ->assertJsonCount(1, 'data.recent_activity')
            ->assertJsonCount(1, 'data.upcoming_birthdays')
            ->assertJsonCount(1, 'data.upcoming_events')
            ->assertJsonCount(1, 'data.notification_summary.recent')
            ->assertJsonPath('data.notification_summary.unread_count', 1)
            ->assertJsonPath('data.family_facts.origin_city', 'Bandung')
            ->assertJsonPath('data.recent_members.0.uuid', $member->uuid);
    }
}
