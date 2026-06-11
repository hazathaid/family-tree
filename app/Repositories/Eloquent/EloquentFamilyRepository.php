<?php

namespace App\Repositories\Eloquent;

use App\Models\Family;
use App\Models\User;
use App\Repositories\Contracts\FamilyRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentFamilyRepository implements FamilyRepositoryInterface
{
    public function create(array $attributes): Family
    {
        return Family::query()->create($attributes);
    }

    public function update(Family $family, array $attributes): Family
    {
        $family->fill($attributes);
        $family->save();

        return $family->refresh();
    }

    public function delete(Family $family): void
    {
        $family->delete();
    }

    public function findByUuid(string $uuid): ?Family
    {
        return Family::query()->where('uuid', $uuid)->first();
    }

    public function paginateForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Family::query()
            ->whereHas('userRoles', fn ($query) => $query->where('user_id', $user->id))
            ->latest()
            ->paginate($perPage);
    }

    public function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return Family::query()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }
}
