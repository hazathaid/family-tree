<?php

namespace App\Repositories\Eloquent;

use App\Models\ActivityLog;
use App\Models\User;
use App\Repositories\Contracts\ActivityLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentActivityLogRepository implements ActivityLogRepositoryInterface
{
    public function create(array $attributes): ActivityLog
    {
        return ActivityLog::query()->create($attributes);
    }

    public function paginateForUser(User $user, array $filters, int $perPage): LengthAwarePaginator
    {
        return ActivityLog::query()
            ->with(['family', 'user'])
            ->whereHas('family.userRoles', fn ($query) => $query->where('user_id', $user->id))
            ->when($filters['family_uuid'] ?? null, fn ($query, $uuid) => $query->whereHas('family', fn ($family) => $family->where('uuid', $uuid)))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->whereIn('activity_type', ActivityLog::FILTERS[$type]))
            ->latest()
            ->paginate($perPage);
    }
}
