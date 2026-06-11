<?php

namespace App\DTOs;

class FamilyRoleData
{
    public function __construct(
        public readonly string $email,
        public readonly string $role,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'],
            role: $data['role'],
        );
    }
}
