<?php

namespace App\Policies;

use App\Models\FamilyUserRole;
use App\Models\PhotoAlbum;
use App\Models\User;

class PhotoAlbumPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->status === 'active';
    }

    public function view(User $user, PhotoAlbum $album): bool
    {
        return $this->role($user, $album) !== null;
    }

    public function update(User $user, PhotoAlbum $album): bool
    {
        return $album->created_by === $user->id || in_array($this->role($user, $album), [FamilyUserRole::ROLE_OWNER, FamilyUserRole::ROLE_ADMIN], true);
    }

    public function delete(User $user, PhotoAlbum $album): bool
    {
        return $this->update($user, $album);
    }

    private function role(User $user, PhotoAlbum $album): ?string
    {
        return $album->family->userRoles()->where('user_id', $user->id)->value('role');
    }
}
