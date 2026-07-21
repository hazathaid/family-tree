<?php

namespace App\Services;

class TreeLayoutService
{
    public function layout(array $tree, string $layout): array
    {
        $groups = [];
        foreach ($tree['nodes'] as $node) {
            $groups[$node['generation']][] = $node;
        }
        ksort($groups);
        $max = max(array_map('count', $groups));
        $width = max(960, $max * 220 + 120);
        $height = max(720, count($groups) * 180 + 120);
        $positioned = [];
        foreach ($groups as $generation => $nodes) {
            foreach ($nodes as $index => $node) {
                if ($layout === 'radial') {
                    $radius = abs($generation) * 180;
                    $angle = count($nodes) === 1 ? 0 : 2 * M_PI * $index / count($nodes);
                    [$x, $y] = [$width / 2 + $radius * cos($angle), $height / 2 + $radius * sin($angle)];
                } else {
                    $level = array_search($generation, array_keys($groups), true);
                    $spacingX = $layout === 'compact' ? 170 : 220;
                    $spacingY = $layout === 'compact' ? 140 : 180;
                    $x = 120 + $index * $spacingX;
                    $y = 80 + $level * $spacingY;
                    if ($layout === 'horizontal') {
                        [$x, $y] = [$y, $x];
                    }
                }
                $positioned[] = [...$node, 'position' => ['x' => (int) round($x), 'y' => (int) round($y)]];
            }
        }

        return [...$tree, 'layout' => $layout, 'nodes' => $positioned, 'viewport' => ['width' => $width, 'height' => $height]];
    }
}
