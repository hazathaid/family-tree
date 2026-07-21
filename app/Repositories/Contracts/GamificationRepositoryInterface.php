<?php

namespace App\Repositories\Contracts;

use App\Models\Badge;
use App\Models\Family;
use App\Models\PointTransaction;
use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Support\Collection;

interface GamificationRepositoryInterface
{
    public function record(Family $family, User $user, string $action, int $points, string $sourceType, int $sourceId): PointTransaction;

    public function totalPoints(Family $family, User $user): int;

    public function actionCount(Family $family, User $user, string $action): int;

    public function findOrCreateBadge(string $code, string $name, string $description): Badge;

    public function award(Family $family, User $user, Badge $badge): UserBadge;

    public function badges(Family $family, User $user): Collection;

    public function userLeaderboard(Family $family, int $limit): Collection;

    public function familyLeaderboard(int $limit): Collection;
}
