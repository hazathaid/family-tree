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

    public function test_polygamous_layout_places_cowives_around_shared_spouse_and_children_under_each_mother(): void
    {
        $result = (new TreeLayoutService)->layout($this->polygamousFamily(), 'vertical');
        $positions = $this->positionsByUuid($result);

        $this->assertSame($positions['firstWife']['y'], $positions['husband']['y']);
        $this->assertSame($positions['secondWife']['y'], $positions['husband']['y']);
        $this->assertTrue($positions['firstWife']['x'] < $positions['husband']['x']);
        $this->assertTrue($positions['husband']['x'] < $positions['secondWife']['x']);
        $this->assertSame((int) (($positions['firstWife']['x'] + $positions['husband']['x']) / 2), $positions['firstChild']['x']);
        $this->assertSame((int) (($positions['husband']['x'] + $positions['secondWife']['x']) / 2), $positions['secondChild']['x']);

        $points = array_map(
            fn (array $node): string => $node['position']['x'].':'.$node['position']['y'],
            $result['nodes'],
        );

        $this->assertSame(count($points), count(array_unique($points)));
    }

    private function polygamousFamily(): array
    {
        return [
            'root_member_uuid' => 'husband',
            'nodes' => [
                ['uuid' => 'husband', 'generation' => 0, 'gender' => 'male'],
                ['uuid' => 'firstWife', 'generation' => 0, 'gender' => 'female'],
                ['uuid' => 'secondWife', 'generation' => 0, 'gender' => 'female'],
                ['uuid' => 'firstChild', 'generation' => 1, 'gender' => 'male'],
                ['uuid' => 'secondChild', 'generation' => 1, 'gender' => 'female'],
            ],
            'edges' => [
                ['source_uuid' => 'husband', 'target_uuid' => 'firstWife', 'relationship' => 'spouse'],
                ['source_uuid' => 'husband', 'target_uuid' => 'secondWife', 'relationship' => 'spouse'],
                ['source_uuid' => 'husband', 'target_uuid' => 'firstChild', 'relationship' => 'child'],
                ['source_uuid' => 'husband', 'target_uuid' => 'secondChild', 'relationship' => 'child'],
                ['source_uuid' => 'firstWife', 'target_uuid' => 'firstChild', 'relationship' => 'child'],
                ['source_uuid' => 'secondWife', 'target_uuid' => 'secondChild', 'relationship' => 'child'],
            ],
        ];
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
