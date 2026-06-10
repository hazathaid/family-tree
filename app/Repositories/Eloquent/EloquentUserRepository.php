<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function create(array $attributes): User
    {
        return User::query()->create($attributes);
    }

    public function update(User $user, array $attributes): User
    {
        $user->fill($attributes);
        $user->save();

        return $user->refresh();
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }

    public function findByUuid(string $uuid): ?User
    {
        return User::query()->where('uuid', $uuid)->first();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return User::query()->latest()->paginate($perPage);
    }
}
