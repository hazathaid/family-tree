<?php

namespace App\Repositories\Contracts;

use App\Models\FamilyViewGrant;
use Illuminate\Support\Collection;

interface FamilyViewGrantRepositoryInterface
{
    public function create(array $attributes): FamilyViewGrant;

    public function update(FamilyViewGrant $grant, array $attributes): FamilyViewGrant;

    public function findByTokenHash(string $tokenHash): ?FamilyViewGrant;

    public function findActiveForFamilyAndUser(int $familyId, int $userId): ?FamilyViewGrant;

    public function listForFamily(int $familyId): Collection;

    public function revokeOpenFor(int $familyId, string $email, ?int $userId): int;

    public function expireDue(): int;
}
