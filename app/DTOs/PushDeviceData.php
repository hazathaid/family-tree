<?php

namespace App\DTOs;

readonly class PushDeviceData
{
    public function __construct(public string $platform, public string $token) {}

    public static function fromArray(array $data): self
    {
        return new self($data['platform'], $data['token']);
    }
}
