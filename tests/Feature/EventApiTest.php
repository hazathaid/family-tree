<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\Family;
use App\Models\FamilyUserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EventApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_an_event(): void
    {
        [$admin, $family] = $this->familyUser(FamilyUserRole::ROLE_ADMIN);
        Sanctum::actingAs($admin);

        $uuid = $this->postJson('/api/v1/events', [
            'family_uuid' => $family->uuid,
            'title' => 'Reuni Keluarga',
            'description' => 'Reuni tahunan',
            'event_date' => now()->addWeek()->format('Y-m-d H:i:s'),
            'location' => 'Bandung',
        ])->assertCreated()->assertJsonPath('data.title', 'Reuni Keluarga')->json('data.uuid');

        $this->assertDatabaseHas('activity_logs', ['activity_type' => ActivityLog::EVENT_CREATED]);
        $this->getJson('/api/v1/events/'.$uuid)->assertOk()->assertJsonPath('data.location', 'Bandung');
        $this->putJson('/api/v1/events/'.$uuid, ['title' => 'Reuni Besar'])->assertOk()->assertJsonPath('data.title', 'Reuni Besar');
        $this->deleteJson('/api/v1/events/'.$uuid)->assertOk();
        $this->assertSoftDeleted('events', ['uuid' => $uuid]);
    }

    public function test_family_member_can_list_view_and_update_rsvp(): void
    {
        [$member, $family] = $this->familyUser();
        $event = Event::factory()->create(['family_id' => $family->id]);
        Sanctum::actingAs($member);

        $this->getJson('/api/v1/events?family_uuid='.$family->uuid)->assertOk()->assertJsonCount(1, 'data');
        $this->postJson('/api/v1/events/'.$event->uuid.'/rsvp', ['status' => 'yes'])
            ->assertOk()->assertJsonPath('data.status', 'yes');
        $this->postJson('/api/v1/events/'.$event->uuid.'/rsvp', ['status' => 'maybe'])
            ->assertOk()->assertJsonPath('data.status', 'maybe');

        $this->assertDatabaseCount('event_attendees', 1);
        $this->assertDatabaseHas('event_attendees', ['event_id' => $event->id, 'user_id' => $member->id, 'status' => EventAttendee::STATUSES[2]]);
    }

    public function test_user_cannot_access_another_family_event(): void
    {
        $event = Event::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/events/'.$event->uuid)->assertForbidden();
        $this->postJson('/api/v1/events/'.$event->uuid.'/rsvp', ['status' => 'yes'])->assertForbidden();
    }

    public function test_event_input_and_rsvp_are_validated(): void
    {
        [$admin, $family] = $this->familyUser(FamilyUserRole::ROLE_ADMIN);
        $event = Event::factory()->create(['family_id' => $family->id]);
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/events', ['family_uuid' => $family->uuid, 'event_date' => now()->subDay()])->assertUnprocessable();
        $this->postJson('/api/v1/events/'.$event->uuid.'/rsvp', ['status' => 'unknown'])->assertUnprocessable();
    }

    private function familyUser(string $role = FamilyUserRole::ROLE_MEMBER): array
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        FamilyUserRole::factory()->create(['family_id' => $family->id, 'user_id' => $user->id, 'role' => $role]);

        return [$user, $family];
    }
}
