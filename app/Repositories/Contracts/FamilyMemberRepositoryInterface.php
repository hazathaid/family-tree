<?php

namespace App\Repositories\Contracts;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface FamilyMemberRepositoryInterface
{
    public function create(array $attributes): FamilyMember;

    public function update(FamilyMember $member, array $attributes): FamilyMember;

    public function delete(FamilyMember $member): void;

    public function findByUuid(string $uuid): ?FamilyMember;

    public function paginateForUser(User $user, int $perPage = 15): LengthAwarePaginator;

    public function paginateForFamily(Family $family, array $filters = [], int $perPage = 15): LengthAwarePaginator;
}
