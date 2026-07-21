<?php

namespace App\DTOs;

final readonly class SystemHealthData
{
    /** @param array<string, string> $checks */
    public function __construct(
        public string $status,
        public array $checks,
    ) {}
}
