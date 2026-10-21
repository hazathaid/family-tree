<?php

namespace App\Repositories\Eloquent;

use App\Models\FamilyViewGrant;
use App\Repositories\Contracts\FamilyViewGrantRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentFamilyViewGrantRepository implements FamilyViewGrantRepositoryInterface
{
    public function create(array $attributes): FamilyViewGrant
    {
        return FamilyViewGrant::query()
            ->create($attributes)
            ->load(['family', 'anchorMember', 'grantedBy', 'grantee']);
    }

    public function update(FamilyViewGrant $grant, array $attributes): FamilyViewGrant
    {
        $grant->fill($attributes);
        $grant->save();

        return $grant->refresh()->load(['family', 'anchorMember', 'grantedBy', 'grantee']);
    }

    public function findByTokenHash(string $tokenHash): ?FamilyViewGrant
    {
        $grant = FamilyViewGrant::query()
            ->with(['family', 'anchorMember', 'grantedBy', 'grantee'])
            ->where('token_hash', $tokenHash)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        return $grant instanceof FamilyViewGrant ? $grant : null;
    }

    public function findActiveForFamilyAndUser(int $familyId, int $userId): ?FamilyViewGrant
    {
        $grant = FamilyViewGrant::query()
            ->with(['family', 'anchorMember', 'grantedBy', 'grantee'])
            ->where('family_id', $familyId)
            ->where('grantee_user_id', $userId)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        return $grant instanceof FamilyViewGrant ? $grant : null;
    }

    public function listForFamily(int $familyId): Collection
    {
        return FamilyViewGrant::query()
            ->with(['anchorMember', 'grantee', 'grantedBy'])
            ->where('family_id', $familyId)
            ->latest('id')
            ->get();
    }

    public function revokeOpenFor(int $familyId, string $email, ?int $userId): int
    {
        return FamilyViewGrant::query()
            ->where('family_id', $familyId)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->where(function ($query) use ($email, $userId): void {
                $query->where('invited_email', $email);

                if ($userId !== null) {
                    $query->orWhere('grantee_user_id', $userId);
                }
            })
            ->update(['revoked_at' => now()]);
    }

    public function expireDue(): int
    {
        return FamilyViewGrant::query()
            ->whereNull('revoked_at')
            ->where('expires_at', '<=', now())
            ->count();
    }
}
