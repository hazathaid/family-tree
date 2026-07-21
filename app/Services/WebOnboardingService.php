<?php

namespace App\Services;

use App\Models\Family;
use App\Models\User;
use App\Repositories\Contracts\FamilyRepositoryInterface;
use Illuminate\Support\Collection;

class WebOnboardingService
{
    public const ACTIVE_FAMILY_KEY = 'active_family_uuid';

    public function __construct(
        private readonly FamilyRepositoryInterface $families,
    ) {}

    public function familiesFor(User $user): Collection
    {
        return $this->families->allForUser($user);
    }

    public function destinationFor(User $user): string
    {
        $families = $this->familiesFor($user);

        if ($families->count() === 1) {
            session([self::ACTIVE_FAMILY_KEY => $families->first()->uuid]);

            return route('dashboard');
        }

        return route('onboarding.index');
    }

    public function activate(Family $family): void
    {
        session([self::ACTIVE_FAMILY_KEY => $family->uuid]);
    }

    public function activeFamilyFor(User $user): ?Family
    {
        $uuid = session(self::ACTIVE_FAMILY_KEY);

        if (! is_string($uuid)) {
            return null;
        }

        $family = $this->families->findByUuid($uuid);

        return $family && $user->can('view', $family) ? $family : null;
    }
}
