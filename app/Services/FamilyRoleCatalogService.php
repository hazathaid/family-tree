<?php

namespace App\Services;

use App\Models\FamilyUserRole;
use App\Models\User;
use Spatie\Permission\Models\Role;

class FamilyRoleCatalogService
{
    public function ensureRolesExist(): void
    {
        foreach (['web', 'sanctum'] as $guard) {
            foreach (FamilyUserRole::ROLES as $role) {
                Role::findOrCreate($role, $guard);
            }
        }
    }

    public function syncUserGlobalRoles(User $user): void
    {
        $this->ensureRolesExist();

        $roles = $user->familyRoles()
            ->select('role')
            ->distinct()
            ->pluck('role')
            ->all();

        $user->syncRoles($roles);
    }
}
