<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Family;
use App\Models\FamilyUserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ArticleApiTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $role = FamilyUserRole::ROLE_MEMBER): array
    {
        $user = User::factory()->create();
        $family = Family::factory()->create();
        FamilyUserRole::factory()->create(['family_id' => $family->id, 'user_id' => $user->id, 'role' => $role]);

        return [$user, $family];
    }

    public function test_member_can_create_publish_comment_like_and_read_article(): void
    {
        [$user,$family] = $this->member();
        $category = ArticleCategory::factory()->create();
        Sanctum::actingAs($user);
        $response = $this->postJson('/api/v1/articles', ['family_uuid' => $family->uuid, 'category_uuid' => $category->uuid, 'title' => 'Sejarah Kami', 'content' => '<p onclick="bad()">Aman<script>bad()</script></p>'])->assertCreated()->assertJsonPath('data.slug', 'sejarah-kami')->assertJsonMissing(['onclick']);
        $article = Article::query()->where('uuid', $response->json('data.uuid'))->firstOrFail();
        $this->postJson('/api/v1/articles/'.$article->uuid.'/publish')->assertOk()->assertJsonPath('data.status', 'published');
        $this->postJson('/api/v1/articles/'.$article->uuid.'/comments', ['comment' => 'Bagus'])->assertCreated();
        $this->postJson('/api/v1/articles/'.$article->uuid.'/like')->assertOk()->assertJsonPath('data.likes_count', 1);
        $this->postJson('/api/v1/articles/'.$article->uuid.'/like')->assertOk()->assertJsonPath('data.likes_count', 1);
        $this->deleteJson('/api/v1/articles/'.$article->uuid.'/like')->assertOk()->assertJsonPath('data.likes_count', 0);
    }

    public function test_outsider_cannot_view_draft(): void
    {
        [$author,$family] = $this->member();
        $article = Article::factory()->create(['family_id' => $family->id, 'author_id' => $author->id]);
        $outsider = User::factory()->create();
        Sanctum::actingAs($outsider);
        $this->getJson('/api/v1/articles/'.$article->uuid)->assertForbidden();
    }

    public function test_admin_can_feature_published_article(): void
    {
        [$admin,$family] = $this->member(FamilyUserRole::ROLE_ADMIN);
        $article = Article::factory()->published()->create(['family_id' => $family->id, 'author_id' => $admin->id]);
        Sanctum::actingAs($admin);
        $this->postJson('/api/v1/articles/'.$article->uuid.'/feature')->assertOk()->assertJsonPath('data.is_featured', true);
        $this->getJson('/api/v1/families/'.$family->uuid.'/articles/featured')->assertOk()->assertJsonPath('data.0.uuid', $article->uuid);
    }

    public function test_validation_uses_standard_envelope(): void
    {
        [$user] = $this->member();
        Sanctum::actingAs($user);
        $this->postJson('/api/v1/articles', [])->assertUnprocessable()->assertJsonPath('success', false)->assertJsonPath('message', 'Validation Error');
    }
}
