<?php

namespace App\Repositories\Contracts;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface FamilyMemberRepositoryInterface
{
    public function create(array $attributes): FamilyMember;

    public function update(FamilyMember $member, array $attributes): FamilyMember;

    public function delete(FamilyMember $member): void;

    public function findByUuid(string $uuid): ?FamilyMember;

    public function findForUserInFamily(User $user, Family $family): ?FamilyMember;

    public function paginateForUser(User $user, int $perPage = 15): LengthAwarePaginator;

    public function paginateForFamily(Family $family, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * @return Collection<int, FamilyMember>
     */
    public function allForFamily(Family $family): Collection;

    /**
     * @return iterable<int, FamilyMember>
     */
    public function cursorForFamily(Family $family): iterable;
}
