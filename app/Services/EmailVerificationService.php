<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

class EmailVerificationService
{
    public function send(User $user): bool
    {
        if ($user->hasVerifiedEmail()) {
            return false;
        }

        $user->sendEmailVerificationNotification();

        return true;
    }

    public function verify(EmailVerificationRequest $request): bool
    {
        if ($request->user()->hasVerifiedEmail()) {
            return false;
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));

            return true;
        }

        return false;
    }
}
