<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthService;
use App\Services\TwoFactorService;
use App\Services\WebOnboardingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TwoFactorChallengeController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly TwoFactorService $twoFactor,
        private readonly WebOnboardingService $onboarding,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('two_factor.user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $userId = $request->session()->get('two_factor.user_id');
        $user = is_numeric($userId) ? User::query()->find((int) $userId) : null;

        if (! $user instanceof User || ! $this->twoFactor->isEnabled($user)) {
            return redirect()->route('login');
        }

        if (! $this->twoFactor->verifyCode($user, $request->input('code'), $request->input('recovery_code'))) {
            return back()->withErrors(['code' => 'Kode dua faktor tidak valid.']);
        }

        $remember = (bool) $request->session()->pull('two_factor.remember', false);
        $request->session()->forget('two_factor.user_id');
        $this->authService->startWebSession($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended($user->hasVerifiedEmail() ? $this->onboarding->destinationFor($user) : route('verification.notice'));
    }
}
