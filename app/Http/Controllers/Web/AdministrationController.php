<?php

namespace App\Http\Controllers\Web;

use App\DTOs\AuditLogCriteria;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\AdminIndexRequest;
use App\Http\Requests\Administration\AuditLogIndexRequest;
use App\Http\Requests\Administration\UpdateUserStatusRequest;
use App\Http\Requests\Administration\WebRemoveFamilyContentRequest;
use App\Models\Family;
use App\Models\User;
use App\Services\AdministrationService;
use App\Services\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdministrationController extends Controller
{
    public function __construct(
        private readonly AdministrationService $administration,
        private readonly AuditLogService $auditLogs,
    ) {}

    public function dashboard(): View
    {
        return view('admin.dashboard', ['statistics' => $this->administration->dashboard()]);
    }

    public function users(AdminIndexRequest $request): View
    {
        return view('admin.users', ['users' => $this->administration->users($request->integer('per_page', 15))]);
    }

    public function updateUser(UpdateUserStatusRequest $request, User $user): RedirectResponse
    {
        $this->administration->updateUserStatus($request->user(), $user, $request->validated('status'));

        return back()->with('success', 'Status pengguna berhasil diperbarui.');
    }

    public function families(AdminIndexRequest $request): View
    {
        return view('admin.families', ['families' => $this->administration->families($request->integer('per_page', 15))]);
    }

    public function family(Family $family): View
    {
        return view('admin.family', ['family' => $this->administration->family($family)]);
    }

    public function removeContent(WebRemoveFamilyContentRequest $request, Family $family): RedirectResponse
    {
        $this->administration->removeContent(
            $request->user(),
            $family,
            $request->validated('content_type'),
            $request->validated('content_uuid'),
        );

        return back()->with('success', 'Konten berhasil dihapus dan tindakan telah dicatat.');
    }

    public function auditLogs(AuditLogIndexRequest $request): View
    {
        $criteria = AuditLogCriteria::fromArray($request->validated());

        return view('admin.audit-logs', ['logs' => $this->auditLogs->paginate($criteria)]);
    }

    public function exportAuditLogs(AuditLogIndexRequest $request): StreamedResponse
    {
        $logs = $this->auditLogs->export(AuditLogCriteria::fromArray($request->validated()));

        return response()->streamDownload(function () use ($logs): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                return;
            }

            fputcsv($output, ['uuid', 'user_email', 'action', 'auditable_type', 'auditable_uuid', 'created_at']);
            foreach ($logs as $log) {
                fputcsv($output, [$log->uuid, $log->user?->email, $log->action, $log->auditable_type, $log->auditable_uuid, $log->created_at?->toISOString()]);
            }
            fclose($output);
        }, 'audit-logs.csv', ['Content-Type' => 'text/csv']);
    }
}
