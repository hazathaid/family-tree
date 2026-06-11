<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyUserRole;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FamilyDashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_family_dashboard_returns_statistics(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        FamilyUserRole::factory()->owner()->create([
            'family_id' => $family->id,
            'user_id' => $user->id,
        ]);
        $this->createDashboardSourceTables();
        Sanctum::actingAs($user);

        DB::table('family_members')->insert([
            ['id' => 1, 'family_id' => $family->id, 'is_alive' => true],
            ['id' => 2, 'family_id' => $family->id, 'is_alive' => false],
        ]);
        DB::table('articles')->insert([
            ['family_id' => $family->id],
            ['family_id' => $family->id],
        ]);
        DB::table('member_photos')->insert([
            ['member_id' => 1],
        ]);
        DB::table('events')->insert([
            ['family_id' => $family->id],
        ]);

        $this->getJson('/api/v1/families/'.$family->uuid.'/dashboard')
            ->assertOk()
            ->assertJsonPath('data.total_members', 2)
            ->assertJsonPath('data.living_members', 1)
            ->assertJsonPath('data.deceased_members', 1)
            ->assertJsonPath('data.total_articles', 2)
            ->assertJsonPath('data.total_photos', 1)
            ->assertJsonPath('data.total_events', 1);
    }

    private function createDashboardSourceTables(): void
    {
        Schema::create('family_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('family_id');
            $table->boolean('is_alive');
        });

        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('family_id');
        });

        Schema::create('member_photos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id');
        });

        Schema::create('events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('family_id');
        });
    }
}
