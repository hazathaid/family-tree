<?php

namespace App\Services;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Str;

class RefreshTokenService
{
    public function issue(User $user, ?int $accessTokenId, ?string $deviceName): string
    {
        $plain = Str::random(64);

        RefreshToken::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'access_token_id' => $accessTokenId,
            'token_hash' => $this->hash($plain),
            'device_name' => $deviceName,
            'expires_at' => now()->addMinutes((int) config('sanctum.refresh_token_ttl', 43200)),
        ]);

        return $plain;
    }

    public function findValid(string $plain): RefreshToken
    {
        $record = RefreshToken::query()
            ->where('token_hash', $this->hash($plain))
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $record instanceof RefreshToken) {
            throw new AuthenticationException;
        }

        return $record;
    }

    public function revoke(RefreshToken $record): void
    {
        if ($record->revoked_at === null) {
            $record->update(['revoked_at' => now()]);
        }
    }

    public function revokeForAccessToken(int $accessTokenId): void
    {
        RefreshToken::query()
            ->where('access_token_id', $accessTokenId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    private function hash(string $plain): string
    {
        return hash('sha256', $plain);
    }
}
