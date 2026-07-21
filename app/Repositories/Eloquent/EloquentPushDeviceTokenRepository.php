<?php

namespace App\Repositories\Eloquent;

use App\DTOs\PushDeviceData;
use App\Models\PushDeviceToken;
use App\Models\User;
use App\Repositories\Contracts\PushDeviceTokenRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentPushDeviceTokenRepository implements PushDeviceTokenRepositoryInterface
{
    public function register(User $user, PushDeviceData $data): PushDeviceToken
    {
        $device = PushDeviceToken::withTrashed()->where('token', $data->token)->first() ?? new PushDeviceToken;
        if ($device->trashed()) {
            $device->restore();
        }
        $device->fill(['user_id' => $user->id, 'platform' => $data->platform, 'token' => $data->token, 'is_active' => true, 'last_used_at' => now()]);
        $device->save();

        return $device;
    }

    public function findForUser(User $user, string $uuid): PushDeviceToken
    {
        return PushDeviceToken::query()->where('user_id', $user->id)->where('uuid', $uuid)->firstOrFail();
    }

    public function activeForUser(int $userId): Collection
    {
        return PushDeviceToken::query()->where('user_id', $userId)->where('is_active', true)->get();
    }

    public function deactivate(PushDeviceToken $device): void
    {
        $device->update(['is_active' => false]);
    }
}
