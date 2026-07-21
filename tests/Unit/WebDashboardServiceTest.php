<?php

namespace Tests\Unit;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\User;
use App\Services\WebDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WebDashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_family_facts_and_caches_family_widgets(): void
    {
        Cache::flush();
        $user = User::factory()->create();
        $family = Family::factory()->create(['origin_city' => 'Bandung']);
        FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $user->id,
            'full_name' => 'Anggota Tertua',
            'birth_date' => '1940-01-01',
        ]);
        FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $user->id,
            'full_name' => 'Anggota Termuda',
            'birth_date' => '2010-01-01',
        ]);

        $dashboard = app(WebDashboardService::class)->show($family, $user);

        $this->assertSame('Bandung', $dashboard->facts[0]['value']);
        $this->assertSame('Anggota Tertua', $dashboard->facts[1]['value']);
        $this->assertSame('Anggota Termuda', $dashboard->facts[2]['value']);
        $this->assertTrue(Cache::has("web-dashboard:family:{$family->id}"));
    }
}
