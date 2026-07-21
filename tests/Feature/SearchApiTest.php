<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Event;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\MemberRelationship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SearchApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_search_returns_members_articles_and_events(): void
    {
        [$user, $family] = $this->familyUser();
        FamilyMember::factory()->create(['family_id' => $family->id, 'full_name' => 'Reuni Santoso']);
        Article::factory()->published()->create(['family_id' => $family->id, 'author_id' => $user->id, 'title' => 'Sejarah Reuni']);
        Event::factory()->create(['family_id' => $family->id, 'organizer_id' => $user->id, 'title' => 'Reuni Akbar']);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/search?keyword=Reuni&family_uuid='.$family->uuid)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.members')
            ->assertJsonCount(1, 'data.articles')
            ->assertJsonCount(1, 'data.events');
    }

    public function test_search_never_returns_content_from_another_family(): void
    {
        [$user] = $this->familyUser();
        FamilyMember::factory()->create(['full_name' => 'Rahasia Keluarga']);
        Article::factory()->published()->create(['title' => 'Rahasia Keluarga']);
        Event::factory()->create(['title' => 'Rahasia Keluarga']);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/search?keyword=Rahasia')
            ->assertOk()
            ->assertJsonCount(0, 'data.members')
            ->assertJsonCount(0, 'data.articles')
            ->assertJsonCount(0, 'data.events');
    }

    public function test_advanced_search_filters_name_city_status_and_generation(): void
    {
        [$user, $family] = $this->familyUser();
        $root = FamilyMember::factory()->create(['family_id' => $family->id, 'full_name' => 'Budi', 'birth_place' => 'Jakarta']);
        $child = FamilyMember::factory()->create(['family_id' => $family->id, 'full_name' => 'Andi Santoso', 'birth_place' => 'Bandung', 'is_alive' => true]);
        FamilyMember::factory()->deceased()->create(['family_id' => $family->id, 'full_name' => 'Andi Lama', 'birth_place' => 'Bandung']);
        MemberRelationship::factory()->create(['family_id' => $family->id, 'source_member_id' => $child->id, 'target_member_id' => $root->id, 'relationship_type' => MemberRelationship::TYPE_CHILD]);
        Sanctum::actingAs($user);

        $query = http_build_query(['family_uuid' => $family->uuid, 'name' => 'Andi', 'city' => 'Bandung', 'status' => 'alive', 'generation' => 1, 'root_member_uuid' => $root->uuid]);
        $this->getJson('/api/v1/search?'.$query)
            ->assertOk()
            ->assertJsonCount(1, 'data.members')
            ->assertJsonPath('data.members.0.uuid', $child->uuid)
            ->assertJsonPath('data.members.0.generation', 1)
            ->assertJsonCount(0, 'data.articles')
            ->assertJsonCount(0, 'data.events');
    }

    public function test_generation_requires_a_root_member(): void
    {
        [$user] = $this->familyUser();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/search?generation=1')->assertUnprocessable()->assertJsonValidationErrors('root_member_uuid');
    }

    private function familyUser(): array
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        FamilyUserRole::factory()->create(['family_id' => $family->id, 'user_id' => $user->id, 'role' => FamilyUserRole::ROLE_MEMBER]);

        return [$user, $family];
    }
}
