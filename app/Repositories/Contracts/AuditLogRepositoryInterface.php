<?php

namespace App\Repositories\Contracts;

use App\DTOs\AuditLogCriteria;
use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AuditLogRepositoryInterface
{
    public function create(array $attributes): AuditLog;

    public function paginate(AuditLogCriteria $criteria): LengthAwarePaginator;

    public function export(AuditLogCriteria $criteria): Collection;
}
