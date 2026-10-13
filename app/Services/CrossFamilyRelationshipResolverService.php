<?php

namespace App\Services;

use App\Models\FamilyMember;
use App\Models\MemberRelationship;
use App\Repositories\Contracts\FamilyLinkRepositoryInterface;
use App\Repositories\Contracts\RelationshipRepositoryInterface;
use Illuminate\Support\Facades\DB;
use SplQueue;

/**
 * Resolves derived kinship across approved cross-family marriage links.
 *
 * The single-family engine (`RelationshipTraversalService`/`RelationshipResolverService`)
 * remains untouched; this service performs BFS over the combined graph of family-scoped
 * base edges plus active `family_links` rows treated as bidirectional spouse bridges.
 * Only derived labels are produced; no relationship is ever persisted (AGENTS.md rule).
 */
class CrossFamilyRelationshipResolverService
{
    public const MAX_DEPTH = 12;

    public function __construct(
        private readonly RelationshipRepositoryInterface $relationships,
        private readonly FamilyLinkRepositoryInterface $links,
        private readonly RelationshipResolverService $resolver,
    ) {}

    /**
     * @return array{relationship: string|null, path: array<int, array<string, mixed>>, connected: bool}
     */
    public function resolve(FamilyMember $source, FamilyMember $target, int $maxDepth = self::MAX_DEPTH): array
    {
        if ($source->id === $target->id) {
            return ['relationship' => 'Saya', 'path' => [], 'connected' => true];
        }

        $path = $this->shortestPath($source, $target, $maxDepth);

        if ($path === []) {
            return ['relationship' => null, 'path' => [], 'connected' => false];
        }

        return [
            'relationship' => $this->resolver->nameFromPath($source, $target, $path),
            'path' => $path,
            'connected' => true,
        ];
    }

    public function connected(FamilyMember $source, FamilyMember $target, int $maxDepth = self::MAX_DEPTH): bool
    {
        if ($source->id === $target->id) {
            return true;
        }

        return $this->shortestPath($source, $target, $maxDepth) !== [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function shortestPath(FamilyMember $source, FamilyMember $target, int $maxDepth): array
    {
        $queue = new SplQueue;
        $queue->enqueue([$source->id, $source->family_id, 0]);
        $visited = [$source->id => true];
        $previous = [];

        while (! $queue->isEmpty()) {
            /** @var array{0: int, 1: int, 2: int} $current */
            $current = $queue->dequeue();
            [$currentId, $currentFamily, $depth] = $current;

            if ($depth >= $maxDepth) {
                continue;
            }

            foreach ($this->neighbors($currentId, $currentFamily) as $edge) {
                /** @var array{0: int, 1: string, 2: string, 3: int, 4: int} $edge */
                [$toId, $relationship, $relationshipType, $relationshipId, $toFamily] = $edge;

                if (isset($visited[$toId])) {
                    continue;
                }

                $visited[$toId] = true;
                $previous[$toId] = [
                    'from_member_id' => $currentId,
                    'relationship' => $relationship,
                    'relationship_type' => $relationshipType,
                    'relationship_id' => $relationshipId,
                ];

                if ($toId === $target->id) {
                    return $this->enrichPath($this->reconstructPath($source->id, $target->id, $previous));
                }

                $queue->enqueue([$toId, $toFamily, $depth + 1]);
            }
        }

        return [];
    }

    /**
     * @return array<int, array{0: int, 1: string, 2: string, 3: int, 4: int}>
     */
    private function neighbors(int $memberId, int $familyId): array
    {
        $rows = [];
        $memberIds = [$memberId => true];

        foreach ($this->relationships->graphEdgesForMember($familyId, $memberId) as $relationship) {
            $rows[] = $relationship;
            $memberIds[$relationship->source_member_id] = true;
            $memberIds[$relationship->target_member_id] = true;
        }

        /** @var array<int, string|null> $genders */
        $genders = DB::table('family_members')
            ->where('family_id', $familyId)
            ->whereIn('id', array_keys($memberIds))
            ->whereNull('deleted_at')
            ->pluck('gender', 'id')
            ->all();

        $edges = [];

        foreach ($rows as $relationship) {
            $source = (int) $relationship->source_member_id;
            $target = (int) $relationship->target_member_id;

            if (! array_key_exists($source, $genders) || ! array_key_exists($target, $genders)) {
                continue;
            }

            $type = (string) $relationship->relationship_type;
            $id = (int) $relationship->id;

            if (in_array($type, [MemberRelationship::TYPE_FATHER, MemberRelationship::TYPE_MOTHER], true)) {
                if ($source === $memberId) {
                    $edges[] = [$target, 'child', $type, $id, $familyId];
                }
                if ($target === $memberId) {
                    $edges[] = [$source, $type, $type, $id, $familyId];
                }

                continue;
            }

            if ($type === MemberRelationship::TYPE_CHILD) {
                if ($source === $memberId) {
                    $edges[] = [$target, $genders[$target] === 'female' ? 'mother' : 'father', $type, $id, $familyId];
                }
                if ($target === $memberId) {
                    $edges[] = [$source, 'child', $type, $id, $familyId];
                }

                continue;
            }

            if ($source === $memberId) {
                $edges[] = [$target, 'spouse', $type, $id, $familyId];
            }
            if ($target === $memberId) {
                $edges[] = [$source, 'spouse', $type, $id, $familyId];
            }
        }

        foreach ($this->links->activeLinksForMember($memberId) as $link) {
            $isInitiating = $link->member_id === $memberId;
            $edges[] = [
                $isInitiating ? $link->linked_member_id : $link->member_id,
                'spouse',
                $link->link_type,
                $link->id,
                $isInitiating ? $link->linked_family_id : $link->family_id,
            ];
        }

        return $edges;
    }

    /**
     * @param  array<int, array<string, mixed>>  $previous
     * @return array<int, array<string, mixed>>
     */
    private function reconstructPath(int $sourceId, int $targetId, array $previous): array
    {
        $path = [];
        $current = $targetId;

        while ($current !== $sourceId && isset($previous[$current])) {
            array_unshift($path, [
                'from_member_id' => $previous[$current]['from_member_id'],
                'to_member_id' => $current,
                'relationship' => $previous[$current]['relationship'],
                'relationship_type' => $previous[$current]['relationship_type'],
                'relationship_id' => $previous[$current]['relationship_id'],
            ]);
            $current = $previous[$current]['from_member_id'];
        }

        return $path;
    }

    /**
     * @param  array<int, array<string, mixed>>  $path
     * @return array<int, array<string, mixed>>
     */
    private function enrichPath(array $path): array
    {
        if ($path === []) {
            return [];
        }

        $memberIds = [];

        foreach ($path as $edge) {
            $memberIds[] = $edge['from_member_id'];
            $memberIds[] = $edge['to_member_id'];
        }

        $members = DB::table('family_members')
            ->select(['id', 'uuid', 'full_name'])
            ->whereIn('id', array_values(array_unique($memberIds)))
            ->whereNull('deleted_at')
            ->get()
            ->keyBy('id');

        return array_map(function (array $edge) use ($members): array {
            $from = $members->get($edge['from_member_id']);
            $to = $members->get($edge['to_member_id']);

            return [
                'from_member_id' => $edge['from_member_id'],
                'from_member_uuid' => $from?->uuid,
                'from_member_name' => $from?->full_name,
                'to_member_id' => $edge['to_member_id'],
                'to_member_uuid' => $to?->uuid,
                'to_member_name' => $to?->full_name,
                'relationship' => $edge['relationship'],
                'relationship_id' => $edge['relationship_id'],
                'relationship_type' => $edge['relationship_type'],
            ];
        }, $path);
    }
}
