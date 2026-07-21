<?php

namespace App\Policies;

use App\Models\FamilyUserRole;
use App\Models\MemberPhoto;
use App\Models\User;

class MemberPhotoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->status === 'active';
    }

    public function view(User $user, MemberPhoto $photo): bool
    {
        return $this->role($user, $photo) !== null;
    }

    public function update(User $user, MemberPhoto $photo): bool
    {
        return $photo->uploaded_by === $user->id || in_array($this->role($user, $photo), [FamilyUserRole::ROLE_OWNER, FamilyUserRole::ROLE_ADMIN], true);
    }

    public function delete(User $user, MemberPhoto $photo): bool
    {
        return $this->update($user, $photo);
    }

    private function role(User $user, MemberPhoto $photo): ?string
    {
        return $photo->family->userRoles()->where('user_id', $user->id)->value('role');
    }
}
