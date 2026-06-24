<?php

namespace Tests\Unit;

use App\Models\Family;
use App\Models\User;
use App\Repositories\Eloquent\EloquentFamilyDashboardRepository;
use App\Repositories\Eloquent\EloquentFamilyRepository;
use App\Repositories\Eloquent\EloquentFamilyUserRoleRepository;
use App\Services\FamilyDashboardService;
use App\Services\FamilyRoleCatalogService;
use App\Services\FamilyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class FamilyDashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_counts_existing_source_tables(): void
    {
        $family = Family::factory()->create(['created_by' => User::factory()]);
        $user = User::factory()->create();
        DB::table('family_members')->insert([
            [
                'uuid' => (string) Str::uuid(),
                'family_id' => $family->id,
                'full_name' => 'Living Member',
                'is_alive' => true,
                'death_date' => null,
                'created_by' => $user->id,
            ],
            [
                'uuid' => (string) Str::uuid(),
                'family_id' => $family->id,
                'full_name' => 'Deceased Member',
                'is_alive' => false,
                'death_date' => '2024-01-01',
                'created_by' => $user->id,
            ],
        ]);

        $summary = $this->service()->summary($family);

        $this->assertSame(2, $summary->totalMembers);
        $this->assertSame(1, $summary->livingMembers);
        $this->assertSame(1, $summary->deceasedMembers);
    }

    private function service(): FamilyDashboardService
    {
        return new FamilyDashboardService(
            new EloquentFamilyDashboardRepository,
            new FamilyService(
                new EloquentFamilyRepository,
                new EloquentFamilyUserRoleRepository,
                new FamilyRoleCatalogService,
            ),
        );
    }
}
