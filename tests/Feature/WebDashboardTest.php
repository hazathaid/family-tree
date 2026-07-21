<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\Notification;
use App\Models\User;
use App\Services\WebOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class WebDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_family_scoped_widgets(): void
    {
        Date::setTestNow('2026-07-21 09:00:00');
        [$user, $family] = $this->memberWithFamily();
        $otherFamily = Family::factory()->create();

        FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $user->id,
            'full_name' => 'Budi Santoso',
            'birth_date' => '1990-07-23',
        ]);
        FamilyMember::factory()->create([
            'family_id' => $otherFamily->id,
            'full_name' => 'Tidak Boleh Terlihat',
        ]);
        ActivityLog::factory()->create([
            'family_id' => $family->id,
            'user_id' => $user->id,
            'payload' => ['name' => 'Budi Santoso'],
        ]);
        Event::factory()->create([
            'family_id' => $family->id,
            'organizer_id' => $user->id,
            'title' => 'Reuni Keluarga',
            'event_date' => now()->addDay(),
        ]);
        Notification::factory()->create([
            'user_id' => $user->id,
            'title' => 'Kabar keluarga',
            'data' => ['family_uuid' => $family->uuid],
        ]);

        $response = $this->withSession([WebOnboardingService::ACTIVE_FAMILY_KEY => $family->uuid])
            ->actingAs($user)
            ->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Selamat datang')
            ->assertSee('Budi Santoso')
            ->assertSee('Reuni Keluarga')
            ->assertSee('Kabar keluarga')
            ->assertDontSee('Tidak Boleh Terlihat');
    }

    public function test_every_dashboard_widget_has_an_empty_state(): void
    {
        [$user, $family] = $this->memberWithFamily(['origin_city' => null]);

        $this->withSession([WebOnboardingService::ACTIVE_FAMILY_KEY => $family->uuid])
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Belum ada aktivitas')
            ->assertSee('Belum ada anggota')
            ->assertSee('Tidak ada ulang tahun')
            ->assertSee('Belum ada acara')
            ->assertSee('Tidak ada notifikasi')
            ->assertSee('Fakta belum tersedia');
    }

    public function test_dashboard_rejects_an_inaccessible_active_family(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create();

        $this->withSession([WebOnboardingService::ACTIVE_FAMILY_KEY => $family->uuid])
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('onboarding.index'));
    }

    public function test_dashboard_loads_within_two_seconds_for_documented_dataset(): void
    {
        [$user, $family] = $this->memberWithFamily();
        FamilyMember::factory()->count(1000)->create([
            'family_id' => $family->id,
            'created_by' => $user->id,
        ]);

        $startedAt = hrtime(true);
        $this->withSession([WebOnboardingService::ACTIVE_FAMILY_KEY => $family->uuid])
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
        $duration = (hrtime(true) - $startedAt) / 1_000_000_000;

        $this->assertLessThan(2.0, $duration, "Dashboard took {$duration} seconds.");
    }

    private function memberWithFamily(array $familyAttributes = []): array
    {
        $user = User::factory()->create();
        $family = Family::factory()->create($familyAttributes);
        FamilyUserRole::factory()->create([
            'family_id' => $family->id,
            'user_id' => $user->id,
        ]);

        return [$user, $family];
    }
}
