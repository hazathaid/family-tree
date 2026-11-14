<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\MemberRelationship;
use App\Models\User;
use App\Services\GedcomExportService;
use App\Services\GedcomImportService;
use App\Services\RelationshipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GedcomMarriageRoundTripTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_corrected_divorce_round_trips_through_gedcom(): void
    {
        $user = User::factory()->create();
        $source = Family::factory()->create(['created_by' => $user->id]);
        $husband = FamilyMember::factory()->create([
            'family_id' => $source->id,
            'created_by' => $user->id,
            'full_name' => 'Budi Santoso',
            'gender' => 'male',
        ]);
        $wife = FamilyMember::factory()->create([
            'family_id' => $source->id,
            'created_by' => $user->id,
            'full_name' => 'Dewi Santoso',
            'gender' => 'female',
        ]);
        $relationship = MemberRelationship::factory()->create([
            'family_id' => $source->id,
            'source_member_id' => $husband->id,
            'target_member_id' => $wife->id,
            'relationship_type' => MemberRelationship::TYPE_HUSBAND,
            'start_date' => '2010-01-01',
            'end_date' => '2024-05-01',
            'end_reason' => MemberRelationship::END_REASON_DIVORCED,
        ]);
        app(RelationshipService::class)->correctMarriageEnd($relationship, '2023-12-31', 'divorced');

        $content = app(GedcomExportService::class)->export($source);
        $target = Family::factory()->create(['created_by' => $user->id]);

        app(GedcomImportService::class)->import($user, $target, $content);

        $importedHusband = FamilyMember::query()
            ->where('family_id', $target->id)
            ->where('full_name', 'Budi Santoso')
            ->firstOrFail();
        $importedWife = FamilyMember::query()
            ->where('family_id', $target->id)
            ->where('full_name', 'Dewi Santoso')
            ->firstOrFail();
        $edge = MemberRelationship::query()
            ->where('family_id', $target->id)
            ->where('source_member_id', $importedHusband->id)
            ->where('target_member_id', $importedWife->id)
            ->where('relationship_type', MemberRelationship::TYPE_HUSBAND)
            ->firstOrFail();

        $this->assertSame('2023-12-31', $edge->end_date?->toDateString());
        $this->assertSame(MemberRelationship::END_REASON_DIVORCED, $edge->end_reason);
    }
}
