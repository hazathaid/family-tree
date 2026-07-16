<?php

namespace App\Services;

use App\Models\ArticleCategory;
use App\Repositories\Contracts\ArticleCategoryRepositoryInterface;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ArticleCategoryService
{
    public function __construct(private readonly ArticleCategoryRepositoryInterface $categories) {}

    public function create(array $data): ArticleCategory
    {
        return $this->categories->create([...$data, 'slug' => $this->uniqueSlug($data['name'])]);
    }

    public function update(ArticleCategory $category, array $data): ArticleCategory
    {
        if (isset($data['name']) && $data['name'] !== $category->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $category->id);
        }

        return $this->categories->update($category, $data);
    }

    public function delete(ArticleCategory $category): void
    {
        if ($this->categories->hasArticles($category)) {
            throw ValidationException::withMessages(['category' => ['Category is used by active articles.']]);
        }
        $this->categories->delete($category);
    }

    private function uniqueSlug(string $name, ?int $ignore = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 2;
        while ($this->categories->slugExists($slug, $ignore)) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
