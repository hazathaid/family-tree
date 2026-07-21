<?php

namespace App\Services;

use App\Models\Family;
use App\Models\User;
use App\Repositories\Contracts\AdministrationRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class AdministrationService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly AdministrationRepositoryInterface $administration,
        private readonly AuditLogService $auditLogs,
    ) {}

    public function users(int $perPage): LengthAwarePaginator
    {
        return $this->users->paginate($perPage);
    }

    public function updateUserStatus(User $actor, User $user, string $status): User
    {
        if ($actor->is($user) && $status === 'suspended') {
            throw ValidationException::withMessages(['status' => ['You cannot suspend your own account.']]);
        }

        $oldStatus = $user->status;
        $updated = $this->users->update($user, ['status' => $status]);

        if ($status === 'suspended') {
            $user->tokens()->delete();
        }

        $this->auditLogs->record($actor, 'user.'.$status, $updated, ['status' => $oldStatus], ['status' => $status]);

        return $updated;
    }

    public function families(int $perPage): LengthAwarePaginator
    {
        return $this->administration->paginateFamilies($perPage);
    }

    public function removeContent(User $actor, Family $family, string $type, string $uuid): Model
    {
        $content = $this->administration->findFamilyContent($family, $type, $uuid);

        if (! $content) {
            throw ValidationException::withMessages(['content_uuid' => ['The selected content does not belong to this family.']]);
        }

        $this->administration->deleteContent($content);
        $this->auditLogs->record($actor, 'content.removed', $content, [], [
            'family_uuid' => $family->uuid,
            'content_type' => $type,
        ]);

        return $content;
    }
}
