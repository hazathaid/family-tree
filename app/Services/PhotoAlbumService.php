<?php

namespace App\Services;

use App\Models\Family;
use App\Models\PhotoAlbum;
use App\Models\User;
use App\Repositories\Contracts\PhotoAlbumRepositoryInterface;

class PhotoAlbumService
{
    public function __construct(private readonly PhotoAlbumRepositoryInterface $albums) {}

    public function create(User $user, array $data): PhotoAlbum
    {
        $family = Family::query()->where('uuid', $data['family_uuid'])->firstOrFail();
        abort_unless($family->userRoles()->where('user_id', $user->id)->exists(), 403);

        return $this->albums->create(['family_id' => $family->id, 'created_by' => $user->id, 'name' => $data['name'], 'description' => $data['description'] ?? null]);
    }

    public function update(PhotoAlbum $album, array $data): PhotoAlbum
    {
        return $this->albums->update($album, $data);
    }

    public function delete(PhotoAlbum $album): void
    {
        $this->albums->delete($album);
    }
}
