<?php

namespace App\Services;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\MemberRelationship;
use App\Repositories\Contracts\FamilyMemberRepositoryInterface;
use App\Repositories\Contracts\FamilyRepositoryInterface;
use App\Repositories\Contracts\RelationshipRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RelationshipService
{
    public function __construct(
        private readonly RelationshipRepositoryInterface $relationships,
        private readonly FamilyRepositoryInterface $families,
        private readonly FamilyMemberRepositoryInterface $members,
    ) {}

    public function create(array $data): MemberRelationship
    {
        [$family, $source, $target] = $this->resolveGraphNodes($data);
        $attributes = $this->relationshipAttributes($family, $source, $target, $data);

        $this->validateGraphIntegrity($family, $source, $target, $attributes['relationship_type']);

        return DB::transaction(function () use ($attributes): MemberRelationship {
            $relationship = $this->relationships->create($attributes);
            $this->syncSpouseInverse($relationship);

            return $relationship->load(['family', 'sourceMember', 'targetMember']);
        });
    }

    public function update(MemberRelationship $relationship, array $data): MemberRelationship
    {
        [$family, $source, $target] = $this->resolveGraphNodes($data, $relationship);
        $attributes = $this->relationshipAttributes($family, $source, $target, $data);
        $original = [
            'family' => $relationship->family,
            'source_member_id' => $relationship->source_member_id,
            'target_member_id' => $relationship->target_member_id,
            'relationship_type' => $relationship->relationship_type,
        ];

        $this->validateGraphIntegrity($family, $source, $target, $attributes['relationship_type'], $relationship->id);

        return DB::transaction(function () use ($relationship, $attributes, $original): MemberRelationship {
            $updated = $this->relationships->update($relationship, $attributes);
            $this->deleteSpouseInverse(
                $original['family'],
                $original['source_member_id'],
                $original['target_member_id'],
                $original['relationship_type'],
                $updated->id
            );
            $this->syncSpouseInverse($updated);

            return $updated->load(['family', 'sourceMember', 'targetMember']);
        });
    }

    public function delete(MemberRelationship $relationship): void
    {
        DB::transaction(function () use ($relationship): void {
            $this->deleteSpouseInverse(
                $relationship->family,
                $relationship->source_member_id,
                $relationship->target_member_id,
                $relationship->relationship_type,
                $relationship->id
            );
            $this->relationships->delete($relationship);
        });
    }

    /**
     * @return array{0: Family, 1: FamilyMember, 2: FamilyMember}
     */
    private function resolveGraphNodes(array $data, ?MemberRelationship $relationship = null): array
    {
        $family = $this->resolveFamily($data, $relationship);
        $source = $this->resolveMember($data, 'source_member', $relationship?->sourceMember);
        $target = $this->resolveMember($data, 'target_member', $relationship?->targetMember);

        if ($source->family_id !== $family->id) {
            throw ValidationException::withMessages([
                'source_member_uuid' => ['The selected source member does not belong to this family.'],
            ]);
        }

        if ($target->family_id !== $family->id) {
            throw ValidationException::withMessages([
                'target_member_uuid' => ['The selected target member does not belong to this family.'],
            ]);
        }

        return [$family, $source, $target];
    }

    private function resolveFamily(array $data, ?MemberRelationship $relationship): Family
    {
        if (isset($data['family_uuid'])) {
            $family = $this->families->findByUuid($data['family_uuid']);

            if ($family instanceof Family) {
                return $family;
            }
        }

        if (isset($data['family_id'])) {
            $family = Family::query()->find($data['family_id']);

            if ($family instanceof Family) {
                return $family;
            }
        }

        if ($relationship instanceof MemberRelationship) {
            return $relationship->family;
        }

        throw ValidationException::withMessages([
            'family_uuid' => ['The selected family is invalid.'],
        ]);
    }

    private function resolveMember(array $data, string $key, ?FamilyMember $fallback): FamilyMember
    {
        $uuidKey = $key.'_uuid';
        $idKey = $key.'_id';

        if (isset($data[$uuidKey])) {
            $member = $this->members->findByUuid($data[$uuidKey]);

            if ($member instanceof FamilyMember) {
                return $member;
            }
        }

        if (isset($data[$idKey])) {
            $member = FamilyMember::query()->find($data[$idKey]);

            if ($member instanceof FamilyMember) {
                return $member;
            }
        }

        if ($fallback instanceof FamilyMember) {
            return $fallback;
        }

        throw ValidationException::withMessages([
            $uuidKey => ['The selected '.$key.' is invalid.'],
        ]);
    }

    private function relationshipAttributes(Family $family, FamilyMember $source, FamilyMember $target, array $data): array
    {
        return [
            'family_id' => $family->id,
            'source_member_id' => $source->id,
            'target_member_id' => $target->id,
            'relationship_type' => $data['relationship_type'],
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function validateGraphIntegrity(
        Family $family,
        FamilyMember $source,
        FamilyMember $target,
        string $relationshipType,
        ?int $exceptRelationshipId = null
    ): void {
        if ($source->id === $target->id) {
            throw ValidationException::withMessages([
                'target_member_uuid' => ['A member cannot have a relationship with themselves.'],
            ]);
        }

        if ($this->relationships->existsEdge($family, $source->id, $target->id, $relationshipType, $exceptRelationshipId)) {
            throw ValidationException::withMessages([
                'relationship_type' => ['This relationship already exists.'],
            ]);
        }

        if (in_array($relationshipType, [MemberRelationship::TYPE_FATHER, MemberRelationship::TYPE_MOTHER], true)) {
            $existingParent = $this->relationships->biologicalParentForChild(
                $family,
                $target,
                $relationshipType,
                $exceptRelationshipId
            );

            if ($existingParent instanceof MemberRelationship && $existingParent->source_member_id !== $source->id) {
                $label = $relationshipType === MemberRelationship::TYPE_FATHER ? 'father' : 'mother';

                throw ValidationException::withMessages([
                    'relationship_type' => ['A child can only have one biological '.$label.'.'],
                ]);
            }
        }

        $parentEdge = $this->parentEdge($source->id, $target->id, $relationshipType);

        if ($parentEdge !== null && $this->createsParentCycle($family, $parentEdge[0], $parentEdge[1], $exceptRelationshipId)) {
            throw ValidationException::withMessages([
                'relationship_type' => ['This relationship would create a circular parent relationship.'],
            ]);
        }
    }

    /**
     * @return array{0: int, 1: int}|null
     */
    private function parentEdge(int $sourceMemberId, int $targetMemberId, string $relationshipType): ?array
    {
        return match ($relationshipType) {
            MemberRelationship::TYPE_FATHER, MemberRelationship::TYPE_MOTHER => [$sourceMemberId, $targetMemberId],
            MemberRelationship::TYPE_CHILD => [$targetMemberId, $sourceMemberId],
            default => null,
        };
    }

    private function createsParentCycle(Family $family, int $parentMemberId, int $childMemberId, ?int $exceptRelationshipId): bool
    {
        $childrenByParent = [];

        foreach ($this->relationships->parentEdges($family, $exceptRelationshipId) as $relationship) {
            $edge = $this->parentEdge(
                $relationship->source_member_id,
                $relationship->target_member_id,
                $relationship->relationship_type
            );

            if ($edge === null) {
                continue;
            }

            $childrenByParent[$edge[0]][] = $edge[1];
        }

        $childrenByParent[$parentMemberId][] = $childMemberId;
        $queue = [$childMemberId];
        $visited = [];

        while ($queue !== []) {
            $current = array_shift($queue);

            if ($current === $parentMemberId) {
                return true;
            }

            if (isset($visited[$current])) {
                continue;
            }

            $visited[$current] = true;

            foreach ($childrenByParent[$current] ?? [] as $next) {
                $queue[] = $next;
            }
        }

        return false;
    }

    private function syncSpouseInverse(MemberRelationship $relationship): void
    {
        $inverseType = $this->inverseSpouseType($relationship->relationship_type);

        if ($inverseType === null) {
            return;
        }

        $inverseAttributes = [
            'family_id' => $relationship->family_id,
            'source_member_id' => $relationship->target_member_id,
            'target_member_id' => $relationship->source_member_id,
            'relationship_type' => $inverseType,
            'start_date' => $relationship->start_date,
            'end_date' => $relationship->end_date,
            'notes' => $relationship->notes,
        ];

        $inverse = MemberRelationship::query()
            ->where('family_id', $relationship->family_id)
            ->where('source_member_id', $relationship->target_member_id)
            ->where('target_member_id', $relationship->source_member_id)
            ->whereIn('relationship_type', [MemberRelationship::TYPE_HUSBAND, MemberRelationship::TYPE_WIFE])
            ->where('id', '!=', $relationship->id)
            ->first();

        if ($inverse instanceof MemberRelationship) {
            $this->relationships->update($inverse, $inverseAttributes);

            return;
        }

        $this->relationships->create($inverseAttributes);
    }

    private function deleteSpouseInverse(
        Family $family,
        int $sourceMemberId,
        int $targetMemberId,
        string $relationshipType,
        ?int $exceptRelationshipId = null
    ): void {
        $inverseType = $this->inverseSpouseType($relationshipType);

        if ($inverseType === null) {
            return;
        }

        $inverse = MemberRelationship::query()
            ->where('family_id', $family->id)
            ->where('source_member_id', $targetMemberId)
            ->where('target_member_id', $sourceMemberId)
            ->where('relationship_type', $inverseType)
            ->when($exceptRelationshipId, fn ($query) => $query->where('id', '!=', $exceptRelationshipId))
            ->first();

        if ($inverse instanceof MemberRelationship) {
            $this->relationships->delete($inverse);
        }
    }

    private function inverseSpouseType(string $relationshipType): ?string
    {
        return match ($relationshipType) {
            MemberRelationship::TYPE_HUSBAND => MemberRelationship::TYPE_WIFE,
            MemberRelationship::TYPE_WIFE => MemberRelationship::TYPE_HUSBAND,
            default => null,
        };
    }
}
