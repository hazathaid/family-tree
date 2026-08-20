<?php

namespace Tests\Unit;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\MemberRelationship;
use App\Models\User;
use App\Services\TreeGraphBuilderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TreeGraphBuilderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_maps_parent_child_spouse_and_child_edges(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $father = $this->member($family, 'Ayah', 'male', $user);
        $mother = $this->member($family, 'Ibu', 'female', $user);
        $child = $this->member($family, 'Anak', 'male', $user);
        $husband = $this->member($family, 'Suami', 'male', $user);
        $wife = $this->member($family, 'Istri', 'female', $user);

        $this->edge($family, $father, $child, MemberRelationship::TYPE_FATHER);
        $this->edge($family, $mother, $child, MemberRelationship::TYPE_MOTHER);
        $this->edge($family, $child, $father, MemberRelationship::TYPE_CHILD);
        $this->edge($family, $husband, $wife, MemberRelationship::TYPE_HUSBAND);

        $graph = app(TreeGraphBuilderService::class)->build($family->id);

        $this->assertCount(5, $graph['nodes']);
        $this->assertContains('father', array_column($graph['adjacency'][$child->id], 'relationship'));
        $this->assertContains('mother', array_column($graph['adjacency'][$child->id], 'relationship'));
        $this->assertContains('child', array_column($graph['adjacency'][$father->id], 'relationship'));
        $this->assertContains('spouse', array_column($graph['adjacency'][$husband->id], 'relationship'));
        $this->assertContains('spouse', array_column($graph['adjacency'][$wife->id], 'relationship'));
    }

    public function test_build_skips_relationships_to_deleted_members(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $father = $this->member($family, 'Ayah', 'male', $user);
        $deleted = $this->member($family, 'Terhapus', 'male', $user);
        $this->edge($family, $deleted, $father, MemberRelationship::TYPE_FATHER);
        $deleted->delete();

        $graph = app(TreeGraphBuilderService::class)->build($family->id);

        $this->assertCount(1, $graph['nodes']);
        $this->assertArrayNotHasKey($deleted->id, $graph['adjacency']);
        $this->assertSame([], $graph['adjacency'][$father->id]);
    }

    private function member(Family $family, string $name, ?string $gender, User $user): FamilyMember
    {
        return FamilyMember::factory()->create([
            'family_id' => $family->id,
            'full_name' => $name,
            'gender' => $gender,
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
