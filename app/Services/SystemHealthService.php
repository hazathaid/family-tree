<?php

namespace App\Services;

use App\DTOs\SystemHealthData;
use App\Repositories\Contracts\SystemHealthRepositoryInterface;

class SystemHealthService
{
    public function __construct(private readonly SystemHealthRepositoryInterface $repository) {}

    public function status(): SystemHealthData
    {
        $checks = [
            'database' => $this->repository->databaseIsAvailable() ? 'ok' : 'unavailable',
            'redis' => $this->repository->redisIsAvailable() ? 'ok' : 'unavailable',
        ];

        return new SystemHealthData(
            status: in_array('unavailable', $checks, true) ? 'degraded' : 'ok',
            checks: $checks,
        );
    }
}
