<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyUserRole;
use App\Models\User;
use App\Services\WebOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_family_sees_empty_state_and_can_create_family(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/onboarding')->assertOk()->assertSee('Belum ada keluarga');

        $this->actingAs($user)->post('/onboarding/families', [
            'name' => 'Keluarga Besar Santoso',
            'origin_city' => 'Bandung',
        ])->assertRedirect(route('dashboard'))
            ->assertSessionHas(WebOnboardingService::ACTIVE_FAMILY_KEY);

        $family = Family::query()->where('name', 'Keluarga Besar Santoso')->firstOrFail();
        $this->assertDatabaseHas('family_user_roles', [
            'family_id' => $family->id,
            'user_id' => $user->id,
            'role' => FamilyUserRole::ROLE_OWNER,
        ]);
    }

    public function test_user_can_select_an_accessible_family(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create();
        FamilyUserRole::factory()->create(['user_id' => $user->id, 'family_id' => $family->id]);

        $this->actingAs($user)->post(route('families.activate', $family))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas(WebOnboardingService::ACTIVE_FAMILY_KEY, $family->uuid);
    }

    public function test_user_cannot_select_another_users_family(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create();

        $this->actingAs($user)->post(route('families.activate', $family))->assertForbidden();
        $this->assertNull(session(WebOnboardingService::ACTIVE_FAMILY_KEY));
    }

    public function test_guest_is_redirected_and_unverified_user_cannot_open_onboarding(): void
    {
        $this->get('/onboarding')->assertRedirect(route('login'));

        $user = User::factory()->unverified()->create();
        $this->actingAs($user)->get('/onboarding')->assertRedirect(route('verification.notice'));
    }

    public function test_dashboard_requires_an_accessible_active_family(): void
    {
        $user = User::factory()->create();
        $otherFamily = Family::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('onboarding.index'));

        $this->withSession([WebOnboardingService::ACTIVE_FAMILY_KEY => $otherFamily->uuid])
            ->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('onboarding.index'));
    }
}
