<?php

namespace App\DTOs;

final readonly class EventData
{
    public function __construct(public string $familyUuid, public string $title, public string $eventDate, public ?string $description = null, public ?string $location = null) {}

    public static function fromArray(array $data): self
    {
        return new self($data['family_uuid'], $data['title'], $data['event_date'], $data['description'] ?? null, $data['location'] ?? null);
    }
}
