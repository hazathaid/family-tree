<?php

namespace App\Repositories\Eloquent;

use App\Models\Family;
use App\Models\FamilyUserRole;
use App\Models\User;
use App\Repositories\Contracts\FamilyUserRoleRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentFamilyUserRoleRepository implements FamilyUserRoleRepositoryInterface
{
    public function create(array $attributes): FamilyUserRole
    {
        return FamilyUserRole::query()->create($attributes);
    }

    public function update(FamilyUserRole $role, array $attributes): FamilyUserRole
    {
        $role->fill($attributes);
        $role->save();

        return $role->refresh();
    }

    public function delete(FamilyUserRole $role): void
    {
        $role->delete();
    }

    public function findActive(Family $family, User $user): ?FamilyUserRole
    {
        return FamilyUserRole::query()
            ->where('family_id', $family->id)
            ->where('user_id', $user->id)
            ->first();
    }

    public function findByUuid(string $uuid): ?FamilyUserRole
    {
        return FamilyUserRole::query()->where('uuid', $uuid)->first();
    }

    public function activeForFamily(Family $family): Collection
    {
        return FamilyUserRole::query()
            ->with('user')
            ->where('family_id', $family->id)
            ->orderBy('role')
            ->orderBy('id')
            ->get();
    }

    public function countOwners(Family $family): int
    {
        return FamilyUserRole::query()
            ->where('family_id', $family->id)
            ->where('role', FamilyUserRole::ROLE_OWNER)
            ->count();
    }

    public function restoreOrCreate(Family $family, User $user, string $role): FamilyUserRole
    {
        $membership = FamilyUserRole::withTrashed()
            ->where('family_id', $family->id)
            ->where('user_id', $user->id)
            ->first();

        if ($membership instanceof FamilyUserRole) {
            $membership->restore();
            $membership->fill(['role' => $role]);
            $membership->save();

            return $membership->refresh();
        }

        return $this->create([
            'family_id' => $family->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);
    }
}
