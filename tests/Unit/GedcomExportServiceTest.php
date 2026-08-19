<?php

namespace Tests\Unit;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\MemberRelationship;
use App\Repositories\Eloquent\EloquentFamilyMemberRepository;
use App\Repositories\Eloquent\EloquentRelationshipRepository;
use App\Services\GedcomExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GedcomExportServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): GedcomExportService
    {
        return new GedcomExportService(
            new EloquentFamilyMemberRepository,
            new EloquentRelationshipRepository,
        );
    }

    public function test_it_produces_a_gedcom_551_document_envelope(): void
    {
        $family = Family::factory()->create(['name' => 'Keluarga Ahmad']);
        FamilyMember::factory()->create(['family_id' => $family->id]);

        $content = $this->service()->export($family);

        $this->assertStringStartsWith("0 HEAD\r\n", $content);
        $this->assertStringContainsString('1 GEDC', $content);
        $this->assertStringContainsString('2 VERS 5.5.1', $content);
        $this->assertStringContainsString('2 FORM LINEAGE-LINKED', $content);
        $this->assertStringContainsString('1 CHAR UTF-8', $content);
        $this->assertStringContainsString('0 TRLR', $content);
        $this->assertStringEndsWith("0 TRLR\r\n", $content);
    }

    public function test_it_emits_individual_records_with_life_details(): void
    {
        $family = Family::factory()->create();
        $member = FamilyMember::factory()->create([
            'family_id' => $family->id,
            'full_name' => 'Hasan bin Ahmad',
            'gender' => 'male',
            'birth_date' => '2001-05-12',
            'birth_place' => 'Bandung',
            'is_alive' => false,
            'death_date' => '2020-03-01',
            'death_place' => 'Jakarta',
        ]);

        $content = $this->service()->export($family);

        $this->assertStringContainsString('0 @I'.$member->id.'@ INDI', $content);
        $this->assertStringContainsString('1 NAME Hasan bin Ahmad', $content);
        $this->assertStringContainsString('1 SEX M', $content);
        $this->assertStringContainsString('1 BIRT', $content);
        $this->assertStringContainsString('2 DATE 12 MAY 2001', $content);
        $this->assertStringContainsString('2 PLAC Bandung', $content);
        $this->assertStringContainsString('1 DEAT', $content);
        $this->assertStringContainsString('2 DATE 1 MAR 2020', $content);
        $this->assertStringContainsString('2 PLAC Jakarta', $content);
    }

    public function test_female_members_are_exported_with_female_sex(): void
    {
        $family = Family::factory()->create();
        $member = FamilyMember::factory()->create([
            'family_id' => $family->id,
            'gender' => 'female',
        ]);

        $content = $this->service()->export($family);

        $this->assertStringContainsString('1 SEX F', $content);
        $this->assertStringContainsString('0 @I'.$member->id.'@ INDI', $content);
    }

    public function test_parent_child_relationships_map_to_family_records(): void
    {
        $family = Family::factory()->create();
        $father = FamilyMember::factory()->create(['family_id' => $family->id, 'gender' => 'male']);
        $mother = FamilyMember::factory()->create(['family_id' => $family->id, 'gender' => 'female']);
        $child = FamilyMember::factory()->create(['family_id' => $family->id, 'gender' => 'male']);

        MemberRelationship::factory()->create(['family_id' => $family->id, 'source_member_id' => $father->id, 'target_member_id' => $child->id, 'relationship_type' => MemberRelationship::TYPE_FATHER]);
        MemberRelationship::factory()->create(['family_id' => $family->id, 'source_member_id' => $mother->id, 'target_member_id' => $child->id, 'relationship_type' => MemberRelationship::TYPE_MOTHER]);

        $content = $this->service()->export($family);

        $this->assertStringContainsString('1 HUSB @I'.$father->id.'@', $content);
        $this->assertStringContainsString('1 WIFE @I'.$mother->id.'@', $content);
        $this->assertStringContainsString('1 CHIL @I'.$child->id.'@', $content);
        $this->assertStringContainsString('1 FAMS @F1@', $content);
        $this->assertStringContainsString('1 FAMC @F1@', $content);
    }

    public function test_spouse_relationships_without_children_create_family_record(): void
    {
        $family = Family::factory()->create();
        $husband = FamilyMember::factory()->create(['family_id' => $family->id, 'gender' => 'male']);
        $wife = FamilyMember::factory()->create(['family_id' => $family->id, 'gender' => 'female']);

        MemberRelationship::factory()->create(['family_id' => $family->id, 'source_member_id' => $husband->id, 'target_member_id' => $wife->id, 'relationship_type' => MemberRelationship::TYPE_HUSBAND]);

        $content = $this->service()->export($family);

        $this->assertStringContainsString('1 HUSB @I'.$husband->id.'@', $content);
        $this->assertStringContainsString('1 WIFE @I'.$wife->id.'@', $content);
        $this->assertStringContainsString('1 FAMS @F1@', $content);
    }

    public function test_child_type_edges_are_mapped_as_parent_child_relationships(): void
    {
        $family = Family::factory()->create();
        $father = FamilyMember::factory()->create(['family_id' => $family->id, 'gender' => 'male']);
        $child = FamilyMember::factory()->create(['family_id' => $family->id, 'gender' => 'male']);

        MemberRelationship::factory()->create(['family_id' => $family->id, 'source_member_id' => $child->id, 'target_member_id' => $father->id, 'relationship_type' => MemberRelationship::TYPE_CHILD]);

        $content = $this->service()->export($family);

        $this->assertStringContainsString('1 HUSB @I'.$father->id.'@', $content);
        $this->assertStringContainsString('1 CHIL @I'.$child->id.'@', $content);
    }

    public function test_gedcom_at_signs_are_escaped(): void
    {
        $family = Family::factory()->create();
        FamilyMember::factory()->create([
            'family_id' => $family->id,
            'full_name' => 'Ahmad @ Jakarta',
            'birth_place' => 'Jakarta @pusat@',
        ]);

        $content = $this->service()->export($family);

        $this->assertStringContainsString('1 NAME Ahmad @@ Jakarta', $content);
        $this->assertStringContainsString('2 PLAC Jakarta @@pusat@@', $content);
    }

    public function test_multiline_biography_uses_cont_and_conts_stay_under_line_limit(): void
    {
        $family = Family::factory()->create();
        FamilyMember::factory()->create([
            'family_id' => $family->id,
            'biography' => "Baris pertama.\nBaris kedua dengan kalimat yang cukup panjang untuk memastikan pemotongan baris bekerja dengan benar di luar batas.",
        ]);

        $content = $this->service()->export($family);

        $this->assertStringContainsString('1 NOTE Baris pertama.', $content);
        $this->assertStringContainsString('1 CONT Baris kedua', $content);
        foreach (preg_split('/\r\n/', $content) ?: [] as $line) {
            $this->assertLessThanOrEqual(255, strlen($line));
        }
    }

    public function test_filename_uses_slugged_family_name(): void
    {
        $family = Family::factory()->create(['name' => 'Keluarga Besar Ahmad']);

        $this->assertSame('keluarga-besar-ahmad-family-tree.ged', $this->service()->filename($family));
    }

    public function test_deleted_relationships_are_excluded(): void
    {
        $family = Family::factory()->create();
        $father = FamilyMember::factory()->create(['family_id' => $family->id, 'gender' => 'male']);
        $child = FamilyMember::factory()->create(['family_id' => $family->id, 'gender' => 'male']);
        $deleted = MemberRelationship::factory()->create(['family_id' => $family->id, 'source_member_id' => $father->id, 'target_member_id' => $child->id, 'relationship_type' => MemberRelationship::TYPE_FATHER]);
        $deleted->delete();

        $content = $this->service()->export($family);

        $this->assertStringNotContainsString('1 HUSB @I'.$father->id.'@', $content);
        $this->assertStringNotContainsString('1 CHIL @I'.$child->id.'@', $content);
    }
}
