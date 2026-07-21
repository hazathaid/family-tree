<?php

namespace Tests\Unit;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\User;
use App\Services\WebTreeViewerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebTreeViewerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_server_side_tree_presentation_and_relationship_labels(): void
    {
        $family = Family::factory()->create();
        $member = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => User::factory()->create()->id]);

        $result = app(WebTreeViewerService::class)->present($family, ['root' => $member->uuid], false);

        $this->assertSame($member->uuid, $result['tree']['root_member_uuid']);
        $this->assertSame('Saya', $result['tree']['nodes'][0]['relationship_label']);
        $this->assertSame('vertical', $result['tree']['layout']);
        $this->assertArrayNotHasKey('id', $result['tree']['nodes'][0]);
    }
}
