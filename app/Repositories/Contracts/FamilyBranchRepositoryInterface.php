<?php

namespace App\Repositories\Contracts;

use App\Models\Family;
use App\Models\FamilyBranch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface FamilyBranchRepositoryInterface
{
    public function create(array $attributes): FamilyBranch;

    public function update(FamilyBranch $branch, array $attributes): FamilyBranch;

    public function delete(FamilyBranch $branch): void;

    public function findByUuid(string $uuid): ?FamilyBranch;

    public function paginateForFamily(Family $family, int $perPage = 15): LengthAwarePaginator;

    /**
     * @return Collection<int, FamilyBranch>
     */
    public function allForFamily(Family $family): Collection;
}
