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
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FamilyDashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_counts_existing_source_tables(): void
    {
        $family = Family::factory()->create(['created_by' => User::factory()]);
        $this->createFamilyMembersTable();
        DB::table('family_members')->insert([
            ['family_id' => $family->id, 'is_alive' => true],
            ['family_id' => $family->id, 'is_alive' => false],
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

    private function createFamilyMembersTable(): void
    {
        Schema::create('family_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('family_id');
            $table->boolean('is_alive');
        });
    }
}
