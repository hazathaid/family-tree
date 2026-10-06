<?php

namespace App\Services;

use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly RefreshTokenService $refreshTokens,
        private readonly TwoFactorService $twoFactor,
    ) {}

    public function register(array $data): User
    {
        $user = $this->users->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'status' => 'active',
        ]);

        event(new Registered($user));

        return $user;
    }

    public function login(array $credentials, string $deviceName = 'api'): array
    {
        $user = $this->credentialsUser($credentials);
        $this->users->update($user, ['last_login_at' => now()]);

        if ($this->twoFactor->isEnabled($user)) {
            return [
                'two_factor_required' => true,
                'challenge_token' => $this->challengeToken($user, $deviceName),
                'user' => $user->refresh(),
            ];
        }

        return ['two_factor_required' => false] + $this->issueTokens($user, $deviceName);
    }

    public function credentialsUser(array $credentials): User
    {
        $user = $this->users->findByEmail((string) ($credentials['email'] ?? ''));

        if (! $user instanceof User || ! Hash::check((string) ($credentials['password'] ?? ''), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['This account is not active.'],
            ]);
        }

        return $user;
    }

    public function webCredentialsUser(array $credentials): User
    {
        $user = $this->users->findByEmail((string) ($credentials['email'] ?? ''));

        if (! $user instanceof User || ! Hash::check((string) ($credentials['password'] ?? ''), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau kata sandi tidak sesuai.'],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Akun ini tidak aktif.'],
            ]);
        }

        return $user;
    }

    public function twoFactorChallenge(string $challengeToken, ?string $code, ?string $recoveryCode = null): array
    {
        [$user, $device] = $this->resolveChallenge($challengeToken);

        if (! $this->twoFactor->isEnabled($user) || $user->status !== 'active') {
            throw new AuthenticationException;
        }

        if (! $this->twoFactor->verifyCode($user, $code, $recoveryCode)) {
            throw ValidationException::withMessages([
                'code' => ['The provided two-factor code was invalid.'],
            ]);
        }

        $this->users->update($user, ['last_login_at' => now()]);

        return $this->issueTokens($user, $device);
    }

    public function refresh(string $refreshToken, string $deviceName = 'api'): array
    {
        $record = $this->refreshTokens->findValid($refreshToken);
        $user = $record->user;

        if (! $user instanceof User || $user->status !== 'active') {
            throw new AuthenticationException;
        }

        return DB::transaction(function () use ($record, $user, $deviceName): array {
            $previousAccessTokenId = $record->access_token_id;
            $this->refreshTokens->revoke($record);

            if ($previousAccessTokenId !== null) {
                PersonalAccessToken::query()->whereKey($previousAccessTokenId)->delete();
            }

            $accessToken = $user->createToken($deviceName);

            return [
                'token' => $accessToken->plainTextToken,
                'refresh_token' => $this->refreshTokens->issue($user, $accessToken->accessToken->getKey(), $deviceName),
                'user' => $user->refresh(),
            ];
        });
    }

    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token === null) {
            throw new AuthenticationException;
        }

        if ($token instanceof PersonalAccessToken) {
            $this->refreshTokens->revokeForAccessToken($token->getKey());
        }

        $token->delete();
    }

    public function loginWeb(array $credentials, bool $remember = false): User
    {
        $user = $this->webCredentialsUser($credentials);
        $this->webGuard()->login($user, $remember);
        $this->users->update($user, ['last_login_at' => now()]);

        return $user->refresh();
    }

    public function logoutWeb(Request $request): void
    {
        $this->webGuard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    public function startWebSession(User $user, bool $remember = false): void
    {
        $this->webGuard()->login($user, $remember);
        $this->users->update($user, ['last_login_at' => now()]);
    }

    /**
     * @return array{token: string, refresh_token: string, user: User}
     */
    private function issueTokens(User $user, string $deviceName): array
    {
        $accessToken = $user->createToken($deviceName);

        return [
            'token' => $accessToken->plainTextToken,
            'refresh_token' => $this->refreshTokens->issue($user, $accessToken->accessToken->getKey(), $deviceName),
            'user' => $user->refresh(),
        ];
    }

    private function challengeToken(User $user, string $deviceName): string
    {
        return Crypt::encryptString((string) json_encode([
            'user_id' => $user->id,
            'device' => $deviceName,
            'exp' => now()->addMinutes(10)->timestamp,
        ]));
    }

    /**
     * @return array{0: User, 1: string}
     */
    private function resolveChallenge(string $token): array
    {
        try {
            $decoded = Crypt::decryptString($token);
        } catch (\Throwable) {
            throw new AuthenticationException;
        }

        $data = json_decode($decoded, true);

        if (! is_array($data) || ! isset($data['user_id'], $data['exp']) || (int) $data['exp'] < now()->timestamp) {
            throw new AuthenticationException;
        }

        $user = User::query()->find($data['user_id']);

        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        return [$user, is_string($data['device'] ?? null) ? $data['device'] : 'api'];
    }

    private function webGuard(): StatefulGuard
    {
        $guard = Auth::guard('web');

        if (! $guard instanceof StatefulGuard) {
            throw new AuthenticationException;
        }

        return $guard;
    }
}
