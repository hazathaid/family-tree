<?php

namespace App\Services;

use App\DTOs\ArticleData;
use App\Events\ArticlePublished;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Family;
use App\Models\User;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ArticleService
{
    public function __construct(private readonly ArticleRepositoryInterface $articles, private readonly RichTextSanitizer $sanitizer) {}

    public function create(User $user, ArticleData $data): Article
    {
        $family = Family::query()->where('uuid', $data->familyUuid)->firstOrFail();
        abort_unless($family->userRoles()->where('user_id', $user->id)->exists(), 403);
        $category = ArticleCategory::query()->where('uuid', $data->categoryUuid)->firstOrFail();
        $this->validateInitialStatus($data->status);
        $article = $this->articles->create(['family_id' => $family->id, 'author_id' => $user->id, 'category_id' => $category->id, 'title' => $data->title, 'slug' => $this->uniqueSlug($family, $data->title), 'excerpt' => $data->excerpt, 'content' => $this->sanitizer->sanitize($data->content), 'status' => $data->status, 'published_at' => $data->status === Article::STATUS_PUBLISHED ? now() : null]);
        if ($article->status === Article::STATUS_PUBLISHED) {
            ArticlePublished::dispatch($article);
        }

        return $this->articles->loadDetails($article, $user);
    }

    public function update(Article $article, array $data, User $user): Article
    {
        if (isset($data['category_uuid'])) {
            $data['category_id'] = ArticleCategory::query()->where('uuid', $data['category_uuid'])->firstOrFail()->id;
            unset($data['category_uuid']);
        }
        unset($data['family_uuid']);
        if (isset($data['content'])) {
            $data['content'] = $this->sanitizer->sanitize($data['content']);
        }
        if (isset($data['title']) && $data['title'] !== $article->title) {
            $data['slug'] = $this->uniqueSlug($article->family, $data['title'], $article->id);
        }
        if (($data['status'] ?? null) === Article::STATUS_PUBLISHED && $article->status !== Article::STATUS_PUBLISHED) {
            $data['published_at'] = now();
        }
        if (($data['status'] ?? null) === Article::STATUS_ARCHIVED) {
            $data += ['is_featured' => false, 'featured_at' => null];
        }

        return $this->articles->loadDetails($this->articles->update($article, $data), $user);
    }

    public function publish(Article $article, User $user): Article
    {
        if ($article->status !== Article::STATUS_PUBLISHED) {
            DB::transaction(fn () => $this->articles->update($article, ['status' => Article::STATUS_PUBLISHED, 'published_at' => now()]));
            ArticlePublished::dispatch($article->refresh());
        }

        return $this->articles->loadDetails($article->refresh(), $user);
    }

    public function delete(Article $article): void
    {
        if ($article->featured_image) {
            Storage::disk('public')->delete($article->featured_image);
        } $this->articles->delete($article);
    }

    public function uploadImage(Article $article, UploadedFile $image, User $user): Article
    {
        $old = $article->featured_image;
        $path = $image->store('articles/'.$article->uuid, 'public');
        $this->articles->update($article, ['featured_image' => $path]);
        if ($old) {
            Storage::disk('public')->delete($old);
        }

        return $this->articles->loadDetails($article->refresh(), $user);
    }

    private function validateInitialStatus(string $status): void
    {
        if ($status === Article::STATUS_ARCHIVED) {
            throw ValidationException::withMessages(['status' => ['A new article cannot be archived.']]);
        }
    }

    private function uniqueSlug(Family $family, string $title, ?int $ignore = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 2;
        while ($this->articles->slugExists($family, $slug, $ignore)) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
