<?php

namespace App\Repositories\Contracts;

use App\Models\FamilyMember;

interface TreeRepositoryInterface
{
    public function members(int $familyId): iterable;

    public function relationships(int $familyId): iterable;

    public function cached(FamilyMember $root, string $mode, int $depth): ?array;

    public function cache(FamilyMember $root, string $mode, int $depth, array $tree): void;

    public function invalidateFamily(int $familyId): void;
}
