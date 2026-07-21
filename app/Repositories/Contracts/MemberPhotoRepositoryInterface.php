<?php

namespace App\Repositories\Contracts;

use App\Models\MemberPhoto;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MemberPhotoRepositoryInterface
{
    public function paginateForUser(User $user, array $filters, int $perPage): LengthAwarePaginator;

    public function create(array $attributes): MemberPhoto;

    public function delete(MemberPhoto $photo): void;

    public function loadDetails(MemberPhoto $photo): MemberPhoto;
}
