<?php

namespace App\Repositories\Contracts;

use App\DTOs\SearchCriteria;
use App\Models\FamilyMember;
use App\Models\User;
use Illuminate\Support\Collection;

interface SearchRepositoryInterface
{
    public function members(User $user, SearchCriteria $criteria): Collection;

    public function articles(User $user, SearchCriteria $criteria): Collection;

    public function events(User $user, SearchCriteria $criteria): Collection;

    public function rootMember(User $user, string $uuid): ?FamilyMember;
}
