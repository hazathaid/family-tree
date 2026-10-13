<?php

namespace App\Repositories\Contracts;

use App\Models\FamilyLink;
use Illuminate\Support\Collection;

interface FamilyLinkRepositoryInterface
{
    public function create(array $attributes): FamilyLink;

    public function update(FamilyLink $link, array $attributes): FamilyLink;

    public function findByUuid(string $uuid): ?FamilyLink;

    public function findForUpdate(FamilyLink $link): ?FamilyLink;

    public function hasOpenLinkForMember(int $memberId): bool;

    public function activeLinkBetweenMembers(int $memberId, int $linkedMemberId): ?FamilyLink;

    public function activeLinksForMember(int $memberId): Collection;

    public function linksForFamily(int $familyId): Collection;

    public function expireDue(): int;
}
