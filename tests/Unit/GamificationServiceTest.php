<?php

namespace Tests\Unit;

use App\Models\Family;
use App\Models\User;
use App\Services\GamificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_award_is_idempotent_and_unlocks_badges(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $service = app(GamificationService::class);

        for ($id = 1; $id <= 10; $id++) {
            $service->award($family, $user, GamificationService::ACTION_UPLOAD_PHOTO, Model::class, $id);
        }
        $service->award($family, $user, GamificationService::ACTION_UPLOAD_PHOTO, Model::class, 1);

        $profile = $service->profile($family, $user);

        $this->assertSame(50, $profile['points']);
        $this->assertSame(['penjaga_sejarah'], $profile['badges']->pluck('badge.code')->all());
    }

    public function test_leaderboards_are_sorted_and_ranked(): void
    {
        $first = User::factory()->create(['name' => 'First']);
        $second = User::factory()->create(['name' => 'Second']);
        $family = Family::factory()->create(['created_by' => $first->id]);
        $service = app(GamificationService::class);

        $service->award($family, $first, GamificationService::ACTION_WRITE_ARTICLE, Model::class, 1);
        $service->award($family, $second, GamificationService::ACTION_ADD_MEMBER, Model::class, 2);

        $leaderboard = $service->userLeaderboard($family, 20);

        $this->assertSame('First', $leaderboard[0]->name);
        $this->assertSame(1, $leaderboard[0]->rank);
        $this->assertSame(15, $leaderboard[0]->points);
    }
}
