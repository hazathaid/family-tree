<?php

namespace App\Repositories\Contracts;

use App\Models\Family;
use App\Models\FamilyUserRole;
use App\Models\User;
use Illuminate\Support\Collection;

interface FamilyUserRoleRepositoryInterface
{
    public function create(array $attributes): FamilyUserRole;

    public function update(FamilyUserRole $role, array $attributes): FamilyUserRole;

    public function delete(FamilyUserRole $role): void;

    public function findActive(Family $family, User $user): ?FamilyUserRole;

    public function findByUuid(string $uuid): ?FamilyUserRole;

    public function activeForFamily(Family $family): Collection;

    public function countOwners(Family $family): int;

    public function restoreOrCreate(Family $family, User $user, string $role): FamilyUserRole;
}
