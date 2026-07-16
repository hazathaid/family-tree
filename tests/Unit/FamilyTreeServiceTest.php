<?php

namespace Tests\Unit;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\MemberRelationship;
use App\Models\User;
use App\Services\FamilyTreeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyTreeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_configurable_ancestor_and_descendant_trees_and_cache(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $grandfather = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id]);
        $father = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id]);
        $child = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id]);
        foreach ([[$grandfather, $father], [$father, $child]] as [$parent, $descendant]) {
            MemberRelationship::factory()->create(['family_id' => $family->id, 'source_member_id' => $parent->id, 'target_member_id' => $descendant->id, 'relationship_type' => 'father']);
        }
        $service = app(FamilyTreeService::class);
        $this->assertCount(2, $service->generate($child, 'ancestor', 1)['nodes']);
        $this->assertCount(3, $service->generate($child, 'ancestor', 2)['nodes']);
        $this->assertCount(3, $service->generate($grandfather, 'descendant', 2)['nodes']);
        $this->assertTrue($service->generate($child, 'ancestor', 2)['cached']);
    }
}
