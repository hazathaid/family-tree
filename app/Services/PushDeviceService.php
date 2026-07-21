<?php

namespace App\Services;

use App\DTOs\PushDeviceData;
use App\Models\PushDeviceToken;
use App\Models\User;
use App\Repositories\Contracts\PushDeviceTokenRepositoryInterface;

class PushDeviceService
{
    public function __construct(private readonly PushDeviceTokenRepositoryInterface $devices) {}

    public function register(User $user, PushDeviceData $data): PushDeviceToken
    {
        return $this->devices->register($user, $data);
    }

    public function remove(PushDeviceToken $device): void
    {
        $device->delete();
    }
}
