<?php

namespace App\Repositories\Contracts;

use App\DTOs\PushDeviceData;
use App\Models\PushDeviceToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface PushDeviceTokenRepositoryInterface
{
    public function register(User $user, PushDeviceData $data): PushDeviceToken;

    public function findForUser(User $user, string $uuid): PushDeviceToken;

    public function activeForUser(int $userId): Collection;

    public function deactivate(PushDeviceToken $device): void;
}
