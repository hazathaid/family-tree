<?php

namespace App\Repositories\Eloquent;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\User;
use App\Repositories\Contracts\FamilyMemberRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class EloquentFamilyMemberRepository implements FamilyMemberRepositoryInterface
{
    public function create(array $attributes): FamilyMember
    {
        return FamilyMember::query()->create($attributes)->refresh();
    }

    public function update(FamilyMember $member, array $attributes): FamilyMember
    {
        $member->fill($attributes);
        $member->save();

        return $member->refresh();
    }

    public function delete(FamilyMember $member): void
    {
        $member->delete();
    }

    public function findByUuid(string $uuid): ?FamilyMember
    {
        return FamilyMember::query()->where('uuid', $uuid)->first();
    }

    public function paginateForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return FamilyMember::query()
            ->with(['family', 'branch'])
            ->whereHas('family.userRoles', fn ($query) => $query->where('user_id', $user->id))
            ->latest()
            ->paginate($perPage);
    }

    public function paginateForFamily(Family $family, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $sort = $filters['sort'] ?? 'newest';

        return FamilyMember::query()
            ->with('branch')
            ->where('family_id', $family->id)
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('full_name', 'like', '%'.$search.'%')
                        ->orWhere('nickname', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['gender'] ?? null, fn (Builder $query, string $gender) => $query->where('gender', $gender))
            ->when(isset($filters['is_alive']) && $filters['is_alive'] !== '', fn (Builder $query) => $query->where('is_alive', (bool) $filters['is_alive']))
            ->when($filters['branch'] ?? null, fn (Builder $query, string $branch) => $query->whereHas('branch', fn (Builder $query) => $query->where('uuid', $branch)))
            ->when($sort === 'name', fn (Builder $query) => $query->orderBy('full_name'))
            ->when($sort === 'oldest', fn (Builder $query) => $query->oldest())
            ->when(! in_array($sort, ['name', 'oldest'], true), fn (Builder $query) => $query->latest())
            ->paginate($perPage)
            ->withQueryString();
    }
}
