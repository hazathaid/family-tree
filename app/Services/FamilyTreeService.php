<?php

namespace App\Services;

use App\Models\FamilyMember;
use App\Models\MemberRelationship;
use Illuminate\Validation\ValidationException;
use SplQueue;

class FamilyTreeService
{
    public function __construct(private readonly TreeGraphBuilderService $graphs, private readonly TreeCacheService $cache) {}

    public function generate(FamilyMember $root, string $mode, int $depth): array
    {
        if (! in_array($mode, ['ancestor', 'descendant', 'full'], true) || $depth < 1 || $depth > 20) {
            throw ValidationException::withMessages(['mode' => ['Invalid tree mode or depth.']]);
        }
        if ($tree = $this->cache->get($root, $mode, $depth)) {
            return [...$tree, 'cached' => true];
        }
        $graph = $this->graphs->build($root->family_id);
        $queue = new SplQueue;
        $queue->enqueue([$root->id, 0, 0]);
        $visited = [$root->id => true];
        $nodes = $edges = [];
        while (! $queue->isEmpty()) {
            [$id, $distance, $generation] = $queue->dequeue();
            $nodes[$id] = [...$graph['nodes'][$id], 'distance' => $distance, 'generation' => $generation, 'is_root' => $id === $root->id];
            if ($distance >= $depth) {
                continue;
            }
            foreach ($graph['adjacency'][$id] as $edge) {
                if (($mode === 'ancestor' && $edge['delta'] >= 0) || ($mode === 'descendant' && $edge['delta'] <= 0) || isset($visited[$edge['to']])) {
                    continue;
                }
                $visited[$edge['to']] = true;
                $edges[] = [
                    'source_id' => $id,
                    'target_id' => $edge['to'],
                    'relationship' => $edge['relationship'],
                    'end_date' => $edge['end_date'] ?? null,
                    'end_reason' => $edge['end_reason'] ?? null,
                    'is_ended' => $edge['is_ended'] ?? false,
                    'label' => $edge['label'] ?? null,
                ];
                $queue->enqueue([$edge['to'], $distance + 1, $generation + $edge['delta']]);
            }
        }
        if ($mode !== 'full') {
            [$nodes, $edges] = $this->addFormerSpousesWithSharedChildren($graph['adjacency'], $graph['nodes'], $nodes, $edges);
        }
        $edges = $this->addMissingParentEdges($graph['adjacency'], array_keys($nodes), $edges);
        $ids = array_column($nodes, 'uuid', 'id');
        $edges = array_map(fn (array $edge): array => [
            'source_uuid' => $ids[$edge['source_id']],
            'target_uuid' => $ids[$edge['target_id']],
            'relationship' => $edge['relationship'],
            'end_date' => $edge['end_date'] ?? null,
            'end_reason' => $edge['end_reason'] ?? null,
            'is_ended' => $edge['is_ended'] ?? false,
            'label' => $edge['label'] ?? null,
        ], $edges);
        $generations = array_column($nodes, 'generation');
        $tree = ['root_member_uuid' => $root->uuid, 'mode' => $mode, 'depth' => $depth, 'nodes' => array_values($nodes), 'edges' => $edges,
            'statistics' => ['members' => count($nodes), 'generations' => max($generations) - min($generations) + 1,
                'living' => count(array_filter($nodes, fn (array $node): bool => $node['is_alive'])),
                'deceased' => count(array_filter($nodes, fn (array $node): bool => ! $node['is_alive']))], 'cached' => false];
        $this->cache->put($root, $mode, $depth, $tree);

        return $tree;
    }

    /**
     * Keep a divorced spouse in ancestor/descendant trees when the couple has
     * a shared child. Full trees traverse spouse edges already, so this only
     * adds the lateral context that directional modes would otherwise drop.
     *
     * @param  array<int, array<int, array<string, mixed>>>  $adjacency
     * @param  array<int, array<string, mixed>>  $graphNodes
     * @param  array<int, array<string, mixed>>  $nodes
     * @param  array<int, array<string, mixed>>  $edges
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     */
    private function addFormerSpousesWithSharedChildren(
        array $adjacency,
        array $graphNodes,
        array $nodes,
        array $edges,
    ): array {
        $childrenOf = [];

        foreach ($adjacency as $parentId => $neighbours) {
            foreach ($neighbours as $edge) {
                if (($edge['relationship'] ?? null) === 'child') {
                    $childrenOf[$parentId][] = $edge['to'];
                }
            }
        }

        $visited = array_fill_keys(array_keys($nodes), true);

        foreach (array_keys($nodes) as $id) {
            foreach ($adjacency[$id] ?? [] as $edge) {
                if (($edge['relationship'] ?? null) !== 'spouse'
                    || ! ($edge['is_ended'] ?? false)
                    || ($edge['end_reason'] ?? null) !== MemberRelationship::END_REASON_DIVORCED) {
                    continue;
                }

                $spouseId = $edge['to'];

                if (isset($visited[$spouseId])
                    || array_intersect($childrenOf[$id] ?? [], $childrenOf[$spouseId] ?? []) === []) {
                    continue;
                }

                $anchor = $nodes[$id];
                $visited[$spouseId] = true;
                $nodes[$spouseId] = [
                    ...$graphNodes[$spouseId],
                    'distance' => $anchor['distance'],
                    'generation' => $anchor['generation'],
                    'is_root' => false,
                ];
                $edges[] = [
                    'source_id' => $id,
                    'target_id' => $spouseId,
                    'relationship' => 'spouse',
                    'end_date' => $edge['end_date'] ?? null,
                    'end_reason' => $edge['end_reason'] ?? null,
                    'is_ended' => true,
                    'label' => $edge['label'] ?? null,
                ];
            }
        }

        return [$nodes, $edges];
    }

    /**
     * Add the second biological parent link when both parents are already in
     * the visited tree. This keeps polygamous households groupable per wife
     * without changing the set of members returned by the traversal.
     *
     * @param  array<int, array<int, array<string, mixed>>>  $adjacency
     * @param  array<int, int>  $visitedIds
     * @param  array<int, array{source_id: int, target_id: int, relationship: string}>  $edges
     * @return array<int, array{source_id: int, target_id: int, relationship: string}>
     */
    private function addMissingParentEdges(array $adjacency, array $visitedIds, array $edges): array
    {
        $visited = array_fill_keys($visitedIds, true);
        $connected = [];

        foreach ($edges as $edge) {
            if (in_array($edge['relationship'], ['father', 'mother'], true)) {
                $connected[$edge['source_id'].':'.$edge['target_id']] = true;
            } elseif ($edge['relationship'] === 'child') {
                $connected[$edge['target_id'].':'.$edge['source_id']] = true;
            }
        }

        foreach ($visitedIds as $childId) {
            foreach ($adjacency[$childId] ?? [] as $edge) {
                if (! in_array($edge['relationship'], ['father', 'mother'], true)) {
                    continue;
                }

                $parentId = $edge['to'];

                if (! isset($visited[$parentId])) {
                    continue;
                }

                $key = $childId.':'.$parentId;

                if (isset($connected[$key])) {
                    continue;
                }

                $edges[] = ['source_id' => $parentId, 'target_id' => $childId, 'relationship' => 'child'];
                $connected[$key] = true;
            }
        }

        return $edges;
    }
}
