<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class WebAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_register_and_is_sent_to_verification(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.test',
            'phone' => '08123456789',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticated();
        $user = User::query()->where('email', 'budi@example.test')->firstOrFail();
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_verified_user_can_login_and_logout_with_session_regeneration(): void
    {
        $user = User::factory()->create(['password' => Hash::make('rahasia123')]);

        $this->post('/login', ['email' => $user->email, 'password' => 'rahasia123'])
            ->assertRedirect(route('onboarding.index'));
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_invalid_login_returns_safe_validation_error(): void
    {
        User::factory()->create(['email' => 'budi@example.test']);

        $this->from('/login')->post('/login', [
            'email' => 'budi@example.test',
            'password' => 'salah',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');
    }

    public function test_password_reset_link_and_reset_flow_work(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password-baru',
                'password_confirmation' => 'password-baru',
            ])->assertRedirect(route('login'));

            return true;
        });
        self::assertTrue(Hash::check('password-baru', $user->refresh()->password));
    }

    public function test_user_can_verify_email_from_signed_web_link(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect(route('onboarding.index'));
        self::assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_authentication_forms_render_with_csrf_protected_actions(): void
    {
        $this->get('/login')->assertOk()->assertSee('Lupa kata sandi?')->assertSee('_token', false);
        $this->get('/register')->assertOk()->assertSee('Konfirmasi kata sandi')->assertSee('_token', false);
        $this->get('/forgot-password')->assertOk()->assertSee('Kirim tautan')->assertSee('_token', false);
    }
}
