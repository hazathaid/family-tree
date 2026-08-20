<?php

namespace Tests\Unit;

use App\Models\ActivityLog;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\MemberRelationship;
use App\Models\User;
use App\Services\GedcomImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GedcomImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private const SAMPLE = <<<'GEDCOM'
0 HEAD
1 SOUR Family Tree Platform Indonesia
1 GEDC
2 VERS 5.5.1
2 FORM LINEAGE-LINKED
1 CHAR UTF-8
1 SUBM @SUBM@
0 @SUBM@ SUBM
1 NAME Keluarga Ahmad
0 @I1@ INDI
1 NAME Ahmad /Surya/
1 SEX M
1 BIRT
2 DATE 12 MAY 1960
2 PLAC Bandung
1 DEAT
2 DATE 1 JAN 2010
1 NOTE Pendiri keluarga.
0 @I2@ INDI
1 NAME Siti /Aminah/
1 SEX F
1 BIRT
2 DATE 1 JUN 1965
0 @I3@ INDI
1 NAME Hasan /Surya/
1 SEX M
1 BIRT
2 DATE 5 MAR 1990
0 @F1@ FAM
1 HUSB @I1@
1 WIFE @I2@
1 CHIL @I3@
0 TRLR
GEDCOM;

    public function test_it_imports_individuals_and_base_relationships(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);

        $summary = app(GedcomImportService::class)->import($user, $family, self::SAMPLE);

        $this->assertSame(3, $summary['members_created']);
        $this->assertSame(4, $summary['relationships_created']);
        $this->assertSame(0, $summary['members_skipped']);
        $this->assertSame(0, $summary['relationships_skipped']);

        $ahmad = FamilyMember::where('family_id', $family->id)->where('full_name', 'Ahmad Surya')->first();
        $this->assertNotNull($ahmad);
        $this->assertSame('male', $ahmad->gender);
        $this->assertSame('1960-05-12', $ahmad->birth_date->format('Y-m-d'));
        $this->assertSame('Bandung', $ahmad->birth_place);
        $this->assertSame('2010-01-01', $ahmad->death_date->format('Y-m-d'));
        $this->assertFalse($ahmad->is_alive);

        $hasan = FamilyMember::where('family_id', $family->id)->where('full_name', 'Hasan Surya')->first();
        $this->assertTrue($hasan->is_alive);

        $fatherEdge = MemberRelationship::where('family_id', $family->id)
            ->where('relationship_type', MemberRelationship::TYPE_FATHER)
            ->first();
        $this->assertSame($ahmad->id, $fatherEdge->source_member_id);
        $this->assertSame($hasan->id, $fatherEdge->target_member_id);

        $husbandEdge = MemberRelationship::where('family_id', $family->id)
            ->where('relationship_type', MemberRelationship::TYPE_HUSBAND)
            ->first();
        $this->assertSame($ahmad->id, $husbandEdge->source_member_id);
    }

    public function test_it_writes_both_spouse_edges_for_a_husband_wife_pair(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);

        app(GedcomImportService::class)->import($user, $family, self::SAMPLE);

        $ahmad = FamilyMember::where('full_name', 'Ahmad Surya')->first();
        $siti = FamilyMember::where('full_name', 'Siti Aminah')->first();

        $this->assertDatabaseHas('member_relationships', [
            'family_id' => $family->id,
            'source_member_id' => $ahmad->id,
            'target_member_id' => $siti->id,
            'relationship_type' => MemberRelationship::TYPE_HUSBAND,
        ]);
        $this->assertDatabaseHas('member_relationships', [
            'family_id' => $family->id,
            'source_member_id' => $siti->id,
            'target_member_id' => $ahmad->id,
            'relationship_type' => MemberRelationship::TYPE_WIFE,
        ]);
    }

    public function test_it_logs_a_gedcom_import_activity(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);

        app(GedcomImportService::class)->import($user, $family, self::SAMPLE);

        $this->assertDatabaseHas('activity_logs', [
            'family_id' => $family->id,
            'user_id' => $user->id,
            'activity_type' => ActivityLog::GEDCOM_IMPORTED,
        ]);
    }

    public function test_duplicate_family_records_within_a_file_are_skipped(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $gedcom = <<<'GEDCOM'
0 HEAD
0 @I1@ INDI
1 NAME Ahmad
1 SEX M
0 @I2@ INDI
1 NAME Siti
1 SEX F
0 @I3@ INDI
1 NAME Hasan
1 SEX M
0 @F1@ FAM
1 HUSB @I1@
1 WIFE @I2@
1 CHIL @I3@
0 @F2@ FAM
1 HUSB @I1@
1 WIFE @I2@
1 CHIL @I3@
0 TRLR
GEDCOM;

        $summary = app(GedcomImportService::class)->import($user, $family, $gedcom);

        $this->assertSame(3, $summary['members_created']);
        $this->assertSame(4, $summary['relationships_created']);
        $this->assertSame(4, $summary['relationships_skipped']);
        $this->assertSame(4, MemberRelationship::where('family_id', $family->id)->count());
    }

    public function test_it_skips_individuals_without_a_name(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $gedcom = str_replace('1 NAME Ahmad /Surya/', '1 NOTE unnamed', self::SAMPLE);

        $summary = app(GedcomImportService::class)->import($user, $family, $gedcom);

        $this->assertSame(2, $summary['members_created']);
        $this->assertSame(1, $summary['members_skipped']);
    }

    public function test_it_rejects_content_that_is_not_a_gedcom_document(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);

        $this->expectException(ValidationException::class);

        app(GedcomImportService::class)->import($user, $family, 'Ini bukan GEDCOM.');
    }

    public function test_it_parses_partial_gedcom_dates_and_gedcom_names(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $gedcom = <<<'GEDCOM'
0 HEAD
0 @I1@ INDI
1 NAME Budi
1 BIRT
2 DATE MAY 1990
0 @I2@ INDI
1 NAME /Santoso/
0 TRLR
GEDCOM;

        $summary = app(GedcomImportService::class)->import($user, $family, $gedcom);

        $this->assertSame(2, $summary['members_created']);
        $budi = FamilyMember::where('full_name', 'Budi')->first();
        $this->assertSame('1990-05-01', $budi->birth_date->format('Y-m-d'));
        $this->assertDatabaseHas('family_members', ['full_name' => 'Santoso']);
    }
}
