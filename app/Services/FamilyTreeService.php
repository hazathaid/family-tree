<?php

namespace App\Services;

use App\Models\FamilyMember;
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
            $nodes[$id] = [...$graph['nodes'][$id], 'generation' => $generation, 'is_root' => $id === $root->id];
            if ($distance >= $depth) {
                continue;
            }
            foreach ($graph['adjacency'][$id] as $edge) {
                if (($mode === 'ancestor' && $edge['delta'] >= 0) || ($mode === 'descendant' && $edge['delta'] <= 0) || isset($visited[$edge['to']])) {
                    continue;
                }
                $visited[$edge['to']] = true;
                $edges[] = ['source_id' => $id, 'target_id' => $edge['to'], 'relationship' => $edge['relationship']];
                $queue->enqueue([$edge['to'], $distance + 1, $generation + $edge['delta']]);
            }
        }
        $ids = array_column($nodes, 'uuid', 'id');
        $edges = array_map(fn (array $edge): array => ['source_uuid' => $ids[$edge['source_id']], 'target_uuid' => $ids[$edge['target_id']], 'relationship' => $edge['relationship']], $edges);
        $generations = array_column($nodes, 'generation');
        $tree = ['root_member_uuid' => $root->uuid, 'mode' => $mode, 'depth' => $depth, 'nodes' => array_values($nodes), 'edges' => $edges,
            'statistics' => ['members' => count($nodes), 'generations' => max($generations) - min($generations) + 1,
                'living' => count(array_filter($nodes, fn (array $node): bool => $node['is_alive'])),
                'deceased' => count(array_filter($nodes, fn (array $node): bool => ! $node['is_alive']))], 'cached' => false];
        $this->cache->put($root, $mode, $depth, $tree);

        return $tree;
    }
}
