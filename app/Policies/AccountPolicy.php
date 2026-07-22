<?php

namespace App\Policies;

use App\Models\User;

class AccountPolicy
{
    public function manage(User $actor, User $account): bool
    {
        return $actor->is($account);
    }
}
