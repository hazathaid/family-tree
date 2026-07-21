<?php

namespace App\Repositories\Contracts;

interface SystemHealthRepositoryInterface
{
    public function databaseIsAvailable(): bool;

    public function redisIsAvailable(): bool;
}
