<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\PasswordResetService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PasswordController extends Controller
{
    public function __construct(private readonly PasswordResetService $passwords) {}

    public function forgotForm(): View
    {
        return view('auth.forgot-password');
    }

    public function email(ForgotPasswordRequest $request): RedirectResponse
    {
        return back()->with('status', $this->passwords->sendResetLink($request->validated('email')));
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->string('email')]);
    }

    public function reset(ResetPasswordRequest $request): RedirectResponse
    {
        $this->passwords->reset($request->validated());

        return redirect()->route('login')->with('status', 'Kata sandi berhasil diperbarui.');
    }
}
