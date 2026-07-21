<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
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
        $user = $this->users->findByEmail($credentials['email']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['This account is not active.'],
            ]);
        }

        $this->users->update($user, ['last_login_at' => now()]);

        return [
            'token' => $user->createToken($deviceName)->plainTextToken,
            'user' => $user->refresh(),
        ];
    }

    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token === null) {
            throw new AuthenticationException;
        }

        $token->delete();
    }

    public function loginWeb(array $credentials, bool $remember = false): User
    {
        $user = $this->users->findByEmail($credentials['email']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau kata sandi tidak sesuai.'],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Akun ini tidak aktif.'],
            ]);
        }

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

    public function startWebSession(User $user): void
    {
        $this->webGuard()->login($user);
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
