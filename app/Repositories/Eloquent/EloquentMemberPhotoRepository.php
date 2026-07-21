<?php

namespace App\Repositories\Eloquent;

use App\Models\MemberPhoto;
use App\Models\User;
use App\Repositories\Contracts\MemberPhotoRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentMemberPhotoRepository implements MemberPhotoRepositoryInterface
{
    public function paginateForUser(User $user, array $filters, int $perPage): LengthAwarePaginator
    {
        return MemberPhoto::query()->with(['family', 'album', 'uploader', 'taggedMembers'])
            ->whereHas('family.userRoles', fn ($query) => $query->where('user_id', $user->id))
            ->when($filters['family_uuid'] ?? null, fn ($query, $uuid) => $query->whereHas('family', fn ($family) => $family->where('uuid', $uuid)))
            ->when($filters['album_uuid'] ?? null, fn ($query, $uuid) => $query->whereHas('album', fn ($album) => $album->where('uuid', $uuid)))
            ->when($filters['member_uuid'] ?? null, fn ($query, $uuid) => $query->whereHas('taggedMembers', fn ($member) => $member->where('uuid', $uuid)))
            ->latest()->paginate($perPage);
    }

    public function create(array $attributes): MemberPhoto
    {
        return $this->loadDetails(MemberPhoto::query()->create($attributes));
    }

    public function delete(MemberPhoto $photo): void
    {
        $photo->delete();
    }

    public function loadDetails(MemberPhoto $photo): MemberPhoto
    {
        return $photo->load(['family', 'album', 'uploader', 'taggedMembers']);
    }
}
