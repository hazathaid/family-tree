<?php

namespace Tests\Unit;

use App\Jobs\SendPushNotification;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_in_app_notification_and_queues_push_delivery(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $notification = app(NotificationService::class)->notify($user, 'article_published', 'Artikel baru', 'Sebuah artikel diterbitkan.', ['article_uuid' => 'abc']);

        $this->assertDatabaseHas('notifications', ['id' => $notification->id, 'user_id' => $user->id, 'type' => 'article_published']);
        Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->notificationId === $notification->id);
    }

    public function test_mark_read_is_idempotent(): void
    {
        $notification = Notification::factory()->create();
        $service = app(NotificationService::class);

        $first = $service->markRead($notification);
        $second = $service->markRead($first);

        $this->assertTrue($second->is_read);
        $this->assertNotNull($second->read_at);
        $this->assertSame($first->read_at->toISOString(), $second->read_at->toISOString());
    }
}
