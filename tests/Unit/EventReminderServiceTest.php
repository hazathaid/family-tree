<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\Family;
use App\Models\FamilyUserRole;
use App\Models\Notification;
use App\Models\User;
use App\Services\EventReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventReminderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_notifies_active_family_users_once_for_events_within_one_day(): void
    {
        $family = Family::factory()->create();
        $first = User::factory()->create();
        $second = User::factory()->create();
        FamilyUserRole::factory()->create(['family_id' => $family->id, 'user_id' => $first->id]);
        FamilyUserRole::factory()->create(['family_id' => $family->id, 'user_id' => $second->id]);
        $event = Event::factory()->create(['family_id' => $family->id, 'organizer_id' => $first->id, 'event_date' => now()->addHours(12)]);

        $service = app(EventReminderService::class);

        $this->assertSame(2, $service->sendDueReminders());
        $this->assertSame(0, $service->sendDueReminders());
        $this->assertSame(2, Notification::query()->where('event_id', $event->id)->count());
        $this->assertNotNull($event->refresh()->reminder_sent_at);
    }

    public function test_it_ignores_events_outside_the_reminder_window(): void
    {
        Event::factory()->create(['event_date' => now()->addDays(2)]);

        $this->assertSame(0, app(EventReminderService::class)->sendDueReminders());
        $this->assertDatabaseCount('notifications', 0);
    }
}
