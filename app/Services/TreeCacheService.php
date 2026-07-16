<?php

namespace App\Services;

use App\Models\FamilyMember;
use App\Repositories\Contracts\TreeRepositoryInterface;

class TreeCacheService
{
    public function __construct(private readonly TreeRepositoryInterface $repository) {}

    public function get(FamilyMember $root, string $mode, int $depth): ?array
    {
        return $this->repository->cached($root, $mode, $depth);
    }

    public function put(FamilyMember $root, string $mode, int $depth, array $tree): void
    {
        $this->repository->cache($root, $mode, $depth, $tree);
    }

    public function invalidateFamily(int $familyId): void
    {
        $this->repository->invalidateFamily($familyId);
    }
}
