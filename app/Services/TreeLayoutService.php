<?php

namespace App\Services;

class TreeLayoutService
{
    private const NODE_HALF_WIDTH = 110;

    private const NODE_HALF_HEIGHT = 70;

    private const MIN_WIDTH = 960;

    private const MIN_HEIGHT = 720;

    public function layout(array $tree, string $layout): array
    {
        $layout = in_array($layout, ['vertical', 'horizontal', 'radial', 'compact'], true) ? $layout : 'vertical';
        $nodes = array_values($tree['nodes'] ?? []);

        if ($nodes === []) {
            return [...$tree, 'layout' => $layout, 'nodes' => [], 'viewport' => [
                'width' => self::MIN_WIDTH,
                'height' => self::MIN_HEIGHT,
            ]];
        }

        $nodeByUuid = [];
        foreach ($nodes as $node) {
            $nodeByUuid[(string) $node['uuid']] = $node;
        }

        [$childrenOf, $spousesOf] = $this->relations($tree['edges'] ?? [], $nodeByUuid);

        $generations = [];
        $generationOf = [];

        foreach ($nodes as $node) {
            $uuid = (string) $node['uuid'];
            $generation = (int) $node['generation'];
            $generations[$generation][] = $uuid;
            $generationOf[$uuid] = $generation;
        }

        ksort($generations);
        $generationKeys = array_keys($generations);

        $ordered = $this->orderGenerations($generationKeys, $generations, $childrenOf, $spousesOf, $generationOf);
        $units = [];

        foreach ($generationKeys as $generation) {
            $units[$generation] = $this->unitsForGeneration($ordered[$generation], $spousesOf, $generationOf, $generation);
        }

        $positions = $layout === 'radial'
            ? $this->radialPositions($generationKeys, $ordered, $generationOf, $tree['root_member_uuid'] ?? null)
            : $this->cartesianPositions(
                $generationKeys,
                $units,
                $childrenOf,
                $layout === 'compact',
                $layout === 'horizontal',
            );

        $positions = $this->normalizePositions($positions);

        $positioned = array_map(function (array $node) use ($positions): array {
            $uuid = (string) $node['uuid'];
            $position = $positions[$uuid] ?? ['x' => self::NODE_HALF_WIDTH, 'y' => self::NODE_HALF_HEIGHT];

            return [...$node, 'position' => [
                'x' => (int) round($position['x']),
                'y' => (int) round($position['y']),
            ]];
        }, $nodes);

        return [...$tree, 'layout' => $layout, 'nodes' => $positioned, 'viewport' => $this->viewport($positioned)];
    }

    /**
     * @return array{0: array<string, array<int, string>>, 1: array<string, array<int, string>>}
     */
    private function relations(array $edges, array $nodeByUuid): array
    {
        $childrenOf = [];
        $spousesOf = [];

        foreach ($edges as $edge) {
            $source = (string) ($edge['source_uuid'] ?? '');
            $target = (string) ($edge['target_uuid'] ?? '');
            $relationship = (string) ($edge['relationship'] ?? '');

            if (! isset($nodeByUuid[$source], $nodeByUuid[$target])) {
                continue;
            }

            if (in_array($relationship, ['father', 'mother', 'parent'], true)) {
                $childrenOf[$target][] = $source;
            } elseif ($relationship === 'child') {
                $childrenOf[$source][] = $target;
            } elseif ($relationship === 'spouse') {
                $spousesOf[$source][] = $target;
                $spousesOf[$target][] = $source;
            }
        }

        foreach ([&$childrenOf, &$spousesOf] as &$map) {
            foreach ($map as $uuid => $related) {
                $map[$uuid] = array_values(array_unique($related));
            }
        }

        return [$childrenOf, $spousesOf];
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function orderGenerations(
        array $generationKeys,
        array $generations,
        array $childrenOf,
        array $spousesOf,
        array $generationOf,
    ): array {
        $ordered = [];
        $placed = [];

        foreach ($generationKeys as $index => $generation) {
            $sequence = [];

            if ($index > 0) {
                foreach ($ordered[$generationKeys[$index - 1]] as $parent) {
                    foreach ($childrenOf[$parent] ?? [] as $child) {
                        if (($generationOf[$child] ?? null) !== $generation) {
                            continue;
                        }

                        $this->appendWithSpouses($sequence, $placed, $child, $spousesOf, $generationOf, $generation);
                    }
                }
            }

            foreach ($generations[$generation] as $uuid) {
                $this->appendWithSpouses($sequence, $placed, $uuid, $spousesOf, $generationOf, $generation);
            }

            $ordered[$generation] = $sequence;
        }

        return $ordered;
    }

    private function appendWithSpouses(
        array &$sequence,
        array &$placed,
        string $uuid,
        array $spousesOf,
        array $generationOf,
        int $generation,
    ): void {
        if (isset($placed[$uuid])) {
            return;
        }

        $placed[$uuid] = true;
        $sequence[] = $uuid;

        foreach ($spousesOf[$uuid] ?? [] as $spouse) {
            if (($generationOf[$spouse] ?? null) === $generation && ! isset($placed[$spouse])) {
                $placed[$spouse] = true;
                $sequence[] = $spouse;
            }
        }
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function unitsForGeneration(array $sequence, array $spousesOf, array $generationOf, int $generation): array
    {
        $present = array_fill_keys($sequence, true);
        $index = array_flip($sequence);
        $seen = [];
        $units = [];

        foreach ($sequence as $uuid) {
            if (isset($seen[$uuid])) {
                continue;
            }

            $members = [];
            $queue = new \SplQueue;
            $queue->enqueue($uuid);
            $seen[$uuid] = true;

            while (! $queue->isEmpty()) {
                $current = $queue->dequeue();
                $members[] = $current;

                foreach ($spousesOf[$current] ?? [] as $spouse) {
                    if (isset($present[$spouse]) && ! isset($seen[$spouse])
                        && ($generationOf[$spouse] ?? null) === $generation) {
                        $seen[$spouse] = true;
                        $queue->enqueue($spouse);
                    }
                }
            }

            usort($members, fn (string $left, string $right): int => $index[$left] <=> $index[$right]);
            $units[] = $members;
        }

        return $units;
    }

    /**
     * @return array<string, array{x: float, y: float}>
     */
    private function cartesianPositions(
        array $generationKeys,
        array $units,
        array $childrenOf,
        bool $compact,
        bool $horizontal,
    ): array {
        $withinSpacing = $horizontal
            ? ($compact ? 150 : 180)
            : ($compact ? 180 : 220);
        $levelSpacing = $horizontal
            ? ($compact ? 210 : 240)
            : ($compact ? 150 : 180);

        $positions = [];
        $positionedGenerations = [];
        $levels = array_flip($generationKeys);

        foreach (array_reverse($generationKeys) as $generation) {
            $generationUnits = $units[$generation];
            $desired = [];

            foreach ($generationUnits as $unitIndex => $members) {
                $childPositions = [];

                foreach ($members as $uuid) {
                    foreach ($childrenOf[$uuid] ?? [] as $child) {
                        if (isset($positionedGenerations[$child])) {
                            $childPositions[$child] = $positionedGenerations[$child]['x'];
                        }
                    }
                }

                $desired[$unitIndex] = $childPositions === []
                    ? null
                    : array_sum($childPositions) / count($childPositions);
            }

            $previousCenter = null;
            $previousHalfWidth = 0.0;

            foreach ($generationUnits as $unitIndex => $members) {
                $halfWidth = count($members) * $withinSpacing / 2;

                if ($desired[$unitIndex] !== null) {
                    $center = $desired[$unitIndex];

                    if ($previousCenter !== null) {
                        $center = max($center, $previousCenter + $previousHalfWidth + $halfWidth);
                    }
                } else {
                    $center = $previousCenter === null
                        ? 120 + $halfWidth
                        : $previousCenter + $previousHalfWidth + $halfWidth;
                }

                foreach ($members as $memberIndex => $uuid) {
                    $offset = ($memberIndex - (count($members) - 1) / 2) * $withinSpacing;
                    $positions[$uuid] = [
                        'x' => $center + $offset,
                        'y' => 80 + $levels[$generation] * $levelSpacing,
                    ];
                    $positionedGenerations[$uuid] = $positions[$uuid];
                }

                $previousCenter = $center;
                $previousHalfWidth = $halfWidth;
            }
        }

        if ($horizontal) {
            foreach ($positions as $uuid => $position) {
                $positions[$uuid] = ['x' => $position['y'], 'y' => $position['x']];
            }
        }

        return $positions;
    }

    /**
     * @return array<string, array{x: float, y: float}>
     */
    private function radialPositions(
        array $generationKeys,
        array $ordered,
        array $generationOf,
        ?string $rootUuid,
    ): array {
        $rootGeneration = $rootUuid !== null && isset($generationOf[$rootUuid])
            ? $generationOf[$rootUuid]
            : 0;

        $positions = [];

        foreach ($generationKeys as $generation) {
            $uuids = $ordered[$generation];
            $count = count($uuids);
            $offset = $generation - $rootGeneration;

            if ($offset === 0 && $count === 1) {
                $positions[$uuids[0]] = ['x' => 0.0, 'y' => 0.0];

                continue;
            }

            $fullCircle = false;

            if ($offset === 0) {
                $radius = 90.0;
                $start = 0.0;
                $end = 2 * M_PI;
                $fullCircle = true;
            } elseif ($offset < 0) {
                $radius = 120.0 + abs($offset) * 170.0;
                $start = M_PI * 1.1;
                $end = M_PI * 1.9;
            } else {
                $radius = 120.0 + $offset * 170.0;
                $start = M_PI * 0.1;
                $end = M_PI * 0.9;
            }

            foreach ($uuids as $index => $uuid) {
                $angle = $count === 1
                    ? ($start + $end) / 2
                    : $start + ($end - $start) * $index / ($fullCircle ? $count : $count - 1);

                $positions[$uuid] = [
                    'x' => $radius * cos($angle),
                    'y' => $radius * sin($angle),
                ];
            }
        }

        return $positions;
    }

    /**
     * @param  array<string, array{x: float, y: float}>  $positions
     * @return array<string, array{x: float, y: float}>
     */
    private function normalizePositions(array $positions): array
    {
        if ($positions === []) {
            return [];
        }

        $minX = $minY = INF;

        foreach ($positions as $position) {
            $minX = min($minX, $position['x'] - self::NODE_HALF_WIDTH);
            $minY = min($minY, $position['y'] - self::NODE_HALF_HEIGHT);
        }

        $shiftX = 120 - $minX;
        $shiftY = 80 - $minY;

        foreach ($positions as $uuid => $position) {
            $positions[$uuid] = ['x' => $position['x'] + $shiftX, 'y' => $position['y'] + $shiftY];
        }

        return $positions;
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array{width: int, height: int}
     */
    private function viewport(array $nodes): array
    {
        $maxX = $maxY = 0.0;

        foreach ($nodes as $node) {
            $maxX = max($maxX, (float) $node['position']['x']);
            $maxY = max($maxY, (float) $node['position']['y']);
        }

        return [
            'width' => (int) max(self::MIN_WIDTH, $maxX + self::NODE_HALF_WIDTH + 120),
            'height' => (int) max(self::MIN_HEIGHT, $maxY + self::NODE_HALF_HEIGHT + 80),
        ];
    }
}
