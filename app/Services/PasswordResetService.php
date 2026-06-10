<?php

namespace App\Services;

use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class PasswordResetService
{
    public function sendResetLink(string $email): string
    {
        $status = Password::sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return __($status);
    }

    public function reset(array $data): string
    {
        $status = Password::reset($data, function ($user, string $password): void {
            $user->forceFill([
                'password' => $password,
            ])->save();

            $user->tokens()->delete();
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return __($status);
    }
}
