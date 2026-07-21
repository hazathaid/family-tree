<?php

namespace Tests\Performance;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\MemberRelationship;
use App\Models\User;
use App\Services\WebOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebFamilyTreePerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_interactive_tree_loads_within_five_seconds_for_documented_fixture(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create();
        FamilyUserRole::factory()->create(['family_id' => $family->id, 'user_id' => $user->id]);
        $root = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id]);

        FamilyMember::factory()->count(100)->create(['family_id' => $family->id, 'created_by' => $user->id])
            ->each(fn (FamilyMember $member) => MemberRelationship::factory()->create([
                'family_id' => $family->id,
                'source_member_id' => $member->id,
                'target_member_id' => $root->id,
                'relationship_type' => MemberRelationship::TYPE_CHILD,
            ]));

        $startedAt = microtime(true);
        $response = $this->withSession([WebOnboardingService::ACTIVE_FAMILY_KEY => $family->uuid])
            ->actingAs($user)->get(route('tree.index', ['root' => $root->uuid, 'depth' => 3]));

        $response->assertOk();
        $this->assertLessThan(5.0, microtime(true) - $startedAt);
    }
}
