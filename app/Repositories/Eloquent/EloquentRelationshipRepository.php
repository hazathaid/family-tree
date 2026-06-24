<?php

namespace App\Repositories\Eloquent;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\MemberRelationship;
use App\Models\User;
use App\Repositories\Contracts\RelationshipRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EloquentRelationshipRepository implements RelationshipRepositoryInterface
{
    public function create(array $attributes): MemberRelationship
    {
        return MemberRelationship::query()->create($attributes)->refresh();
    }

    public function update(MemberRelationship $relationship, array $attributes): MemberRelationship
    {
        $relationship->fill($attributes);
        $relationship->save();

        return $relationship->refresh();
    }

    public function delete(MemberRelationship $relationship): void
    {
        $relationship->delete();
    }

    public function findByUuid(string $uuid): ?MemberRelationship
    {
        return MemberRelationship::query()->where('uuid', $uuid)->first();
    }

    public function paginateForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return MemberRelationship::query()
            ->with(['family', 'sourceMember', 'targetMember'])
            ->whereHas('family.userRoles', fn (Builder $query) => $query->where('user_id', $user->id))
            ->when($filters['family_id'] ?? null, fn (Builder $query, int $familyId) => $query->where('family_id', $familyId))
            ->when($filters['member_id'] ?? null, function (Builder $query, int $memberId): void {
                $query->where(function (Builder $query) use ($memberId): void {
                    $query->where('source_member_id', $memberId)
                        ->orWhere('target_member_id', $memberId);
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    public function existsEdge(
        Family $family,
        int $sourceMemberId,
        int $targetMemberId,
        string $relationshipType,
        ?int $exceptRelationshipId = null
    ): bool {
        $query = MemberRelationship::query()
            ->where('family_id', $family->id)
            ->where('source_member_id', $sourceMemberId)
            ->where('target_member_id', $targetMemberId)
            ->where('relationship_type', $relationshipType);

        if ($exceptRelationshipId !== null) {
            $query->where('id', '!=', $exceptRelationshipId);
        }

        return $query->exists();
    }

    public function biologicalParentForChild(
        Family $family,
        FamilyMember $child,
        string $relationshipType,
        ?int $exceptRelationshipId = null
    ): ?MemberRelationship {
        $query = MemberRelationship::query()
            ->where('family_id', $family->id)
            ->where('target_member_id', $child->id)
            ->where('relationship_type', $relationshipType);

        if ($exceptRelationshipId !== null) {
            $query->where('id', '!=', $exceptRelationshipId);
        }

        return $query->first();
    }

    public function parentEdges(Family $family, ?int $exceptRelationshipId = null): Collection
    {
        /** @var Builder<MemberRelationship> $query */
        $query = MemberRelationship::query()
            ->where('family_id', $family->id)
            ->whereIn('relationship_type', [
                MemberRelationship::TYPE_FATHER,
                MemberRelationship::TYPE_MOTHER,
                MemberRelationship::TYPE_CHILD,
            ]);

        if ($exceptRelationshipId !== null) {
            $query->where('id', '!=', $exceptRelationshipId);
        }

        return $query->get();
    }

    public function graphEdgesForFamily(int $familyId): Collection
    {
        /** @var Collection<int, MemberRelationship> $relationships */
        $relationships = MemberRelationship::query()
            ->select([
                'id',
                'family_id',
                'source_member_id',
                'target_member_id',
                'relationship_type',
            ])
            ->where('family_id', $familyId)
            ->get();

        return $relationships;
    }
}
