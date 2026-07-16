<?php

namespace Tests\Unit;

use App\Services\TreeLayoutService;
use PHPUnit\Framework\TestCase;

class TreeLayoutServiceTest extends TestCase
{
    public function test_supports_all_required_layouts(): void
    {
        $tree = ['nodes' => [['uuid' => 'a', 'generation' => 0], ['uuid' => 'b', 'generation' => 1]]];
        foreach (['vertical', 'horizontal', 'radial'] as $layout) {
            $result = (new TreeLayoutService)->layout($tree, $layout);
            $this->assertSame($layout, $result['layout']);
            $this->assertArrayHasKey('position', $result['nodes'][0]);
        }
    }
}
