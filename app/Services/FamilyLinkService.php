<?php

namespace App\Services;

use App\Models\Family;
use App\Models\FamilyLink;
use App\Models\FamilyMember;
use App\Models\User;
use App\Repositories\Contracts\FamilyLinkRepositoryInterface;
use App\Repositories\Contracts\FamilyMemberRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FamilyLinkService
{
    public const DEFAULT_TTL_DAYS = 30;

    public function __construct(
        private readonly FamilyLinkRepositoryInterface $links,
        private readonly ActivityLogService $activityLog,
        private readonly FamilyMemberRepositoryInterface $members,
        private readonly RelationshipCacheService $relationshipCache,
        private readonly TreeCacheService $treeCache,
    ) {}

    public function request(User $actor, FamilyMember $member, FamilyMember $linkedMember): FamilyLink
    {
        if ($member->family_id === $linkedMember->family_id) {
            throw ValidationException::withMessages([
                'linked_member' => ['Kedua anggota harus berasal dari keluarga yang berbeda.'],
            ]);
        }

        return DB::transaction(function () use ($actor, $member, $linkedMember): FamilyLink {
            if ($this->links->hasOpenLinkForMember($member->id) || $this->links->hasOpenLinkForMember($linkedMember->id)) {
                throw ValidationException::withMessages([
                    'linked_member' => ['Salah satu anggota sudah memiliki tautan yang aktif atau menunggu persetujuan.'],
                ]);
            }

            $link = $this->links->create([
                'family_id' => $member->family_id,
                'linked_family_id' => $linkedMember->family_id,
                'member_id' => $member->id,
                'linked_member_id' => $linkedMember->id,
                'link_type' => FamilyLink::LINK_TYPE_MARRIAGE,
                'status' => FamilyLink::STATUS_PENDING,
                'requested_by' => $actor->id,
                'approved_by_family' => $actor->id,
                'expires_at' => now()->addDays(self::DEFAULT_TTL_DAYS),
            ]);

            $this->activityLog->familyLinkRequested($actor, $link);
            $this->invalidateFamilies($member->family_id, $linkedMember->family_id);

            return $link;
        });
    }

    public function approve(User $actor, FamilyLink $link): FamilyLink
    {
        $link->refresh();

        if (! $link->isPending()) {
            throw ValidationException::withMessages([
                'link' => ['Tautan tidak berada pada status menunggu persetujuan.'],
            ]);
        }

        if ($link->expires_at !== null && $link->expires_at->isPast()) {
            $this->links->update($link, ['status' => FamilyLink::STATUS_EXPIRED]);

            throw ValidationException::withMessages([
                'link' => ['Tautan sudah kedaluwarsa.'],
            ]);
        }

        return DB::transaction(function () use ($actor, $link): FamilyLink {
            $locked = $this->links->findForUpdate($link) ?? $link;

            if (! $locked->isPending()) {
                throw ValidationException::withMessages([
                    'link' => ['Tautan tidak berada pada status menunggu persetujuan.'],
                ]);
            }

            $updated = $this->links->update($locked, [
                'status' => FamilyLink::STATUS_ACTIVE,
                'approved_by_linked_family' => $actor->id,
            ]);

            $this->activityLog->familyLinkApproved($actor, $updated);
            $this->invalidateFamilies($updated->family_id, $updated->linked_family_id);

            return $updated;
        });
    }

    public function revoke(User $actor, FamilyLink $link): FamilyLink
    {
        if (in_array($link->status, [FamilyLink::STATUS_REVOKED, FamilyLink::STATUS_EXPIRED], true)) {
            return $link;
        }

        return DB::transaction(function () use ($actor, $link): FamilyLink {
            $updated = $this->links->update($link, [
                'status' => FamilyLink::STATUS_REVOKED,
                'revoked_at' => now(),
            ]);

            $this->activityLog->familyLinkRevoked($actor, $updated);
            $this->invalidateFamilies($updated->family_id, $updated->linked_family_id);

            return $updated;
        });
    }

    public function find(string $uuid): ?FamilyLink
    {
        return $this->links->findByUuid($uuid);
    }

    public function linksForFamily(Family $family): Collection
    {
        return $this->links->linksForFamily($family->id);
    }

    public function candidateMembers(Family $family): Collection
    {
        return $this->members->candidateMembersForFamily($family);
    }

    public function searchLinkedCandidates(User $user, Family $activeFamily, string $term): Collection
    {
        return $this->members->searchMembersInUserFamilies($user, $activeFamily, $term);
    }

    public function expireDue(): int
    {
        return $this->links->expireDue();
    }

    public function activeLinkBetween(FamilyMember $member, FamilyMember $linkedMember): ?FamilyLink
    {
        return $this->links->activeLinkBetweenMembers($member->id, $linkedMember->id);
    }

    public function activeLinksForMember(FamilyMember $member): Collection
    {
        return $this->links->activeLinksForMember($member->id);
    }

    private function invalidateFamilies(int ...$familyIds): void
    {
        foreach (array_unique($familyIds) as $familyId) {
            $this->relationshipCache->invalidateFamily($familyId);
            $this->treeCache->invalidateFamily($familyId);
        }
    }
}
