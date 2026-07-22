<?php

namespace App\Services;

use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Repositories\Contracts\AccountSessionRepositoryInterface;
use Illuminate\Support\Collection;

class AccountSessionService
{
    public function __construct(private readonly AccountSessionRepositoryInterface $sessions) {}

    public function list(User $user): Collection
    {
        $currentId = $user->currentAccessToken()?->getKey();

        return $this->sessions->forUser($user)->each(
            fn (PersonalAccessToken $token) => $token->setAttribute('is_current', $token->getKey() === $currentId)
        );
    }

    public function revoke(User $user, string $uuid): bool
    {
        $token = $this->sessions->findForUser($user, $uuid);
        abort_if($token === null, 404);
        $isCurrent = $token->getKey() === $user->currentAccessToken()?->getKey();
        $this->sessions->revoke($token);

        return $isCurrent;
    }
}
