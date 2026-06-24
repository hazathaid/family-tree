<?php

namespace App\Policies;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\User;
use App\Repositories\Contracts\FamilyUserRoleRepositoryInterface;

class FamilyMemberPolicy
{
    public function __construct(
        private readonly FamilyUserRoleRepositoryInterface $familyRoles,
    ) {}

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user, Family $family): bool
    {
        return in_array($this->role($user, $family), [
            FamilyUserRole::ROLE_OWNER,
            FamilyUserRole::ROLE_ADMIN,
        ], true);
    }

    public function view(User $user, FamilyMember $member): bool
    {
        return $this->role($user, $member->family) !== null;
    }

    public function update(User $user, FamilyMember $member): bool
    {
        return in_array($this->role($user, $member->family), [
            FamilyUserRole::ROLE_OWNER,
            FamilyUserRole::ROLE_ADMIN,
        ], true);
    }

    public function delete(User $user, FamilyMember $member): bool
    {
        return $this->update($user, $member);
    }

    private function role(User $user, Family $family): ?string
    {
        return $this->familyRoles->findActive($family, $user)?->role;
    }
}
