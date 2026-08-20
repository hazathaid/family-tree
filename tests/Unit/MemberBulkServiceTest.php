<?php

namespace Tests\Unit;

use App\Models\ActivityLog;
use App\Models\Family;
use App\Models\FamilyBranch;
use App\Models\FamilyMember;
use App\Models\User;
use App\Services\MemberBulkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MemberBulkServiceTest extends TestCase
{
    use RefreshDatabase;

    private const CSV = <<<'CSV'
full_name,nickname,gender,religion,birth_date,birth_place,is_alive,death_date,death_place,biography,branch_uuid
Hasan Basri,Hasan,male,islam,1990-03-05,Bandung,true,,,,
Siti Aminah,,female,islam,1992-07-14,Jakarta,false,2020-01-01,Depok,,
CSV;

    public function test_it_imports_valid_csv_rows(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);

        $summary = app(MemberBulkService::class)->import($user, $family, self::CSV);

        $this->assertSame(2, $summary['imported']);
        $this->assertSame(0, $summary['skipped']);
        $this->assertSame([], $summary['errors']);

        $hasan = FamilyMember::where('family_id', $family->id)->where('full_name', 'Hasan Basri')->first();
        $this->assertSame('male', $hasan->gender);
        $this->assertSame('1990-03-05', $hasan->birth_date->format('Y-m-d'));
        $this->assertTrue($hasan->is_alive);

        $siti = FamilyMember::where('family_id', $family->id)->where('full_name', 'Siti Aminah')->first();
        $this->assertFalse($siti->is_alive);
        $this->assertSame('2020-01-01', $siti->death_date->format('Y-m-d'));
    }

    public function test_it_reports_per_row_validation_errors(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $csv = <<<'CSV'
full_name,nickname,gender,religion
Hasan Basri,,invalid,islam
,,male,islam
Budi,,male,islam
CSV;

        $summary = app(MemberBulkService::class)->import($user, $family, $csv);

        $this->assertSame(1, $summary['imported']);
        $this->assertSame(2, $summary['skipped']);
        $this->assertCount(2, $summary['errors']);
        $this->assertArrayHasKey('gender', $summary['errors'][0]['errors']);
        $this->assertArrayHasKey('full_name', $summary['errors'][1]['errors']);
        $this->assertSame(2, $summary['errors'][0]['row']);
    }

    public function test_it_rejects_branch_from_another_family(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $other = Family::factory()->create(['created_by' => $user->id]);
        $branch = FamilyBranch::factory()->create(['family_id' => $other->id]);
        $csv = "full_name,branch_uuid\nBudi,".$branch->uuid;

        $summary = app(MemberBulkService::class)->import($user, $family, $csv);

        $this->assertSame(0, $summary['imported']);
        $this->assertSame(1, $summary['skipped']);
        $this->assertArrayHasKey('branch_uuid', $summary['errors'][0]['errors']);
    }

    public function test_it_resolves_branch_in_same_family(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $branch = FamilyBranch::factory()->create(['family_id' => $family->id]);
        $csv = "full_name,branch_uuid\nBudi,".$branch->uuid;

        $summary = app(MemberBulkService::class)->import($user, $family, $csv);

        $this->assertSame(1, $summary['imported']);
        $member = FamilyMember::where('full_name', 'Budi')->first();
        $this->assertSame($branch->id, $member->family_branch_id);
    }

    public function test_it_logs_members_imported_activity(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);

        app(MemberBulkService::class)->import($user, $family, self::CSV);

        $this->assertDatabaseHas('activity_logs', [
            'family_id' => $family->id,
            'user_id' => $user->id,
            'activity_type' => ActivityLog::MEMBERS_IMPORTED,
        ]);
    }

    public function test_it_rejects_csv_without_full_name_column(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);

        $this->expectException(ValidationException::class);

        app(MemberBulkService::class)->import($user, $family, "name,gender\nBudi,male");
    }

    public function test_it_exports_members_as_csv(): void
    {
        $family = Family::factory()->create(['name' => 'Keluarga Ahmad']);
        $branch = FamilyBranch::factory()->create(['family_id' => $family->id, 'name' => 'Cabang 1']);
        FamilyMember::factory()->create([
            'family_id' => $family->id,
            'family_branch_id' => $branch->id,
            'full_name' => 'Hasan Basri',
            'gender' => 'male',
            'birth_date' => '1990-03-05',
            'is_alive' => true,
        ]);
        FamilyMember::factory()->create([
            'family_id' => $family->id,
            'full_name' => 'Siti Aminah',
            'gender' => 'female',
            'is_alive' => false,
            'death_date' => '2020-01-01',
        ]);

        $csv = app(MemberBulkService::class)->export($family);

        $this->assertStringContainsString('uuid,full_name,nickname,gender', $csv);
        $this->assertStringContainsString('Hasan Basri', $csv);
        $this->assertStringContainsString($branch->uuid, $csv);
        $this->assertStringContainsString('Siti Aminah', $csv);
    }
}
