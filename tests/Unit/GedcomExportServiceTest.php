<?php

namespace Tests\Unit;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\MemberRelationship;
use App\Repositories\Eloquent\EloquentFamilyMemberRepository;
use App\Repositories\Eloquent\EloquentRelationshipRepository;
use App\Services\GedcomExportService;
use App\Services\RelationshipService;
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

    public function test_polygamous_household_exports_one_family_per_wife(): void
    {
        $family = Family::factory()->create();
        $husband = FamilyMember::factory()->create(['family_id' => $family->id, 'gender' => 'male']);
        $firstWife = FamilyMember::factory()->create(['family_id' => $family->id, 'gender' => 'female']);
        $secondWife = FamilyMember::factory()->create(['family_id' => $family->id, 'gender' => 'female']);
        $firstChild = FamilyMember::factory()->create(['family_id' => $family->id, 'gender' => 'male']);
        $secondChild = FamilyMember::factory()->create(['family_id' => $family->id, 'gender' => 'female']);

        foreach ([
            [$husband, $firstWife, MemberRelationship::TYPE_HUSBAND],
            [$firstWife, $husband, MemberRelationship::TYPE_WIFE],
            [$husband, $secondWife, MemberRelationship::TYPE_HUSBAND],
            [$secondWife, $husband, MemberRelationship::TYPE_WIFE],
            [$husband, $firstChild, MemberRelationship::TYPE_FATHER],
            [$firstWife, $firstChild, MemberRelationship::TYPE_MOTHER],
            [$husband, $secondChild, MemberRelationship::TYPE_FATHER],
            [$secondWife, $secondChild, MemberRelationship::TYPE_MOTHER],
        ] as [$source, $target, $type]) {
            MemberRelationship::factory()->create([
                'family_id' => $family->id,
                'source_member_id' => $source->id,
                'target_member_id' => $target->id,
                'relationship_type' => $type,
            ]);
        }

        $content = $this->service()->export($family);
        $families = $this->familyRecords($content);

        $this->assertCount(2, $families);

        $firstFamily = $this->familyFor($families, $husband->id, $firstWife->id, $firstChild->id);
        $secondFamily = $this->familyFor($families, $husband->id, $secondWife->id, $secondChild->id);

        $this->assertNotNull($firstFamily);
        $this->assertNotNull($secondFamily);
        $this->assertNotSame($firstFamily, $secondFamily);

        $this->assertSame(2, substr_count($this->individualBlock($content, $husband->id), '1 FAMS @'));
        $this->assertStringContainsString('1 FAMC @'.$firstFamily.'@', $this->individualBlock($content, $firstChild->id));
        $this->assertStringContainsString('1 FAMC @'.$secondFamily.'@', $this->individualBlock($content, $secondChild->id));
        $this->assertStringNotContainsString('1 FAMC @'.$secondFamily.'@', $this->individualBlock($content, $firstChild->id));
    }

    public function test_divorced_marriage_exports_a_div_record(): void
    {
        $family = Family::factory()->create();
        $husband = FamilyMember::factory()->create(['family_id' => $family->id, 'gender' => 'male']);
        $wife = FamilyMember::factory()->create(['family_id' => $family->id, 'gender' => 'female']);

        MemberRelationship::factory()->create([
            'family_id' => $family->id,
            'source_member_id' => $husband->id,
            'target_member_id' => $wife->id,
            'relationship_type' => MemberRelationship::TYPE_HUSBAND,
            'start_date' => '2010-01-01',
            'end_date' => '2024-05-01',
            'end_reason' => MemberRelationship::END_REASON_DIVORCED,
        ]);

        $content = $this->service()->export($family);

        $this->assertStringContainsString('1 DIV', $content);
        $this->assertStringContainsString('2 DATE 1 MAY 2024', $content);
    }

    public function test_corrected_marriage_end_updates_the_div_record(): void
    {
        $family = Family::factory()->create();
        $husband = FamilyMember::factory()->create(['family_id' => $family->id, 'gender' => 'male']);
        $wife = FamilyMember::factory()->create(['family_id' => $family->id, 'gender' => 'female']);
        $relationship = MemberRelationship::factory()->create([
            'family_id' => $family->id,
            'source_member_id' => $husband->id,
            'target_member_id' => $wife->id,
            'relationship_type' => MemberRelationship::TYPE_HUSBAND,
            'start_date' => '2010-01-01',
            'end_date' => '2024-05-01',
            'end_reason' => MemberRelationship::END_REASON_DIVORCED,
        ]);

        app(RelationshipService::class)->correctMarriageEnd($relationship, '2023-12-31', 'divorced');

        $content = $this->service()->export($family);

        $this->assertStringContainsString('1 DIV', $content);
        $this->assertStringContainsString('2 DATE 31 DEC 2023', $content);
        $this->assertStringNotContainsString('2 DATE 1 MAY 2024', $content);
    }

    public function test_reopened_marriage_omits_the_div_record(): void
    {
        $family = Family::factory()->create();
        $husband = FamilyMember::factory()->create(['family_id' => $family->id, 'gender' => 'male']);
        $wife = FamilyMember::factory()->create(['family_id' => $family->id, 'gender' => 'female']);
        $relationship = MemberRelationship::factory()->create([
            'family_id' => $family->id,
            'source_member_id' => $husband->id,
            'target_member_id' => $wife->id,
            'relationship_type' => MemberRelationship::TYPE_HUSBAND,
            'start_date' => '2010-01-01',
            'end_date' => '2024-05-01',
            'end_reason' => MemberRelationship::END_REASON_DIVORCED,
        ]);

        app(RelationshipService::class)->reopenMarriage($relationship);

        $content = $this->service()->export($family);

        $this->assertStringNotContainsString('1 DIV', $content);
        $this->assertStringNotContainsString('2 DATE 1 MAY 2024', $content);
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

    /**
     * @return array<string, array{husbands: list<int>, wives: list<int>, children: list<int>}>
     */
    private function familyRecords(string $content): array
    {
        $records = [];
        $current = null;

        foreach (preg_split('/\r\n/', trim($content)) ?: [] as $line) {
            if (preg_match('/^0 @(F\d+)@ FAM$/', $line, $match) === 1) {
                $current = $match[1];
                $records[$current] = ['husbands' => [], 'wives' => [], 'children' => []];

                continue;
            }

            if (str_starts_with($line, '0 ')) {
                $current = null;

                continue;
            }

            if ($current === null) {
                continue;
            }

            if (preg_match('/^1 HUSB @I(\d+)@$/', $line, $match) === 1) {
                $records[$current]['husbands'][] = (int) $match[1];
            }
            if (preg_match('/^1 WIFE @I(\d+)@$/', $line, $match) === 1) {
                $records[$current]['wives'][] = (int) $match[1];
            }
            if (preg_match('/^1 CHIL @I(\d+)@$/', $line, $match) === 1) {
                $records[$current]['children'][] = (int) $match[1];
            }
        }

        return $records;
    }

    /**
     * @param  array<string, array{husbands: list<int>, wives: list<int>, children: list<int>}>  $records
     */
    private function familyFor(array $records, int $husbandId, int $wifeId, int $childId): ?string
    {
        foreach ($records as $xref => $record) {
            if (in_array($husbandId, $record['husbands'], true)
                && in_array($wifeId, $record['wives'], true)
                && in_array($childId, $record['children'], true)) {
                return $xref;
            }
        }

        return null;
    }

    private function individualBlock(string $content, int $memberId): string
    {
        $normalized = str_replace("\r\n", "\n", $content);

        if (preg_match('/^0 @I'.$memberId.'@ INDI\n(.*?)(?=^0 @|^0 TRLR)/ms', $normalized, $match) === 1) {
            return $match[1];
        }

        return '';
    }
}
