<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\MemberPhoto;
use App\Models\MemberRelationship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_family_statistics_returns_member_and_generation_totals(): void
    {
        [$user, $family] = $this->familyUser();
        $parent = FamilyMember::factory()->create(['family_id' => $family->id, 'is_alive' => true]);
        $child = FamilyMember::factory()->deceased()->create(['family_id' => $family->id]);
        MemberRelationship::factory()->create([
            'family_id' => $family->id,
            'source_member_id' => $child->id,
            'target_member_id' => $parent->id,
            'relationship_type' => MemberRelationship::TYPE_CHILD,
        ]);
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/families/{$family->uuid}/reports/family-statistics")
            ->assertOk()
            ->assertJsonPath('data.total_members', 2)
            ->assertJsonPath('data.alive_members', 1)
            ->assertJsonPath('data.deceased_members', 1)
            ->assertJsonPath('data.total_generations', 2);
    }

    public function test_activity_report_returns_active_user_upload_and_article_totals(): void
    {
        [$user, $family] = $this->familyUser();
        $contributor = User::factory()->create();
        ActivityLog::factory()->create(['family_id' => $family->id, 'user_id' => $user->id]);
        ActivityLog::factory()->create(['family_id' => $family->id, 'user_id' => $contributor->id]);
        MemberPhoto::factory()->create(['family_id' => $family->id, 'uploaded_by' => $contributor->id]);
        Article::factory()->published()->create(['family_id' => $family->id, 'author_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/families/{$family->uuid}/reports/activity")
            ->assertOk()
            ->assertJsonPath('data.active_users', 2)
            ->assertJsonPath('data.uploads.total', 1)
            ->assertJsonPath('data.uploads.contributors', 1)
            ->assertJsonPath('data.articles.total', 1)
            ->assertJsonPath('data.articles.published', 1);
    }

    public function test_reports_are_forbidden_to_users_outside_the_family(): void
    {
        $family = Family::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/v1/families/{$family->uuid}/reports/family-statistics")->assertForbidden();
    }

    private function familyUser(): array
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        FamilyUserRole::factory()->create(['family_id' => $family->id, 'user_id' => $user->id]);

        return [$user, $family];
    }
}
