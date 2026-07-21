<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyUserRole;
use App\Models\User;
use App\Services\FamilyMemberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GamificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_creation_awards_points_and_profile_returns_them(): void
    {
        [$user, $family] = $this->familyUser();
        app(FamilyMemberService::class)->create($user, $family, ['full_name' => 'Budi', 'is_alive' => true]);
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/families/{$family->uuid}/gamification")
            ->assertOk()
            ->assertJsonPath('data.points', 10)
            ->assertJsonCount(0, 'data.badges');
    }

    public function test_user_and_family_leaderboards_return_ranked_contributions(): void
    {
        [$user, $family] = $this->familyUser();
        app(FamilyMemberService::class)->create($user, $family, ['full_name' => 'Budi', 'is_alive' => true]);
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/families/{$family->uuid}/leaderboard?limit=10")
            ->assertOk()
            ->assertJsonPath('data.0.rank', 1)
            ->assertJsonPath('data.0.uuid', $user->uuid)
            ->assertJsonPath('data.0.points', 10);

        $this->getJson('/api/v1/leaderboard/families?limit=10')
            ->assertOk()
            ->assertJsonPath('data.0.uuid', $family->uuid)
            ->assertJsonPath('data.0.points', 10);
    }

    public function test_family_gamification_is_forbidden_to_non_members(): void
    {
        $family = Family::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/v1/families/{$family->uuid}/gamification")->assertForbidden();
        $this->getJson("/api/v1/families/{$family->uuid}/leaderboard")->assertForbidden();
    }

    public function test_leaderboard_limit_is_validated(): void
    {
        [$user, $family] = $this->familyUser();
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/families/{$family->uuid}/leaderboard?limit=101")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('limit');
    }

    private function familyUser(): array
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        FamilyUserRole::factory()->create(['family_id' => $family->id, 'user_id' => $user->id]);

        return [$user, $family];
    }
}
