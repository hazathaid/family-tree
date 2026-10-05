<?php

namespace App\Services;

use App\Repositories\Contracts\TreeRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class GenerationMapService
{
    private const TTL_MINUTES = 30;

    public function __construct(private readonly TreeRepositoryInterface $tree) {}

    /**
     * Compute the signed generation of every active member relative to a root.
     *
     * BFS mirrors the tree graph builder: parent edges move one generation up (-1),
     * child edges move one generation down (+1) and spouse edges do not change
     * generation. The result is family-scoped and cycle-safe.
     *
     * @return array<int, int> member id => generation relative to root (root = 0)
     */
    public function generationsForFamily(int $familyId, int $rootMemberId): array
    {
        return Cache::remember(
            $this->cacheKey($familyId, $rootMemberId),
            now()->addMinutes(self::TTL_MINUTES),
            fn (): array => $this->compute($familyId, $rootMemberId),
        );
    }

    /**
     * @return array<int, int>
     */
    public function memberIdsAtGeneration(int $familyId, int $rootMemberId, int $generation): array
    {
        $members = $this->generationsForFamily($familyId, $rootMemberId);
        $ids = [];

        foreach ($members as $memberId => $memberGeneration) {
            if ($memberGeneration === $generation) {
                $ids[] = $memberId;
            }
        }

        return $ids;
    }

    public function invalidateFamily(int $familyId): void
    {
        $version = (int) Cache::get($this->versionKey($familyId), 1);
        Cache::forever($this->versionKey($familyId), $version + 1);
    }

    /**
     * @return array<int, int>
     */
    private function compute(int $familyId, int $rootMemberId): array
    {
        $validIds = [];
        $adjacency = [];

        foreach ($this->tree->members($familyId) as $member) {
            $validIds[$member->id] = true;
            $adjacency[$member->id] = [];
        }

        if (! isset($validIds[$rootMemberId])) {
            return [];
        }

        foreach ($this->tree->relationships($familyId) as $relation) {
            $source = $relation->source_member_id;
            $target = $relation->target_member_id;

            if (! isset($validIds[$source], $validIds[$target])) {
                continue;
            }

            if (in_array($relation->relationship_type, ['father', 'mother'], true)) {
                $adjacency[$target][] = [$source, -1];
                $adjacency[$source][] = [$target, 1];
            } elseif ($relation->relationship_type === 'child') {
                $adjacency[$source][] = [$target, -1];
                $adjacency[$target][] = [$source, 1];
            } else {
                $adjacency[$source][] = [$target, 0];
                $adjacency[$target][] = [$source, 0];
            }
        }

        $generations = [$rootMemberId => 0];
        $queue = new \SplQueue;
        $queue->enqueue($rootMemberId);

        while (! $queue->isEmpty()) {
            $current = $queue->dequeue();

            foreach ($adjacency[$current] ?? [] as [$next, $delta]) {
                if (isset($generations[$next])) {
                    continue;
                }

                $generations[$next] = $generations[$current] + $delta;
                $queue->enqueue($next);
            }
        }

        return $generations;
    }

    private function cacheKey(int $familyId, int $rootMemberId): string
    {
        return sprintf('family:%d:generation-map:%d:%d', $familyId, $this->version($familyId), $rootMemberId);
    }

    private function versionKey(int $familyId): string
    {
        return sprintf('family:%d:generation-version', $familyId);
    }

    private function version(int $familyId): int
    {
        return (int) Cache::get($this->versionKey($familyId), 1);
    }
}
