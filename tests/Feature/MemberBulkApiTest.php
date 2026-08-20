<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MemberBulkApiTest extends TestCase
{
    use RefreshDatabase;

    private const CSV = <<<'CSV'
full_name,nickname,gender,religion,birth_date
Hasan Basri,Hasan,male,islam,1990-03-05
Siti Aminah,,female,islam,1992-07-14
CSV;

    public function test_owner_can_import_members_from_csv(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['name' => 'Keluarga Ahmad', 'created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $owner->id]);
        Sanctum::actingAs($owner);

        $this->post('/api/v1/families/'.$family->uuid.'/members/import', [
            'file' => UploadedFile::fake()->createWithContent('members.csv', self::CSV),
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.imported', 2)
            ->assertJsonPath('data.skipped', 0);

        $this->assertSame(2, FamilyMember::where('family_id', $family->id)->count());
        $this->assertDatabaseHas('activity_logs', [
            'family_id' => $family->id,
            'user_id' => $owner->id,
            'activity_type' => ActivityLog::MEMBERS_IMPORTED,
        ]);
    }

    public function test_admin_can_import_members_from_csv(): void
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

        $this->post('/api/v1/families/'.$family->uuid.'/members/import', [
            'file' => UploadedFile::fake()->createWithContent('members.csv', self::CSV),
        ])->assertOk()->assertJsonPath('data.imported', 2);
    }

    public function test_regular_member_cannot_import_members(): void
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

        $this->post('/api/v1/families/'.$family->uuid.'/members/import', [
            'file' => UploadedFile::fake()->createWithContent('members.csv', self::CSV),
        ])->assertForbidden();
    }

    public function test_outsider_cannot_import_members(): void
    {
        $outsider = User::factory()->create();
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $owner->id]);
        Sanctum::actingAs($outsider);

        $this->post('/api/v1/families/'.$family->uuid.'/members/import', [
            'file' => UploadedFile::fake()->createWithContent('members.csv', self::CSV),
        ])->assertForbidden();
    }

    public function test_owner_can_export_members_as_csv(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['name' => 'Keluarga Ahmad', 'created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $owner->id]);
        FamilyMember::factory()->create(['family_id' => $family->id, 'full_name' => 'Hasan Basri']);
        Sanctum::actingAs($owner);

        $this->get('/api/v1/families/'.$family->uuid.'/members/export')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeader('Content-Disposition', 'attachment; filename="keluarga-ahmad-members.csv"')
            ->assertSee('full_name', false)
            ->assertSee('Hasan Basri', false);
    }

    public function test_regular_member_can_export_members_as_csv(): void
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
        FamilyMember::factory()->create(['family_id' => $family->id, 'full_name' => 'Hasan Basri']);
        Sanctum::actingAs($memberUser);

        $this->get('/api/v1/families/'.$family->uuid.'/members/export')->assertOk();
    }

    public function test_outsider_cannot_export_members(): void
    {
        $outsider = User::factory()->create();
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $owner->id]);
        Sanctum::actingAs($outsider);

        $this->get('/api/v1/families/'.$family->uuid.'/members/export')->assertForbidden();
    }

    public function test_unauthenticated_user_is_rejected(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $owner->id]);

        $this->postJson('/api/v1/families/'.$family->uuid.'/members/import', [
            'file' => UploadedFile::fake()->createWithContent('members.csv', self::CSV),
        ])->assertStatus(401);

        $this->getJson('/api/v1/families/'.$family->uuid.'/members/export')->assertStatus(401);
    }

    public function test_csv_without_full_name_column_is_rejected(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $owner->id]);
        Sanctum::actingAs($owner);

        $this->postJson('/api/v1/families/'.$family->uuid.'/members/import', [
            'file' => UploadedFile::fake()->createWithContent('members.csv', "name,gender\nBudi,male"),
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
