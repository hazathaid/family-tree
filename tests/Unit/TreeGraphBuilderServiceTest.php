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

    public function test_build_exposes_spouse_labels_and_ended_metadata(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $husband = $this->member($family, 'Suami', 'male', $user);
        $divorcedWife = $this->member($family, 'Mantan Istri', 'female', $user);
        $widowedWife = $this->member($family, 'Istri Wafat', 'female', $user);
        $legacyWife = $this->member($family, 'Istri Lama', 'female', $user);

        $this->edge($family, $husband, $divorcedWife, MemberRelationship::TYPE_HUSBAND, [
            'start_date' => '2010-01-01',
            'end_date' => '2024-06-01',
            'end_reason' => MemberRelationship::END_REASON_DIVORCED,
        ]);
        $this->edge($family, $husband, $widowedWife, MemberRelationship::TYPE_HUSBAND, [
            'start_date' => '2000-01-01',
            'end_date' => '2020-01-01',
            'end_reason' => MemberRelationship::END_REASON_WIDOWED,
        ]);
        $this->edge($family, $husband, $legacyWife, MemberRelationship::TYPE_HUSBAND, [
            'start_date' => '1990-01-01',
            'end_date' => '1995-01-01',
            'end_reason' => null,
        ]);

        $graph = app(TreeGraphBuilderService::class)->build($family->id);
        $husbandEdges = collect($graph['adjacency'][$husband->id])->keyBy('to');

        $this->assertSame('Mantan Istri', $husbandEdges[$divorcedWife->id]['label']);
        $this->assertTrue($husbandEdges[$divorcedWife->id]['is_ended']);
        $this->assertSame('Istri', $husbandEdges[$widowedWife->id]['label']);
        $this->assertTrue($husbandEdges[$widowedWife->id]['is_ended']);
        $this->assertSame('Mantan Istri', $husbandEdges[$legacyWife->id]['label']);
        $this->assertTrue($husbandEdges[$legacyWife->id]['is_ended']);
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

    private function edge(Family $family, FamilyMember $source, FamilyMember $target, string $type, array $attributes = []): MemberRelationship
    {
        return MemberRelationship::factory()->create([
            'family_id' => $family->id,
            'source_member_id' => $source->id,
            'target_member_id' => $target->id,
            'relationship_type' => $type,
            ...$attributes,
        ]);
    }
}
