<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TotpService;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TwoFactorApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_enable_confirm_and_disable_two_factor(): void
    {
        $user = $this->plainUser();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/profile/two-factor')
            ->assertOk()
            ->assertJsonPath('data.enabled', false);

        $secret = $this->postJson('/api/v1/profile/two-factor', ['current_password' => 'secret123'])
            ->assertOk()
            ->assertJsonStructure(['data' => ['secret', 'otpauth_url']])
            ->json('data.secret');

        $code = app(TotpService::class)->code($secret);

        $this->postJson('/api/v1/profile/two-factor/confirm', ['code' => $code])
            ->assertOk()
            ->assertJsonCount(8, 'data.recovery_codes');

        $this->getJson('/api/v1/profile/two-factor')
            ->assertOk()
            ->assertJsonPath('data.enabled', true)
            ->assertJsonPath('data.recovery_codes_count', 8);

        $this->deleteJson('/api/v1/profile/two-factor', ['current_password' => 'secret123'])->assertOk();
        $this->assertNull($user->refresh()->two_factor_confirmed_at);
    }

    public function test_enabling_requires_the_current_password(): void
    {
        Sanctum::actingAs($this->plainUser());

        $this->postJson('/api/v1/profile/two-factor', ['current_password' => 'wrong'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');
    }

    public function test_login_requires_a_challenge_and_accepts_a_totp_code(): void
    {
        $user = $this->twoFactorUser();

        $login = $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonPath('data.two_factor_required', true)
            ->assertJsonPath('data.token', null);

        $code = app(TotpService::class)->code($user->two_factor_secret);

        $this->postJson('/api/v1/auth/two-factor-challenge', [
            'challenge_token' => $login->json('data.challenge_token'),
            'code' => $code,
        ])->assertOk()->assertJsonStructure(['data' => ['token', 'refresh_token', 'user']]);
    }

    public function test_challenge_accepts_a_single_use_recovery_code(): void
    {
        $user = $this->twoFactorUser();
        $login = $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'secret123'])->assertOk();
        $challenge = $login->json('data.challenge_token');

        $this->postJson('/api/v1/auth/two-factor-challenge', [
            'challenge_token' => $challenge,
            'recovery_code' => 'ABCDE-12345',
        ])->assertOk();

        $this->assertSame(0, count(app(TwoFactorService::class)->recoveryCodes($user->refresh())));

        $second = $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'secret123'])->assertOk();
        $this->postJson('/api/v1/auth/two-factor-challenge', [
            'challenge_token' => $second->json('data.challenge_token'),
            'recovery_code' => 'ABCDE-12345',
        ])->assertUnprocessable();
    }

    public function test_challenge_rejects_an_invalid_code(): void
    {
        $user = $this->twoFactorUser();
        $login = $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'secret123'])->assertOk();

        $this->postJson('/api/v1/auth/two-factor-challenge', [
            'challenge_token' => $login->json('data.challenge_token'),
            'code' => '000000',
        ])->assertUnprocessable()->assertJsonValidationErrors('code');
    }

    public function test_disable_removes_the_login_challenge(): void
    {
        $user = $this->twoFactorUser();
        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/profile/two-factor', ['current_password' => 'secret123'])->assertOk();

        $this->app['auth']->forgetGuards();

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonPath('data.two_factor_required', false)
            ->assertJsonStructure(['data' => ['token']]);
    }

    private function plainUser(): User
    {
        return User::factory()->create([
            'email' => 'budi@example.com',
            'password' => Hash::make('secret123'),
            'status' => 'active',
        ]);
    }

    private function twoFactorUser(): User
    {
        $user = $this->plainUser();
        $secret = app(TotpService::class)->generateSecret();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => ['ABCDE-12345'],
        ])->save();

        return $user->refresh();
    }
}
