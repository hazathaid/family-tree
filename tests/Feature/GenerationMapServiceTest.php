<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\MemberRelationship;
use App\Models\User;
use App\Services\GenerationMapService;
use App\Services\TreeGraphBuilderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SplQueue;
use Tests\TestCase;

class GenerationMapServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_matches_the_authoritative_tree_graph_generations(): void
    {
        [$root, $child, $grandchild, $parent, $spouse] = $this->lineage();

        $map = app(GenerationMapService::class)->generationsForFamily($root->family_id, $root->id);
        $expected = $this->bfsGenerationsFromGraph($root->family_id, $root->id);

        ksort($map);
        ksort($expected);

        $this->assertSame($expected, $map);
        $this->assertSame(0, $map[$root->id]);
        $this->assertSame(1, $map[$child->id]);
        $this->assertSame(2, $map[$grandchild->id]);
        $this->assertSame(-1, $map[$parent->id]);
        $this->assertSame(0, $map[$spouse->id]);
    }

    public function test_it_returns_member_ids_for_a_requested_generation(): void
    {
        [$root, $child, $grandchild] = $this->lineage();

        $ids = app(GenerationMapService::class)->memberIdsAtGeneration($root->family_id, $root->id, 2);

        $this->assertSame([$grandchild->id], $ids);
        $this->assertContains($child->id, app(GenerationMapService::class)->memberIdsAtGeneration($root->family_id, $root->id, 1));
    }

    /**
     * @return array<int, FamilyMember>
     */
    private function lineage(): array
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);

        $root = $this->member($family, 'Root', $user);
        $child = $this->member($family, 'Child', $user);
        $grandchild = $this->member($family, 'Grandchild', $user);
        $parent = $this->member($family, 'Parent', $user);
        $spouse = $this->member($family, 'Spouse', $user);

        $this->edge($family, $child, $root, MemberRelationship::TYPE_CHILD);
        $this->edge($family, $grandchild, $child, MemberRelationship::TYPE_CHILD);
        $this->edge($family, $root, $parent, MemberRelationship::TYPE_CHILD);
        $this->edge($family, $root, $spouse, MemberRelationship::TYPE_HUSBAND);

        return [$root, $child, $grandchild, $parent, $spouse];
    }

    /**
     * @return array<int, int>
     */
    private function bfsGenerationsFromGraph(int $familyId, int $rootMemberId): array
    {
        $graph = app(TreeGraphBuilderService::class)->build($familyId);

        $generations = [$rootMemberId => 0];
        $queue = new SplQueue;
        $queue->enqueue($rootMemberId);

        while (! $queue->isEmpty()) {
            $current = $queue->dequeue();

            foreach ($graph['adjacency'][$current] ?? [] as $edge) {
                if (isset($generations[$edge['to']])) {
                    continue;
                }

                $generations[$edge['to']] = $generations[$current] + $edge['delta'];
                $queue->enqueue($edge['to']);
            }
        }

        return $generations;
    }

    private function member(Family $family, string $name, User $user): FamilyMember
    {
        return FamilyMember::factory()->create([
            'family_id' => $family->id,
            'full_name' => $name,
            'created_by' => $user->id,
        ]);
    }

    private function edge(Family $family, FamilyMember $source, FamilyMember $target, string $type): MemberRelationship
    {
        return MemberRelationship::factory()->create([
            'family_id' => $family->id,
            'source_member_id' => $source->id,
            'target_member_id' => $target->id,
            'relationship_type' => $type,
        ]);
    }
}
