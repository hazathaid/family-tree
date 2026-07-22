<?php

namespace App\Repositories\Contracts;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Support\Collection;

interface AccountSessionRepositoryInterface
{
    public function forUser(User $user): Collection;

    public function findForUser(User $user, string $uuid): ?PersonalAccessToken;

    public function revoke(PersonalAccessToken $token): void;
}
