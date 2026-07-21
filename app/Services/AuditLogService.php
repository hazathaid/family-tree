<?php

namespace App\Services;

use App\DTOs\AuditLogCriteria;
use App\Models\AuditLog;
use App\Models\User;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AuditLogService
{
    public function __construct(private readonly AuditLogRepositoryInterface $auditLogs) {}

    public function record(User $actor, string $action, Model $subject, array $oldValues = [], array $newValues = []): AuditLog
    {
        return $this->auditLogs->create([
            'user_id' => $actor->id,
            'action' => $action,
            'auditable_type' => $subject->getMorphClass(),
            'auditable_id' => $subject->getKey(),
            'auditable_uuid' => $subject->getAttribute('uuid'),
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function paginate(AuditLogCriteria $criteria): LengthAwarePaginator
    {
        return $this->auditLogs->paginate($criteria);
    }

    public function export(AuditLogCriteria $criteria): Collection
    {
        return $this->auditLogs->export($criteria);
    }
}
