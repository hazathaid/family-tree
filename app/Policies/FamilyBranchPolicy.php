<?php

namespace App\Policies;

use App\Models\FamilyBranch;
use App\Models\FamilyUserRole;
use App\Models\User;
use App\Repositories\Contracts\FamilyUserRoleRepositoryInterface;

class FamilyBranchPolicy
{
    public function __construct(
        private readonly FamilyUserRoleRepositoryInterface $familyRoles,
    ) {}

    public function view(User $user, FamilyBranch $branch): bool
    {
        return $this->familyRoles->findActive($branch->family, $user) !== null;
    }

    public function update(User $user, FamilyBranch $branch): bool
    {
        $role = $this->familyRoles->findActive($branch->family, $user)?->role;

        return in_array($role, [
            FamilyUserRole::ROLE_OWNER,
            FamilyUserRole::ROLE_ADMIN,
        ], true);
    }

    public function delete(User $user, FamilyBranch $branch): bool
    {
        return $this->update($user, $branch);
    }
}
