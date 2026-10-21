<?php

namespace App\Repositories\Eloquent;

use App\Models\FamilyLink;
use App\Repositories\Contracts\FamilyLinkRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentFamilyLinkRepository implements FamilyLinkRepositoryInterface
{
    public function create(array $attributes): FamilyLink
    {
        return FamilyLink::query()->create($attributes)->load(['family', 'linkedFamily', 'member', 'linkedMember']);
    }

    public function update(FamilyLink $link, array $attributes): FamilyLink
    {
        $link->fill($attributes);
        $link->save();

        return $link->refresh();
    }

    public function findByUuid(string $uuid): ?FamilyLink
    {
        $link = FamilyLink::query()
            ->with(['family', 'linkedFamily', 'member', 'linkedMember', 'childrenHomeFamily'])
            ->where('uuid', $uuid)
            ->first();

        return $link instanceof FamilyLink ? $link : null;
    }

    public function findForUpdate(FamilyLink $link): ?FamilyLink
    {
        /** @var FamilyLink|null $locked */
        $locked = FamilyLink::query()->whereKey($link->getKey())->lockForUpdate()->first();

        return $locked;
    }

    public function hasOpenLinkForMember(int $memberId): bool
    {
        return FamilyLink::query()
            ->whereIn('status', FamilyLink::OPEN_STATUSES)
            ->where(fn ($query) => $query->where('member_id', $memberId)->orWhere('linked_member_id', $memberId))
            ->exists();
    }

    public function activeLinkBetweenMembers(int $memberId, int $linkedMemberId): ?FamilyLink
    {
        $link = FamilyLink::query()
            ->where('status', FamilyLink::STATUS_ACTIVE)
            ->where(function ($query) use ($memberId, $linkedMemberId): void {
                $query
                    ->where(fn ($pair) => $pair->where('member_id', $memberId)->where('linked_member_id', $linkedMemberId))
                    ->orWhere(fn ($pair) => $pair->where('member_id', $linkedMemberId)->where('linked_member_id', $memberId));
            })
            ->first();

        return $link instanceof FamilyLink ? $link : null;
    }

    public function activeLinksForMember(int $memberId): Collection
    {
        return FamilyLink::query()
            ->where('status', FamilyLink::STATUS_ACTIVE)
            ->where(fn ($query) => $query->where('member_id', $memberId)->orWhere('linked_member_id', $memberId))
            ->with(['family', 'linkedFamily', 'member', 'linkedMember'])
            ->get();
    }

    public function linksForFamily(int $familyId): Collection
    {
        return FamilyLink::query()
            ->where(fn ($query) => $query->where('family_id', $familyId)->orWhere('linked_family_id', $familyId))
            ->with(['family', 'linkedFamily', 'member', 'linkedMember', 'childrenHomeFamily'])
            ->latest('id')
            ->get();
    }

    public function expireDue(): int
    {
        return FamilyLink::query()
            ->whereIn('status', FamilyLink::OPEN_STATUSES)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => FamilyLink::STATUS_EXPIRED]);
    }
}
