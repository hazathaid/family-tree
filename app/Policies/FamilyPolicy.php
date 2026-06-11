<?php

namespace App\Policies;

use App\Models\Family;
use App\Models\FamilyUserRole;
use App\Models\User;
use App\Repositories\Contracts\FamilyUserRoleRepositoryInterface;

class FamilyPolicy
{
    public function __construct(
        private readonly FamilyUserRoleRepositoryInterface $familyRoles,
    ) {}

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->status === 'active';
    }

    public function view(User $user, Family $family): bool
    {
        return $this->role($user, $family) !== null;
    }

    public function update(User $user, Family $family): bool
    {
        return in_array($this->role($user, $family), [
            FamilyUserRole::ROLE_OWNER,
            FamilyUserRole::ROLE_ADMIN,
        ], true);
    }

    public function delete(User $user, Family $family): bool
    {
        return $this->role($user, $family) === FamilyUserRole::ROLE_OWNER;
    }

    public function manageRoles(User $user, Family $family): bool
    {
        return $this->role($user, $family) === FamilyUserRole::ROLE_OWNER;
    }

    public function manageBranches(User $user, Family $family): bool
    {
        return $this->update($user, $family);
    }

    public function viewDashboard(User $user, Family $family): bool
    {
        return $this->view($user, $family);
    }

    private function role(User $user, Family $family): ?string
    {
        return $this->familyRoles->findActive($family, $user)?->role;
    }
}
