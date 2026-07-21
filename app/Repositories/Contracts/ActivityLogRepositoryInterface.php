<?php

namespace App\Repositories\Contracts;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ActivityLogRepositoryInterface
{
    public function create(array $attributes): ActivityLog;

    public function paginateForUser(User $user, array $filters, int $perPage): LengthAwarePaginator;
}
