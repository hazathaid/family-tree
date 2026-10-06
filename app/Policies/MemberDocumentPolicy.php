<?php

namespace App\Policies;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\MemberDocument;
use App\Models\User;
use App\Repositories\Contracts\FamilyUserRoleRepositoryInterface;

class MemberDocumentPolicy
{
    public function __construct(
        private readonly FamilyUserRoleRepositoryInterface $familyRoles,
    ) {}

    public function viewAny(User $user, FamilyMember $member): bool
    {
        return $this->role($user, $member->family) !== null;
    }

    public function view(User $user, MemberDocument $document): bool
    {
        return $this->role($user, $document->member->family) !== null;
    }

    public function create(User $user, FamilyMember $member): bool
    {
        if ($member->user_id !== null && $member->user_id === $user->id) {
            return true;
        }

        return in_array($this->role($user, $member->family), [
            FamilyUserRole::ROLE_OWNER,
            FamilyUserRole::ROLE_ADMIN,
        ], true);
    }

    public function delete(User $user, MemberDocument $document): bool
    {
        if ($document->uploaded_by === $user->id) {
            return true;
        }

        return in_array($this->role($user, $document->member->family), [
            FamilyUserRole::ROLE_OWNER,
            FamilyUserRole::ROLE_ADMIN,
        ], true);
    }

    private function role(User $user, Family $family): ?string
    {
        return $this->familyRoles->findActive($family, $user)?->role;
    }
}
