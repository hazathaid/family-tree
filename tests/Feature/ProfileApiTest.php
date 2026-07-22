<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_profile(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('data.uuid', $user->uuid)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile', [
            'name' => 'Siti Aminah',
            'email' => 'siti@example.com',
            'phone' => '081299988877',
            'current_password' => 'password',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Siti Aminah')
            ->assertJsonPath('data.email', 'siti@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Siti Aminah',
            'email' => 'siti@example.com',
            'phone' => '081299988877',
            'email_verified_at' => null,
        ]);
    }

    public function test_email_change_requires_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secret-password')]);
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile', [
            'name' => $user->name,
            'email' => 'changed@example.com',
            'phone' => $user->phone,
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');
    }

    public function test_user_can_change_password_and_revoke_tokens(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-secret')]);
        $user->createToken('existing-device');
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/profile/password', [
            'current_password' => 'old-secret',
            'password' => 'new-secret',
            'password_confirmation' => 'new-secret',
        ])->assertOk()
            ->assertJsonPath('message', 'Password changed');

        $this->assertTrue(Hash::check('new-secret', $user->refresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_user_cannot_change_password_with_wrong_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-secret')]);
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/profile/password', [
            'current_password' => 'wrong-secret',
            'password' => 'new-secret',
            'password_confirmation' => 'new-secret',
        ])->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors('current_password');
    }

    public function test_user_can_upload_avatar(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/profile/avatar', [
            'avatar' => UploadedFile::fake()->createWithContent('avatar.png', $this->tinyPng()),
        ])->assertOk()
            ->assertJsonPath('message', 'Avatar uploaded');

        Storage::disk('public')->assertExists($user->refresh()->avatar);
    }

    public function test_avatar_upload_rejects_invalid_file_type(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/profile/avatar', [
            'avatar' => UploadedFile::fake()->create('avatar.gif', 100, 'image/gif'),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('avatar');
    }

    public function test_user_can_manage_notification_preferences(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $preferences = [
            'email' => false,
            'push' => true,
            'event_reminders' => false,
            'family_updates' => true,
        ];

        $this->putJson('/api/v1/profile/notification-preferences', $preferences)
            ->assertOk()->assertExactJson([
                'success' => true,
                'message' => 'Preferences updated',
                'data' => $preferences,
            ]);

        $this->getJson('/api/v1/profile/notification-preferences')
            ->assertOk()->assertJsonPath('data.email', false);
        $this->assertSame($preferences, $user->refresh()->notification_preferences);
    }

    public function test_preferences_require_all_boolean_values(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/v1/profile/notification-preferences', ['email' => 'sometimes'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'push', 'event_reminders', 'family_updates']);
    }

    public function test_user_lists_safe_device_sessions_and_revokes_only_own_token(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $current = $user->createToken('Pixel 9');
        $otherToken = $other->createToken('Other device');

        $this->withToken($current->plainTextToken)->getJson('/api/v1/profile/sessions')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.device_name', 'Pixel 9')
            ->assertJsonPath('data.0.is_current', true)
            ->assertJsonMissingPath('data.0.id')
            ->assertJsonMissingPath('data.0.token')
            ->assertJsonMissingPath('data.0.ip_address');

        $otherUuid = $otherToken->accessToken->uuid;
        $this->withToken($current->plainTextToken)
            ->deleteJson('/api/v1/profile/sessions/'.$otherUuid)
            ->assertNotFound();
        $this->assertDatabaseHas('personal_access_tokens', ['uuid' => $otherUuid]);

        $currentUuid = $current->accessToken->uuid;
        $this->withToken($current->plainTextToken)
            ->deleteJson('/api/v1/profile/sessions/'.$currentUuid)
            ->assertOk()->assertJsonPath('data.revoked_current', true);
        $this->assertDatabaseMissing('personal_access_tokens', ['uuid' => $currentUuid]);
    }

    private function tinyPng(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=') ?: '';
    }
}
