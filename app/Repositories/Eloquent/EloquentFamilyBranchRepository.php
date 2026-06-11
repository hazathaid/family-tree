<?php

namespace App\Repositories\Eloquent;

use App\Models\Family;
use App\Models\FamilyBranch;
use App\Repositories\Contracts\FamilyBranchRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentFamilyBranchRepository implements FamilyBranchRepositoryInterface
{
    public function create(array $attributes): FamilyBranch
    {
        return FamilyBranch::query()->create($attributes);
    }

    public function update(FamilyBranch $branch, array $attributes): FamilyBranch
    {
        $branch->fill($attributes);
        $branch->save();

        return $branch->refresh();
    }

    public function delete(FamilyBranch $branch): void
    {
        $branch->delete();
    }

    public function findByUuid(string $uuid): ?FamilyBranch
    {
        return FamilyBranch::query()->where('uuid', $uuid)->first();
    }

    public function paginateForFamily(Family $family, int $perPage = 15): LengthAwarePaginator
    {
        return FamilyBranch::query()
            ->where('family_id', $family->id)
            ->latest()
            ->paginate($perPage);
    }
}
