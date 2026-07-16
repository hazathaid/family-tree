<?php

namespace App\DTOs;

final readonly class ArticleData
{
    public function __construct(public string $familyUuid, public string $categoryUuid, public string $title, public string $content, public string $status = 'draft', public ?string $excerpt = null) {}

    public static function fromArray(array $data): self
    {
        return new self($data['family_uuid'], $data['category_uuid'], $data['title'], $data['content'], $data['status'] ?? 'draft', $data['excerpt'] ?? null);
    }
}
