<?php

namespace Tests\Unit;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\MemberRelationship;
use App\Models\User;
use App\Services\FamilyTreeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
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

    public function test_generates_polygamous_trees_with_each_wife_children(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $husband = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id, 'gender' => 'male']);
        $firstWife = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id, 'gender' => 'female']);
        $secondWife = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id, 'gender' => 'female']);
        $firstChild = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id, 'gender' => 'male']);
        $secondChild = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id, 'gender' => 'female']);

        foreach ([[$husband, $firstWife, 'husband'], [$firstWife, $husband, 'wife'], [$husband, $secondWife, 'husband'], [$secondWife, $husband, 'wife']] as [$source, $target, $type]) {
            MemberRelationship::factory()->create([
                'family_id' => $family->id,
                'source_member_id' => $source->id,
                'target_member_id' => $target->id,
                'relationship_type' => $type,
            ]);
        }

        foreach ([[$husband, $firstChild, 'father'], [$firstWife, $firstChild, 'mother'], [$husband, $secondChild, 'father'], [$secondWife, $secondChild, 'mother']] as [$source, $target, $type]) {
            MemberRelationship::factory()->create([
                'family_id' => $family->id,
                'source_member_id' => $source->id,
                'target_member_id' => $target->id,
                'relationship_type' => $type,
            ]);
        }

        $service = app(FamilyTreeService::class);
        $full = $service->generate($husband, 'full', 1);

        $this->assertCount(5, $full['nodes']);
        $this->assertCount(5, array_unique(array_column($full['nodes'], 'uuid')));
        $this->assertTrue($this->hasParentEdge($full['edges'], $husband->uuid, $firstChild->uuid));
        $this->assertTrue($this->hasParentEdge($full['edges'], $firstWife->uuid, $firstChild->uuid));
        $this->assertTrue($this->hasParentEdge($full['edges'], $husband->uuid, $secondChild->uuid));
        $this->assertTrue($this->hasParentEdge($full['edges'], $secondWife->uuid, $secondChild->uuid));

        $firstWifeTree = $service->generate($firstWife, 'descendant', 1);
        $this->assertCount(2, $firstWifeTree['nodes']);
        $this->assertContains($firstChild->uuid, array_column($firstWifeTree['nodes'], 'uuid'));
        $this->assertNotContains($secondChild->uuid, array_column($firstWifeTree['nodes'], 'uuid'));

        $secondWifeTree = $service->generate($secondWife, 'descendant', 1);
        $this->assertCount(2, $secondWifeTree['nodes']);
        $this->assertContains($secondChild->uuid, array_column($secondWifeTree['nodes'], 'uuid'));

        $descendants = $service->generate($husband, 'descendant', 1);
        $this->assertCount(3, $descendants['nodes']);
        $this->assertContains($firstChild->uuid, array_column($descendants['nodes'], 'uuid'));
        $this->assertContains($secondChild->uuid, array_column($descendants['nodes'], 'uuid'));
    }

    public function test_directional_trees_keep_a_divorced_spouse_with_a_shared_child(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $father = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id, 'gender' => 'male']);
        $mother = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id, 'gender' => 'female']);
        $child = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id]);

        MemberRelationship::factory()->create([
            'family_id' => $family->id,
            'source_member_id' => $father->id,
            'target_member_id' => $mother->id,
            'relationship_type' => MemberRelationship::TYPE_HUSBAND,
            'start_date' => '2010-01-01',
            'end_date' => '2024-06-01',
            'end_reason' => MemberRelationship::END_REASON_DIVORCED,
        ]);
        foreach ([[$father, 'father'], [$mother, 'mother']] as [$parent, $type]) {
            MemberRelationship::factory()->create([
                'family_id' => $family->id,
                'source_member_id' => $parent->id,
                'target_member_id' => $child->id,
                'relationship_type' => $type,
            ]);
        }

        $tree = app(FamilyTreeService::class)->generate($father, 'descendant', 1);
        $this->assertContains($mother->uuid, array_column($tree['nodes'], 'uuid'));
        $this->assertTrue(collect($tree['edges'])->contains(
            fn (array $edge): bool => $edge['source_uuid'] === $father->uuid
                && $edge['target_uuid'] === $mother->uuid
                && $edge['label'] === 'Mantan Istri'
                && $edge['is_ended'] === true,
        ));
    }

    public function test_directional_trees_do_not_add_a_divorced_spouse_without_shared_children(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $father = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id, 'gender' => 'male']);
        $formerWife = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id, 'gender' => 'female']);
        $child = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id]);

        MemberRelationship::factory()->create([
            'family_id' => $family->id,
            'source_member_id' => $father->id,
            'target_member_id' => $formerWife->id,
            'relationship_type' => MemberRelationship::TYPE_HUSBAND,
            'start_date' => '2010-01-01',
            'end_date' => '2024-06-01',
            'end_reason' => MemberRelationship::END_REASON_DIVORCED,
        ]);
        MemberRelationship::factory()->create([
            'family_id' => $family->id,
            'source_member_id' => $father->id,
            'target_member_id' => $child->id,
            'relationship_type' => MemberRelationship::TYPE_FATHER,
        ]);

        $tree = app(FamilyTreeService::class)->generate($father, 'descendant', 1);

        $this->assertNotContains($formerWife->uuid, array_column($tree['nodes'], 'uuid'));
    }

    public function test_rejects_invalid_mode_and_depth(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $member = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id]);

        $this->expectException(ValidationException::class);

        app(FamilyTreeService::class)->generate($member, 'invalid', 1);
    }

    private function hasParentEdge(array $edges, string $parentUuid, string $childUuid): bool
    {
        foreach ($edges as $edge) {
            if ($edge['relationship'] === 'child'
                && $edge['source_uuid'] === $parentUuid
                && $edge['target_uuid'] === $childUuid) {
                return true;
            }

            if (in_array($edge['relationship'], ['father', 'mother'], true)
                && $edge['source_uuid'] === $childUuid
                && $edge['target_uuid'] === $parentUuid) {
                return true;
            }
        }

        return false;
    }
}
