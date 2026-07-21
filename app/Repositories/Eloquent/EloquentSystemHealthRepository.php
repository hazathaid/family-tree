<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\SystemHealthRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

class EloquentSystemHealthRepository implements SystemHealthRepositoryInterface
{
    public function databaseIsAvailable(): bool
    {
        try {
            DB::select('SELECT 1');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function redisIsAvailable(): bool
    {
        if (config('cache.default') !== 'redis' && config('queue.default') !== 'redis') {
            return true;
        }

        try {
            Redis::connection()->ping();

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
