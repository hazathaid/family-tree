<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\LogoutRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use App\Services\TwoFactorService;
use App\Services\WebOnboardingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly TwoFactorService $twoFactor,
        private readonly WebOnboardingService $onboarding,
    ) {}

    public function loginForm(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $user = $this->authService->webCredentialsUser($request->only('email', 'password'));

        if ($this->twoFactor->isEnabled($user)) {
            $request->session()->put('two_factor.user_id', $user->id);
            $request->session()->put('two_factor.remember', $request->boolean('remember'));

            return redirect()->route('two-factor.challenge');
        }

        $this->authService->startWebSession($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended($user->hasVerifiedEmail() ? $this->onboarding->destinationFor($user) : route('verification.notice'));
    }

    public function registerForm(): View
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = $this->authService->register($request->validated());
        $this->authService->startWebSession($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice');
    }

    public function logout(LogoutRequest $request): RedirectResponse
    {
        $this->authService->logoutWeb($request);

        return redirect()->route('login')->with('status', 'Anda telah keluar.');
    }
}
