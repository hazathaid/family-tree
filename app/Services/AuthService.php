<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Events\Registered;
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
}
