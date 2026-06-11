<?php

namespace App\DTOs;

class FamilyData
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?string $originCity,
        public readonly ?string $logo,
        public readonly ?string $coverImage,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            description: $data['description'] ?? null,
            originCity: $data['origin_city'] ?? null,
            logo: $data['logo'] ?? null,
            coverImage: $data['cover_image'] ?? null,
        );
    }

    public function toAttributes(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'origin_city' => $this->originCity,
            'logo' => $this->logo,
            'cover_image' => $this->coverImage,
        ];
    }
}
