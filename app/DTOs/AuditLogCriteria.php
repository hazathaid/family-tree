<?php

namespace App\DTOs;

final readonly class AuditLogCriteria
{
    public function __construct(
        public ?string $action,
        public ?string $auditableType,
        public ?string $dateFrom,
        public ?string $dateTo,
        public int $perPage,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['action'] ?? null,
            $data['auditable_type'] ?? null,
            $data['date_from'] ?? null,
            $data['date_to'] ?? null,
            (int) ($data['per_page'] ?? 15),
        );
    }
}
