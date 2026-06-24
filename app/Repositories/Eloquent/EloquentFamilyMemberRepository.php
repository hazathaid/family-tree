<?php

namespace App\Repositories\Eloquent;

use App\Models\FamilyMember;
use App\Models\User;
use App\Repositories\Contracts\FamilyMemberRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
}
