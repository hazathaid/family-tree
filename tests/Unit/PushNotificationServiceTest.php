<?php

namespace Tests\Unit;

use App\Models\Notification;
use App\Models\PushDeviceToken;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PushNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_an_fcm_compatible_payload_for_android_and_ios(): void
    {
        config(['push.endpoint' => 'https://push.example.test/messages', 'push.access_token' => 'secret']);
        Http::fake(['push.example.test/*' => Http::response(['name' => 'sent'], 200)]);
        $notification = Notification::factory()->create(['data' => ['event_uuid' => 'event-1']]);

        foreach (PushDeviceToken::PLATFORMS as $platform) {
            $device = PushDeviceToken::factory()->create(['user_id' => $notification->user_id, 'platform' => $platform]);
            $this->assertTrue(app(PushNotificationService::class)->send($device, $notification));
        }

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer secret')
            && $request['message']['notification']['title'] === $notification->title
            && $request['message']['data']['event_uuid'] === 'event-1');
    }

    public function test_it_skips_delivery_when_push_is_not_configured(): void
    {
        config(['push.endpoint' => null, 'push.access_token' => null]);

        $this->assertFalse(app(PushNotificationService::class)->send(PushDeviceToken::factory()->make(), Notification::factory()->make()));
    }
}
