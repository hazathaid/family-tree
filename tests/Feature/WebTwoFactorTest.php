<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WebTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_two_factor_redirects_to_the_challenge(): void
    {
        $this->twoFactorUser();

        $this->post('/login', ['email' => 'budi@example.com', 'password' => 'secret123'])
            ->assertRedirect(route('two-factor.challenge'));

        $this->assertGuest();
        $this->get(route('two-factor.challenge'))->assertOk();
    }

    public function test_a_valid_code_completes_the_web_login(): void
    {
        $user = $this->twoFactorUser();

        $this->post('/login', ['email' => 'budi@example.com', 'password' => 'secret123']);

        $code = app(TotpService::class)->code($user->two_factor_secret);

        $this->post(route('two-factor.challenge.store'), ['code' => $code])
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    public function test_an_invalid_code_keeps_the_user_unauthenticated(): void
    {
        $this->twoFactorUser();

        $this->post('/login', ['email' => 'budi@example.com', 'password' => 'secret123']);

        $this->from(route('two-factor.challenge'))
            ->post(route('two-factor.challenge.store'), ['code' => '000000'])
            ->assertRedirect(route('two-factor.challenge'))
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    private function twoFactorUser(): User
    {
        $user = User::factory()->create([
            'email' => 'budi@example.com',
            'password' => Hash::make('secret123'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $secret = app(TotpService::class)->generateSecret();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => [],
        ])->save();

        return $user->refresh();
    }
}
