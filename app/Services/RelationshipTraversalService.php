<?php

namespace App\Services;

use App\Models\FamilyMember;
use App\Models\MemberRelationship;
use App\Repositories\Contracts\RelationshipRepositoryInterface;
use Illuminate\Support\Facades\DB;
use SplQueue;

class RelationshipTraversalService
{
    public function __construct(
        private readonly RelationshipRepositoryInterface $relationships,
        private readonly RelationshipCacheService $cache,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function shortestPath(FamilyMember $source, FamilyMember $target): array
    {
        if ($source->family_id !== $target->family_id) {
            return [];
        }

        if ($source->id === $target->id) {
            return [];
        }

        $cachedPath = $this->cache->getPath($source, $target);

        if ($cachedPath !== null) {
            return $cachedPath;
        }

        $queue = new SplQueue;
        $queue->enqueue($source->id);

        $visited = [$source->id => true];
        $previous = [];

        while (! $queue->isEmpty()) {
            $current = $queue->dequeue();

            foreach ($this->neighborsForMember($source->family_id, $current) as $edge) {
                $next = $edge[0];

                if (isset($visited[$next])) {
                    continue;
                }

                $visited[$next] = true;
                $previous[$next] = [
                    'from_member_id' => $current,
                    'edge' => $edge,
                ];

                if ($next === $target->id) {
                    $path = $this->enrichPath($this->reconstructPath($source->id, $target->id, $previous));
                    $this->cache->putPath($source, $target, $path);

                    return $path;
                }

                $queue->enqueue($next);
            }
        }

        $this->cache->putPath($source, $target, []);

        return [];
    }

    /**
     * @return array<int, array{0: int, 1: string, 2: int, 3: string}>
     */
    private function neighborsForMember(int $familyId, int $memberId): array
    {
        $relationships = [];
        $memberIds = [$memberId];

        foreach ($this->relationships->graphEdgesForMember($familyId, $memberId) as $relationship) {
            $relationships[] = $relationship;
            $memberIds[] = $relationship->source_member_id;
            $memberIds[] = $relationship->target_member_id;
        }

        /** @var array<int, string|null> $memberGenders */
        $memberGenders = DB::table('family_members')
            ->where('family_id', $familyId)
            ->whereIn('id', array_values(array_unique($memberIds)))
            ->whereNull('deleted_at')
            ->pluck('gender', 'id')
            ->all();
        $neighbors = [];

        foreach ($relationships as $relationship) {
            foreach ($this->normalizedEdges($relationship, $memberGenders) as $edge) {
                if ($edge[0] === $memberId) {
                    $neighbors[] = [$edge[1], $edge[2], $edge[3], $edge[4]];
                }
            }
        }

        return $neighbors;
    }

    /**
     * @param  array<int, string|null>  $memberGenders
     * @return array<int, array{0: int, 1: int, 2: string, 3: int, 4: string}>
     */
    private function normalizedEdges(object $relationship, array $memberGenders): array
    {
        if (! array_key_exists($relationship->source_member_id, $memberGenders)
            || ! array_key_exists($relationship->target_member_id, $memberGenders)) {
            return [];
        }

        return match ($relationship->relationship_type) {
            MemberRelationship::TYPE_FATHER, MemberRelationship::TYPE_MOTHER => [
                $this->edge($relationship->target_member_id, $relationship->source_member_id, $relationship->relationship_type, $relationship),
                $this->edge($relationship->source_member_id, $relationship->target_member_id, 'child', $relationship),
            ],
            MemberRelationship::TYPE_CHILD => [
                $this->edge(
                    $relationship->source_member_id,
                    $relationship->target_member_id,
                    $this->parentRelationFor($memberGenders[$relationship->target_member_id]),
                    $relationship
                ),
                $this->edge($relationship->target_member_id, $relationship->source_member_id, 'child', $relationship),
            ],
            MemberRelationship::TYPE_HUSBAND, MemberRelationship::TYPE_WIFE => [
                $this->edge($relationship->source_member_id, $relationship->target_member_id, 'spouse', $relationship),
                $this->edge($relationship->target_member_id, $relationship->source_member_id, 'spouse', $relationship),
            ],
            default => [],
        };
    }

    /**
     * @return array{0: int, 1: int, 2: string, 3: int, 4: string}
     */
    private function edge(
        int $fromMemberId,
        int $toMemberId,
        string $relationship,
        object $sourceRelationship
    ): array {
        return [
            $fromMemberId,
            $toMemberId,
            $relationship,
            $sourceRelationship->id,
            $sourceRelationship->relationship_type,
        ];
    }

    private function parentRelationFor(?string $gender): string
    {
        return match ($gender) {
            'male' => 'father',
            'female' => 'mother',
            default => 'parent',
        };
    }

    /**
     * @param  array<int, array{from_member_id: int, edge: array{0: int, 1: string, 2: int, 3: string}}>  $previous
     * @return array<int, array{0: int, 1: int, 2: string, 3: int, 4: string}>
     */
    private function reconstructPath(int $sourceMemberId, int $targetMemberId, array $previous): array
    {
        $path = [];
        $current = $targetMemberId;

        while ($current !== $sourceMemberId && isset($previous[$current])) {
            array_unshift($path, [
                $previous[$current]['from_member_id'],
                $previous[$current]['edge'][0],
                $previous[$current]['edge'][1],
                $previous[$current]['edge'][2],
                $previous[$current]['edge'][3],
            ]);
            $current = $previous[$current]['from_member_id'];
        }

        return $path;
    }

    /**
     * @param  array<int, array{0: int, 1: int, 2: string, 3: int, 4: string}>  $path
     * @return array<int, array<string, mixed>>
     */
    private function enrichPath(array $path): array
    {
        if ($path === []) {
            return [];
        }

        $memberIds = [];

        foreach ($path as $edge) {
            $memberIds[] = $edge[0];
            $memberIds[] = $edge[1];
        }

        $members = DB::table('family_members')
            ->select(['id', 'uuid', 'full_name'])
            ->whereIn('id', array_values(array_unique($memberIds)))
            ->whereNull('deleted_at')
            ->get()
            ->keyBy('id');

        return array_map(function (array $edge) use ($members): array {
            $from = $members->get($edge[0]);
            $to = $members->get($edge[1]);

            return [
                'from_member_id' => $edge[0],
                'from_member_uuid' => $from?->uuid,
                'from_member_name' => $from?->full_name,
                'to_member_id' => $edge[1],
                'to_member_uuid' => $to?->uuid,
                'to_member_name' => $to?->full_name,
                'relationship' => $edge[2],
                'relationship_id' => $edge[3],
                'relationship_type' => $edge[4],
            ];
        }, $path);
    }
}
