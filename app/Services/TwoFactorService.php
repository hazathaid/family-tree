<?php

namespace App\Services;

use App\Models\User;

class TwoFactorService
{
    private const RECOVERY_CODE_COUNT = 8;

    public function __construct(private readonly TotpService $totp) {}

    public function isEnabled(User $user): bool
    {
        return $user->two_factor_confirmed_at !== null && $user->two_factor_secret !== null;
    }

    /**
     * @return array{secret: string, otpauth_url: string}
     */
    public function enable(User $user): array
    {
        $secret = $this->totp->generateSecret();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return [
            'secret' => $secret,
            'otpauth_url' => $this->totp->provisioningUri($secret, $user->email, (string) config('app.name')),
        ];
    }

    /**
     * @return array<int, string>|null Confirmed recovery codes, or null on an invalid code.
     */
    public function confirm(User $user, string $code): ?array
    {
        $secret = $user->two_factor_secret;

        if ($secret === null || ! $this->totp->verify($secret, $code)) {
            return null;
        }

        $recoveryCodes = $this->generateRecoveryCodes();
        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $recoveryCodes,
        ])->save();

        return $recoveryCodes;
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    /**
     * @return array<int, string>
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        $codes = $this->generateRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $codes])->save();

        return $codes;
    }

    /**
     * @return array<int, string>
     */
    public function recoveryCodes(User $user): array
    {
        return $user->two_factor_recovery_codes ?? [];
    }

    public function verifyCode(User $user, ?string $code, ?string $recoveryCode = null): bool
    {
        if (is_string($recoveryCode) && $recoveryCode !== '') {
            return $this->consumeRecoveryCode($user, $recoveryCode);
        }

        if (! is_string($code) || $user->two_factor_secret === null) {
            return false;
        }

        return $this->totp->verify($user->two_factor_secret, $code);
    }

    /**
     * @return array<int, string>
     */
    private function generateRecoveryCodes(): array
    {
        $codes = [];

        for ($i = 0; $i < self::RECOVERY_CODE_COUNT; $i++) {
            $raw = strtoupper(bin2hex(random_bytes(5)));
            $codes[] = substr($raw, 0, 5).'-'.substr($raw, 5, 5);
        }

        return $codes;
    }

    private function consumeRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->two_factor_recovery_codes ?? [];
        $normalized = strtoupper(trim($code));

        foreach ($codes as $index => $candidate) {
            if (hash_equals(strtoupper((string) $candidate), $normalized)) {
                unset($codes[$index]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                return true;
            }
        }

        return false;
    }
}
