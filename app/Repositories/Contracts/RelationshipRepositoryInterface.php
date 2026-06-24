<?php

namespace App\Repositories\Contracts;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\MemberRelationship;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface RelationshipRepositoryInterface
{
    public function create(array $attributes): MemberRelationship;

    public function update(MemberRelationship $relationship, array $attributes): MemberRelationship;

    public function delete(MemberRelationship $relationship): void;

    public function findByUuid(string $uuid): ?MemberRelationship;

    public function paginateForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function existsEdge(
        Family $family,
        int $sourceMemberId,
        int $targetMemberId,
        string $relationshipType,
        ?int $exceptRelationshipId = null
    ): bool;

    public function biologicalParentForChild(
        Family $family,
        FamilyMember $child,
        string $relationshipType,
        ?int $exceptRelationshipId = null
    ): ?MemberRelationship;

    /**
     * @return Collection<int, MemberRelationship>
     */
    public function parentEdges(Family $family, ?int $exceptRelationshipId = null): Collection;

    /**
     * @return iterable<int, object>
     */
    public function graphEdgesForFamily(int $familyId): iterable;

    /**
     * @return iterable<int, object>
     */
    public function graphEdgesForMember(int $familyId, int $memberId): iterable;
}
