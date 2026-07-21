<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\AuditLogCriteria;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\AuditLogIndexRequest;
use App\Http\Resources\AuditLogResource;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLogs) {}

    public function index(AuditLogIndexRequest $request): JsonResponse
    {
        Gate::authorize('administer');
        $criteria = AuditLogCriteria::fromArray($request->validated());

        return response()->json(['success' => true, 'message' => 'Success', 'data' => AuditLogResource::collection($this->auditLogs->paginate($criteria))]);
    }

    public function export(AuditLogIndexRequest $request): StreamedResponse
    {
        Gate::authorize('administer');
        $logs = $this->auditLogs->export(AuditLogCriteria::fromArray($request->validated()));

        return response()->streamDownload(function () use ($logs): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                return;
            }

            fputcsv($output, ['uuid', 'user_email', 'action', 'auditable_type', 'auditable_uuid', 'old_values', 'new_values', 'ip_address', 'created_at']);

            foreach ($logs as $log) {
                fputcsv($output, [
                    $log->uuid,
                    $log->user?->email,
                    $log->action,
                    $log->auditable_type,
                    $log->auditable_uuid,
                    json_encode($log->old_values),
                    json_encode($log->new_values),
                    $log->ip_address,
                    $log->created_at?->toISOString(),
                ]);
            }

            fclose($output);
        }, 'audit-logs.csv', ['Content-Type' => 'text/csv']);
    }
}
