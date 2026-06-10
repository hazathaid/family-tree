<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Ahmad',
            'email' => 'ahmad@example.com',
            'phone' => '08123456789',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Registration successful')
            ->assertJsonStructure(['data' => ['uuid', 'name', 'email', 'phone', 'status']]);

        $this->assertDatabaseHas('users', [
            'email' => 'ahmad@example.com',
            'phone' => '08123456789',
            'status' => 'active',
        ]);

        $this->assertNotNull(User::query()->first()?->uuid);
        Notification::assertSentTo(User::query()->first(), VerifyEmail::class);
    }

    public function test_user_can_login_and_receive_token(): void
    {
        $user = User::factory()->create([
            'email' => 'budi@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'budi@example.com',
            'password' => 'secret123',
            'device_name' => 'feature-test',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'user' => ['uuid', 'email']]]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'feature-test',
        ]);
        $this->assertNotNull($user->refresh()->last_login_at);
    }

    public function test_suspended_user_cannot_login(): void
    {
        User::factory()->suspended()->create([
            'email' => 'suspended@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'suspended@example.com',
            'password' => 'secret123',
        ])->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_authenticated_user_can_read_self_and_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test');

        $this->withToken($token->plainTextToken)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.uuid', $user->uuid);

        $this->withToken($token->plainTextToken)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logout successful');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_forgot_password_sends_reset_link(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'reset@example.com']);

        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'reset@example.com',
        ])->assertOk()
            ->assertJsonPath('success', true);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_user_can_request_email_verification_notification(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/auth/email/verification-notification')
            ->assertOk()
            ->assertJsonPath('message', 'Verification link sent');

        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
