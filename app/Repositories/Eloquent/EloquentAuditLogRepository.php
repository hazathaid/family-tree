<?php

namespace App\Repositories\Eloquent;

use App\DTOs\AuditLogCriteria;
use App\Models\AuditLog;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EloquentAuditLogRepository implements AuditLogRepositoryInterface
{
    public function create(array $attributes): AuditLog
    {
        return AuditLog::query()->create($attributes);
    }

    public function paginate(AuditLogCriteria $criteria): LengthAwarePaginator
    {
        return $this->query($criteria)->paginate($criteria->perPage);
    }

    public function export(AuditLogCriteria $criteria): Collection
    {
        return $this->query($criteria)->limit(10000)->get();
    }

    private function query(AuditLogCriteria $criteria): Builder
    {
        return AuditLog::query()
            ->with('user:id,uuid,name,email')
            ->when($criteria->action, fn (Builder $query, string $action) => $query->where('action', $action))
            ->when($criteria->auditableType, fn (Builder $query, string $type) => $query->where('auditable_type', $type))
            ->when($criteria->dateFrom, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($criteria->dateTo, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->latest();
    }
}
