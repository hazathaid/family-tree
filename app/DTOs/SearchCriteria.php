<?php

namespace App\DTOs;

final readonly class SearchCriteria
{
    public function __construct(
        public ?string $keyword,
        public ?string $familyUuid,
        public ?int $familyId,
        public ?string $name,
        public ?string $city,
        public ?int $generation,
        public ?string $status,
        public ?string $rootMemberUuid,
        public int $limit,
    ) {}
}
