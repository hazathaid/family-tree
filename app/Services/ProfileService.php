<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function update(User $user, array $data): User
    {
        return $this->users->update($user, [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'email_verified_at' => $user->email === $data['email'] ? $user->email_verified_at : null,
        ]);
    }

    public function changePassword(User $user, string $password): User
    {
        $updated = $this->users->update($user, [
            'password' => Hash::make($password),
        ]);

        $updated->tokens()->delete();

        return $updated;
    }

    public function uploadAvatar(User $user, UploadedFile $avatar): User
    {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $avatar->store('avatars', 'public');

        return $this->users->update($user, [
            'avatar' => $path,
        ]);
    }

    public function updateNotificationPreferences(User $user, array $preferences): User
    {
        return $this->users->update($user, ['notification_preferences' => $preferences]);
    }

    public function activeSessions(User $user, string $currentSessionId): Collection
    {
        return $this->users->activeSessions($user)->map(function (object $session) use ($currentSessionId): object {
            $session->is_current = hash_equals($currentSessionId, (string) $session->id);
            $session->last_active_at = now()->setTimestamp((int) $session->last_activity);

            return $session;
        });
    }
}
