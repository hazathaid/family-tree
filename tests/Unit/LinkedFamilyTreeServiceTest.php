<?php

namespace Tests\Unit;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\MemberRelationship;
use App\Models\User;
use App\Services\LinkedFamilyTreeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkedFamilyTreeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_present_redacts_living_members_but_keeps_the_anchor_and_deceased(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $owner->id]);

        $anchor = FamilyMember::factory()->create([
            'family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'female', 'is_alive' => true,
        ]);
        $livingFather = FamilyMember::factory()->create([
            'family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'male', 'is_alive' => true,
            'birth_date' => '1960-01-01',
        ]);
        $deceasedMother = FamilyMember::factory()->deceased()->create([
            'family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'female',
        ]);

        MemberRelationship::factory()->create([
            'family_id' => $family->id,
            'source_member_id' => $livingFather->id,
            'target_member_id' => $anchor->id,
            'relationship_type' => MemberRelationship::TYPE_FATHER,
        ]);
        MemberRelationship::factory()->create([
            'family_id' => $family->id,
            'source_member_id' => $deceasedMother->id,
            'target_member_id' => $anchor->id,
            'relationship_type' => MemberRelationship::TYPE_MOTHER,
        ]);

        $tree = app(LinkedFamilyTreeService::class)->present($anchor, 'full', 2, 'vertical');

        $this->assertTrue($tree['read_only']);
        $this->assertTrue($tree['redacted']);

        $nodes = collect($tree['nodes'])->keyBy('uuid');
        $this->assertFalse($nodes[$anchor->uuid]['is_redacted']);
        $this->assertSame($anchor->full_name, $nodes[$anchor->uuid]['name']);

        $this->assertTrue($nodes[$livingFather->uuid]['is_redacted']);
        $this->assertSame('Anggota Hidup', $nodes[$livingFather->uuid]['name']);
        $this->assertNull($nodes[$livingFather->uuid]['birth_year']);
        $this->assertNull($nodes[$livingFather->uuid]['profile_photo_url']);

        $this->assertFalse($nodes[$deceasedMother->uuid]['is_redacted']);
        $this->assertSame($deceasedMother->full_name, $nodes[$deceasedMother->uuid]['name']);

        foreach ($tree['nodes'] as $node) {
            $this->assertArrayNotHasKey('id', $node);
        }
    }

    public function test_present_removes_marriage_edge_labels_that_touch_redacted_members(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $owner->id]);

        $anchor = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'female', 'is_alive' => true]);
        $livingFormerSpouse = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'male', 'is_alive' => true]);
        MemberRelationship::factory()->create([
            'family_id' => $family->id,
            'source_member_id' => $livingFormerSpouse->id,
            'target_member_id' => $anchor->id,
            'relationship_type' => MemberRelationship::TYPE_HUSBAND,
            'start_date' => '2010-01-01',
            'end_date' => '2024-06-01',
            'end_reason' => MemberRelationship::END_REASON_DIVORCED,
        ]);

        $tree = app(LinkedFamilyTreeService::class)->present($anchor, 'full', 1, 'vertical');

        $spouseEdge = collect($tree['edges'])->first(
            fn (array $edge): bool => in_array($livingFormerSpouse->uuid, [$edge['source_uuid'], $edge['target_uuid']], true)
                && in_array($anchor->uuid, [$edge['source_uuid'], $edge['target_uuid']], true),
        );

        $this->assertNotNull($spouseEdge);
        $this->assertNull($spouseEdge['label']);
        $this->assertFalse($spouseEdge['is_ended']);
    }
}
