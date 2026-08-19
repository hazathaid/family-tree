<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\User;
use App\Services\WebOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FamilyExportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_export_family_as_gedcom(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['name' => 'Keluarga Ahmad', 'created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $owner->id]);
        $member = FamilyMember::factory()->create(['family_id' => $family->id, 'full_name' => 'Hasan bin Ahmad']);
        Sanctum::actingAs($owner);

        $this->get('/api/v1/families/'.$family->uuid.'/export/gedcom')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/x-gedcom; charset=UTF-8')
            ->assertHeader('Content-Disposition', 'attachment; filename="keluarga-ahmad-family-tree.ged"')
            ->assertSee('0 HEAD', false)
            ->assertSee('0 TRLR', false)
            ->assertSee('0 @I'.$member->id.'@ INDI', false)
            ->assertSee('1 NAME Hasan bin Ahmad', false);
    }

    public function test_regular_member_can_export_family_as_gedcom(): void
    {
        $memberUser = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $memberUser->id]);
        FamilyUserRole::factory()->create([
            'family_id' => $family->id,
            'user_id' => $memberUser->id,
            'role' => FamilyUserRole::ROLE_MEMBER,
        ]);
        FamilyMember::factory()->create(['family_id' => $family->id]);
        Sanctum::actingAs($memberUser);

        $this->get('/api/v1/families/'.$family->uuid.'/export/gedcom')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/x-gedcom; charset=UTF-8');
    }

    public function test_outsider_cannot_export_family_as_gedcom(): void
    {
        $outsider = User::factory()->create();
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $owner->id]);
        FamilyMember::factory()->create(['family_id' => $family->id]);
        Sanctum::actingAs($outsider);

        $this->get('/api/v1/families/'.$family->uuid.'/export/gedcom')
            ->assertForbidden();
    }

    public function test_unauthenticated_user_is_rejected(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $owner->id]);

        $this->getJson('/api/v1/families/'.$family->uuid.'/export/gedcom')
            ->assertStatus(401);
    }

    public function test_web_owner_can_download_gedcom_from_settings(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['name' => 'Keluarga Ahmad', 'created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $owner->id]);
        $member = FamilyMember::factory()->create(['family_id' => $family->id, 'full_name' => 'Hasan bin Ahmad']);

        $this->withSession([WebOnboardingService::ACTIVE_FAMILY_KEY => $family->uuid])
            ->actingAs($owner)
            ->get('/settings/export/gedcom')
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="keluarga-ahmad-family-tree.ged"')
            ->assertSee('0 @I'.$member->id.'@ INDI', false);
    }

    public function test_settings_page_exposes_gedcom_export_link(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['name' => 'Keluarga Ahmad', 'created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $owner->id]);

        $this->withSession([WebOnboardingService::ACTIVE_FAMILY_KEY => $family->uuid])
            ->actingAs($owner)
            ->get('/settings')
            ->assertOk()
            ->assertSee('Ekspor GEDCOM')
            ->assertSee('href="'.route('settings.export.gedcom').'"', false);
    }

    public function test_web_outsider_without_active_family_is_redirected(): void
    {
        $outsider = User::factory()->create();
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $owner->id]);

        $this->withSession([WebOnboardingService::ACTIVE_FAMILY_KEY => $family->uuid])
            ->actingAs($outsider)
            ->get('/settings/export/gedcom')
            ->assertRedirect(route('onboarding.index'));
    }
}
