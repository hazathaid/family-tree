<?php

namespace App\Repositories\Eloquent;

use App\Models\PhotoAlbum;
use App\Models\User;
use App\Repositories\Contracts\PhotoAlbumRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentPhotoAlbumRepository implements PhotoAlbumRepositoryInterface
{
    public function paginateForUser(User $user, ?string $familyUuid, int $perPage): LengthAwarePaginator
    {
        return PhotoAlbum::query()->with(['family', 'creator'])->withCount('photos')
            ->whereHas('family.userRoles', fn ($query) => $query->where('user_id', $user->id))
            ->when($familyUuid, fn ($query) => $query->whereHas('family', fn ($family) => $family->where('uuid', $familyUuid)))
            ->latest()->paginate($perPage);
    }

    public function create(array $attributes): PhotoAlbum
    {
        return PhotoAlbum::query()->create($attributes)->load(['family', 'creator'])->loadCount('photos');
    }

    public function update(PhotoAlbum $album, array $attributes): PhotoAlbum
    {
        $album->update($attributes);

        return $album->refresh()->load(['family', 'creator'])->loadCount('photos');
    }

    public function delete(PhotoAlbum $album): void
    {
        $album->delete();
    }
}
