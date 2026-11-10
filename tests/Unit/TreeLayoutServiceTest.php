<?php

namespace Tests\Unit;

use App\Services\TreeLayoutService;
use PHPUnit\Framework\TestCase;

class TreeLayoutServiceTest extends TestCase
{
    public function test_supports_all_required_layouts(): void
    {
        $tree = ['nodes' => [['uuid' => 'a', 'generation' => 0], ['uuid' => 'b', 'generation' => 1]]];
        foreach (['vertical', 'horizontal', 'radial', 'compact'] as $layout) {
            $result = (new TreeLayoutService)->layout($tree, $layout);
            $this->assertSame($layout, $result['layout']);
            $this->assertArrayHasKey('position', $result['nodes'][0]);
        }
    }

    public function test_parents_are_centered_above_their_child_and_spouses_stay_adjacent(): void
    {
        $result = (new TreeLayoutService)->layout($this->coupleWithChild(), 'vertical');
        $positions = $this->positionsByUuid($result);

        $this->assertSame($positions['root']['y'], $positions['spouse']['y']);
        $this->assertSame(220, abs($positions['spouse']['x'] - $positions['root']['x']));
        $this->assertSame($positions['child']['x'], (int) (($positions['root']['x'] + $positions['spouse']['x']) / 2));
        $this->assertLessThan($positions['child']['y'], $positions['root']['y']);
    }

    public function test_horizontal_layout_uses_the_generation_axis_left_to_right(): void
    {
        $result = (new TreeLayoutService)->layout($this->coupleWithChild(), 'horizontal');
        $positions = $this->positionsByUuid($result);

        $this->assertSame($positions['root']['x'], $positions['spouse']['x']);
        $this->assertGreaterThan($positions['root']['x'], $positions['child']['x']);
        $this->assertNotSame($positions['root']['y'], $positions['spouse']['y']);
    }

    public function test_radial_layout_places_same_generation_members_at_distinct_points(): void
    {
        $result = (new TreeLayoutService)->layout($this->coupleWithChild(), 'radial');
        $positions = $this->positionsByUuid($result);

        $this->assertNotSame(
            [$positions['root']['x'], $positions['root']['y']],
            [$positions['spouse']['x'], $positions['spouse']['y']],
        );
    }

    public function test_layout_never_overlaps_nodes(): void
    {
        $result = (new TreeLayoutService)->layout($this->coupleWithChild(), 'vertical');
        $points = array_map(
            fn (array $node): string => $node['position']['x'].':'.$node['position']['y'],
            $result['nodes'],
        );

        $this->assertSame(count($points), count(array_unique($points)));
    }

    private function coupleWithChild(): array
    {
        return [
            'root_member_uuid' => 'child',
            'nodes' => [
                ['uuid' => 'child', 'generation' => 1],
                ['uuid' => 'spouse', 'generation' => 0],
                ['uuid' => 'root', 'generation' => 0],
            ],
            'edges' => [
                ['source_uuid' => 'root', 'target_uuid' => 'child', 'relationship' => 'child'],
                ['source_uuid' => 'root', 'target_uuid' => 'spouse', 'relationship' => 'spouse'],
            ],
        ];
    }

    /**
     * @return array<string, array{x: int, y: int}>
     */
    private function positionsByUuid(array $tree): array
    {
        $positions = [];

        foreach ($tree['nodes'] as $node) {
            $positions[$node['uuid']] = $node['position'];
        }

        return $positions;
    }
}
