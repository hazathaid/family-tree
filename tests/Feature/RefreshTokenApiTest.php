<?php

namespace Tests\Feature;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RefreshTokenApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_issues_access_and_refresh_tokens(): void
    {
        $this->user();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'budi@example.com',
            'password' => 'secret123',
            'device_name' => 'feature-test',
        ])->assertOk()->assertJsonStructure(['data' => ['token', 'refresh_token', 'user']]);

        $this->assertNotEmpty($response->json('data.refresh_token'));
        $this->assertDatabaseCount('refresh_tokens', 1);
    }

    public function test_refresh_rotates_tokens_and_rejects_reuse(): void
    {
        $this->user();
        $login = $this->postJson('/api/v1/auth/login', ['email' => 'budi@example.com', 'password' => 'secret123'])->assertOk();
        $refresh = $login->json('data.refresh_token');

        $rotated = $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $refresh, 'device_name' => 'feature-test'])
            ->assertOk()
            ->assertJsonStructure(['data' => ['token', 'refresh_token', 'user']]);

        $this->assertNotSame($refresh, $rotated->json('data.refresh_token'));

        $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $refresh])->assertUnauthorized();
    }

    public function test_refresh_rejects_invalid_and_expired_tokens(): void
    {
        $this->user();
        $login = $this->postJson('/api/v1/auth/login', ['email' => 'budi@example.com', 'password' => 'secret123'])->assertOk();
        $refresh = $login->json('data.refresh_token');

        $this->postJson('/api/v1/auth/refresh', ['refresh_token' => 'not-a-real-token'])->assertUnauthorized();

        $this->travel(31)->days();
        $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $refresh])->assertUnauthorized();

        $this->travelBack();
    }

    public function test_refresh_rejects_suspended_accounts(): void
    {
        $user = $this->user();
        $login = $this->postJson('/api/v1/auth/login', ['email' => 'budi@example.com', 'password' => 'secret123'])->assertOk();

        $user->update(['status' => 'suspended']);

        $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $login->json('data.refresh_token')])
            ->assertUnauthorized();
    }

    public function test_logout_revokes_the_associated_refresh_token(): void
    {
        $this->user();
        $login = $this->postJson('/api/v1/auth/login', ['email' => 'budi@example.com', 'password' => 'secret123'])->assertOk();

        $this->withToken($login->json('data.token'))->postJson('/api/v1/auth/logout')->assertOk();

        $this->assertDatabaseMissing('refresh_tokens', ['revoked_at' => null]);
        $this->assertSame(0, RefreshToken::query()->whereNull('revoked_at')->count());
    }

    private function user(): User
    {
        return User::factory()->create([
            'email' => 'budi@example.com',
            'password' => Hash::make('secret123'),
            'status' => 'active',
        ]);
    }
}
