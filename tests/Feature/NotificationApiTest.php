<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\PushDeviceToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_and_mark_only_their_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $user->id]);
        Notification::factory()->create(['user_id' => $user->id]);
        $foreign = Notification::factory()->create(['user_id' => $other->id]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/notifications?status=unread')
            ->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.is_read', false);
        $this->postJson('/api/v1/notifications/'.$notification->uuid.'/read')
            ->assertOk()->assertJsonPath('data.is_read', true);
        $this->postJson('/api/v1/notifications/'.$foreign->uuid.'/read')->assertNotFound();
        $this->postJson('/api/v1/notifications/read-all')->assertOk()->assertJsonPath('data.updated', 1);

        $this->assertDatabaseHas('notifications', ['id' => $notification->id, 'is_read' => true]);
        $this->assertDatabaseHas('notifications', ['id' => $foreign->id, 'is_read' => false]);
    }

    public function test_user_can_register_android_and_ios_devices_and_remove_their_own_device(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        foreach (PushDeviceToken::PLATFORMS as $platform) {
            $this->postJson('/api/v1/push-devices', ['platform' => $platform, 'token' => $platform.'-token'])
                ->assertCreated()->assertJsonPath('data.platform', $platform);
        }

        $device = PushDeviceToken::query()->where('platform', 'android')->firstOrFail();
        $this->deleteJson('/api/v1/push-devices/'.$device->uuid)->assertOk();
        $this->assertSoftDeleted('push_device_tokens', ['id' => $device->id]);
    }

    public function test_device_registration_is_validated_and_requires_authentication(): void
    {
        $this->postJson('/api/v1/push-devices', ['platform' => 'web', 'token' => 'x'])->assertUnauthorized();
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/v1/push-devices', ['platform' => 'web', 'token' => ''])->assertUnprocessable();
        $this->getJson('/api/v1/notifications?status=invalid')->assertUnprocessable();
    }
}
