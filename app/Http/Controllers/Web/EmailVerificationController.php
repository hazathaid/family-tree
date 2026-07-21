<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\EmailVerificationService;
use App\Services\WebOnboardingService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function __construct(
        private readonly EmailVerificationService $verification,
        private readonly WebOnboardingService $onboarding,
    ) {}

    public function notice(): View
    {
        return view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $this->verification->verify($request);

        return redirect($this->onboarding->destinationFor($request->user()))->with('status', 'Email berhasil diverifikasi.');
    }

    public function send(Request $request): RedirectResponse
    {
        $sent = $this->verification->send($request->user());

        return back()->with('status', $sent ? 'Tautan verifikasi telah dikirim.' : 'Email sudah diverifikasi.');
    }
}
