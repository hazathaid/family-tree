<?php

namespace App\Repositories\Eloquent;

use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Repositories\Contracts\AccountSessionRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentAccountSessionRepository implements AccountSessionRepositoryInterface
{
    public function forUser(User $user): Collection
    {
        return PersonalAccessToken::query()->whereMorphedTo('tokenable', $user)
            ->latest('last_used_at')->latest('created_at')->get();
    }

    public function findForUser(User $user, string $uuid): ?PersonalAccessToken
    {
        return PersonalAccessToken::query()->whereMorphedTo('tokenable', $user)->where('uuid', $uuid)->first();
    }

    public function revoke(PersonalAccessToken $token): void
    {
        $token->delete();
    }
}
