<?php

namespace App\Repositories\Contracts;

use App\Models\PhotoAlbum;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PhotoAlbumRepositoryInterface
{
    public function paginateForUser(User $user, ?string $familyUuid, int $perPage): LengthAwarePaginator;

    public function create(array $attributes): PhotoAlbum;

    public function update(PhotoAlbum $album, array $attributes): PhotoAlbum;

    public function delete(PhotoAlbum $album): void;
}
