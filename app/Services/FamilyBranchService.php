<?php

namespace App\Services;

use App\DTOs\FamilyBranchData;
use App\Models\Family;
use App\Models\FamilyBranch;
use App\Repositories\Contracts\FamilyBranchRepositoryInterface;
use Illuminate\Validation\ValidationException;

class FamilyBranchService
{
    public function __construct(
        private readonly FamilyBranchRepositoryInterface $branches,
    ) {}

    public function create(Family $family, FamilyBranchData $data): FamilyBranch
    {
        return $this->branches->create([
            ...$data->toAttributes(),
            'family_id' => $family->id,
        ]);
    }

    public function update(Family $family, FamilyBranch $branch, FamilyBranchData $data): FamilyBranch
    {
        $this->ensureBranchBelongsToFamily($family, $branch);

        return $this->branches->update($branch, $data->toAttributes());
    }

    public function delete(Family $family, FamilyBranch $branch): void
    {
        $this->ensureBranchBelongsToFamily($family, $branch);
        $this->branches->delete($branch);
    }

    public function ensureBranchBelongsToFamily(Family $family, FamilyBranch $branch): void
    {
        if ($branch->family_id !== $family->id) {
            throw ValidationException::withMessages([
                'branch' => ['The selected branch does not belong to this family.'],
            ]);
        }
    }
}
