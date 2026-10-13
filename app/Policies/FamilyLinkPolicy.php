<?php

namespace App\Policies;

use App\Models\Family;
use App\Models\FamilyLink;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\User;
use App\Repositories\Contracts\FamilyUserRoleRepositoryInterface;

class FamilyLinkPolicy
{
    public function __construct(
        private readonly FamilyUserRoleRepositoryInterface $familyRoles,
    ) {}

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function request(User $user, FamilyMember $member): bool
    {
        return $this->manages($this->role($user, $member->family));
    }

    public function view(User $user, FamilyLink $link): bool
    {
        return $this->role($user, $link->family) !== null
            || $this->role($user, $link->linkedFamily) !== null;
    }

    public function approve(User $user, FamilyLink $link): bool
    {
        return $this->manages($this->role($user, $link->linkedFamily));
    }

    public function revoke(User $user, FamilyLink $link): bool
    {
        return $this->manages($this->role($user, $link->family))
            || $this->manages($this->role($user, $link->linkedFamily));
    }

    private function manages(?string $role): bool
    {
        return in_array($role, [FamilyUserRole::ROLE_OWNER, FamilyUserRole::ROLE_ADMIN], true);
    }

    private function role(User $user, ?Family $family): ?string
    {
        if (! $family instanceof Family) {
            return null;
        }

        return $this->familyRoles->findActive($family, $user)?->role;
    }
}
