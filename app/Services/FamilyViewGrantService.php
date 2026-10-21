<?php

namespace App\Services;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\FamilyViewGrant;
use App\Models\User;
use App\Notifications\FamilyViewGrantNotification;
use App\Repositories\Contracts\FamilyUserRoleRepositoryInterface;
use App\Repositories\Contracts\FamilyViewGrantRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FamilyViewGrantService
{
    public const DEFAULT_TTL_DAYS = 30;

    public const MAX_TTL_DAYS = 90;

    public function __construct(
        private readonly FamilyViewGrantRepositoryInterface $grants,
        private readonly FamilyUserRoleRepositoryInterface $roles,
        private readonly ActivityLogService $activityLog,
    ) {}

    /**
     * @return array{grant: FamilyViewGrant, plain_token: string}
     */
    public function create(User $actor, Family $family, FamilyMember $anchor, string $email, int $days = self::DEFAULT_TTL_DAYS): array
    {
        $this->authorizeManager($actor, $family);

        if ($anchor->family_id !== $family->id) {
            throw ValidationException::withMessages(['anchor_member_uuid' => ['Anggota acuan harus berasal dari keluarga ini.']]);
        }

        $email = Str::lower(trim($email));
        $days = max(1, min(self::MAX_TTL_DAYS, $days));
        $grantee = User::query()->where('email', $email)->first();
        $plainToken = Str::random(64);

        $grant = DB::transaction(function () use ($actor, $family, $anchor, $email, $days, $grantee, $plainToken): FamilyViewGrant {
            $this->grants->revokeOpenFor($family->id, $email, $grantee?->id);

            return $this->grants->create([
                'family_id' => $family->id,
                'anchor_member_id' => $anchor->id,
                'granted_by' => $actor->id,
                'grantee_user_id' => $grantee?->id,
                'invited_email' => $email,
                'token_hash' => hash('sha256', $plainToken),
                'can_view_living' => false,
                'expires_at' => now()->addDays($days),
            ]);
        });

        $this->activityLog->familyViewGrantCreated($actor, $grant);

        if ($grantee instanceof User) {
            $grantee->notify(new FamilyViewGrantNotification($grant));
        } else {
            Notification::route('mail', $email)->notify(new FamilyViewGrantNotification($grant, $plainToken));
        }

        return ['grant' => $grant, 'plain_token' => $plainToken];
    }

    public function accept(User $user, string $plainToken): FamilyViewGrant
    {
        $grant = $this->grants->findByTokenHash(hash('sha256', $plainToken));

        if (! $grant instanceof FamilyViewGrant) {
            throw ValidationException::withMessages(['token' => ['Tautan akses tidak valid atau sudah kedaluwarsa.']]);
        }

        if ($grant->grantee_user_id !== null && $grant->grantee_user_id !== $user->id) {
            throw ValidationException::withMessages(['token' => ['Tautan akses ini bukan untuk akun Anda.']]);
        }

        if ($grant->invited_email !== null && strcasecmp($grant->invited_email, $user->email) !== 0) {
            throw ValidationException::withMessages(['token' => ['Tautan akses ini bukan untuk email Anda.']]);
        }

        $accepted = $this->grants->update($grant, ['grantee_user_id' => $user->id]);
        $this->activityLog->familyViewGrantAccepted($user, $accepted);

        return $accepted;
    }

    public function revoke(User $actor, FamilyViewGrant $grant): FamilyViewGrant
    {
        $this->authorizeManager($actor, $grant->family);

        if ($grant->isRevoked()) {
            return $grant;
        }

        $revoked = $this->grants->update($grant, ['revoked_at' => now()]);
        $this->activityLog->familyViewGrantRevoked($actor, $revoked);

        return $revoked;
    }

    public function authorize(User $user, Family $family): FamilyViewGrant
    {
        $grant = $this->grants->findActiveForFamilyAndUser($family->id, $user->id);

        if (! $grant instanceof FamilyViewGrant) {
            throw new AuthorizationException('Anda tidak memiliki akses baca-saja ke keluarga ini.');
        }

        return $grant;
    }

    /**
     * @return Collection<int, FamilyViewGrant>
     */
    public function listForFamily(User $actor, Family $family): Collection
    {
        $this->authorizeManager($actor, $family);

        return $this->grants->listForFamily($family->id);
    }

    public function markAccessed(User $user, FamilyViewGrant $grant): void
    {
        if (! $grant->isActive() || $grant->grantee_user_id !== $user->id) {
            throw new AuthorizationException('Akses baca-saja tidak aktif.');
        }

        $shouldLog = $grant->last_accessed_at === null
            || $grant->last_accessed_at->lt(now()->subMinutes(5));

        $this->grants->update($grant, ['last_accessed_at' => now()]);

        if ($shouldLog) {
            $this->activityLog->familyViewAccessed($user, $grant);
        }
    }

    public function expireDue(): int
    {
        return $this->grants->expireDue();
    }

    private function authorizeManager(User $user, Family $family): void
    {
        $role = $this->roles->findActive($family, $user)?->role;

        if (! in_array($role, [FamilyUserRole::ROLE_OWNER, FamilyUserRole::ROLE_ADMIN], true)) {
            throw new AuthorizationException('Hanya owner atau admin keluarga yang dapat mengelola akses tamu.');
        }
    }
}
