<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\MemberRelationship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FamilyImportApiTest extends TestCase
{
    use RefreshDatabase;

    private const GEDCOM = <<<'GEDCOM'
0 HEAD
1 SOUR Family Tree Platform Indonesia
1 GEDC
2 VERS 5.5.1
2 FORM LINEAGE-LINKED
1 CHAR UTF-8
0 @I1@ INDI
1 NAME Ahmad /Surya/
1 SEX M
0 @I2@ INDI
1 NAME Siti /Aminah/
1 SEX F
0 @F1@ FAM
1 HUSB @I1@
1 WIFE @I2@
0 TRLR
GEDCOM;

    public function test_owner_can_import_gedcom_file(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $owner->id]);
        Sanctum::actingAs($owner);

        $this->post('/api/v1/families/'.$family->uuid.'/import/gedcom', [
            'file' => UploadedFile::fake()->createWithContent('family.ged', self::GEDCOM),
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.members_created', 2)
            ->assertJsonPath('data.relationships_created', 2);

        $this->assertSame(2, FamilyMember::where('family_id', $family->id)->count());
        $this->assertSame(2, MemberRelationship::where('family_id', $family->id)->count());
        $this->assertDatabaseHas('activity_logs', [
            'family_id' => $family->id,
            'user_id' => $owner->id,
            'activity_type' => ActivityLog::GEDCOM_IMPORTED,
        ]);
    }

    public function test_admin_can_import_gedcom_file(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $owner->id]);
        FamilyUserRole::factory()->create([
            'family_id' => $family->id,
            'user_id' => $admin->id,
            'role' => FamilyUserRole::ROLE_ADMIN,
        ]);
        Sanctum::actingAs($admin);

        $this->post('/api/v1/families/'.$family->uuid.'/import/gedcom', [
            'file' => UploadedFile::fake()->createWithContent('family.ged', self::GEDCOM),
        ])
            ->assertOk()
            ->assertJsonPath('data.members_created', 2);
    }

    public function test_regular_member_cannot_import_gedcom_file(): void
    {
        $owner = User::factory()->create();
        $memberUser = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $owner->id]);
        FamilyUserRole::factory()->create([
            'family_id' => $family->id,
            'user_id' => $memberUser->id,
            'role' => FamilyUserRole::ROLE_MEMBER,
        ]);
        Sanctum::actingAs($memberUser);

        $this->post('/api/v1/families/'.$family->uuid.'/import/gedcom', [
            'file' => UploadedFile::fake()->createWithContent('family.ged', self::GEDCOM),
        ])
            ->assertForbidden();
    }

    public function test_outsider_cannot_import_gedcom_file(): void
    {
        $outsider = User::factory()->create();
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $owner->id]);
        Sanctum::actingAs($outsider);

        $this->post('/api/v1/families/'.$family->uuid.'/import/gedcom', [
            'file' => UploadedFile::fake()->createWithContent('family.ged', self::GEDCOM),
        ])
            ->assertForbidden();
    }

    public function test_unauthenticated_user_is_rejected(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $owner->id]);

        $this->postJson('/api/v1/families/'.$family->uuid.'/import/gedcom', [
            'file' => UploadedFile::fake()->createWithContent('family.ged', self::GEDCOM),
        ])->assertStatus(401);
    }

    public function test_invalid_gedcom_content_returns_validation_error(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $owner->id]);
        Sanctum::actingAs($owner);

        $this->postJson('/api/v1/families/'.$family->uuid.'/import/gedcom', [
            'file' => UploadedFile::fake()->createWithContent('family.ged', 'bukan gedcom'),
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_missing_file_is_rejected(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $owner->id]);
        Sanctum::actingAs($owner);

        $this->postJson('/api/v1/families/'.$family->uuid.'/import/gedcom')
            ->assertStatus(422);
    }
}
