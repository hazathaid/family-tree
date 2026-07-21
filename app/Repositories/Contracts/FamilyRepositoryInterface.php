<?php

namespace App\Repositories\Contracts;

use App\Models\Family;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface FamilyRepositoryInterface
{
    public function create(array $attributes): Family;

    public function update(Family $family, array $attributes): Family;

    public function delete(Family $family): void;

    public function findByUuid(string $uuid): ?Family;

    public function paginateForUser(User $user, int $perPage = 15): LengthAwarePaginator;

    public function allForUser(User $user): Collection;

    public function slugExists(string $slug, ?int $ignoreId = null): bool;
}
