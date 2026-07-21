<?php

namespace App\Repositories\Eloquent;

use App\Models\Badge;
use App\Models\Family;
use App\Models\PointTransaction;
use App\Models\User;
use App\Models\UserBadge;
use App\Repositories\Contracts\GamificationRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentGamificationRepository implements GamificationRepositoryInterface
{
    public function record(Family $family, User $user, string $action, int $points, string $sourceType, int $sourceId): PointTransaction
    {
        return PointTransaction::query()->firstOrCreate(
            ['action' => $action, 'source_type' => $sourceType, 'source_id' => $sourceId],
            ['family_id' => $family->id, 'user_id' => $user->id, 'points' => $points],
        );
    }

    public function totalPoints(Family $family, User $user): int
    {
        return (int) PointTransaction::query()->whereBelongsTo($family)->whereBelongsTo($user)->sum('points');
    }

    public function actionCount(Family $family, User $user, string $action): int
    {
        return PointTransaction::query()->whereBelongsTo($family)->whereBelongsTo($user)->where('action', $action)->count();
    }

    public function findOrCreateBadge(string $code, string $name, string $description): Badge
    {
        return Badge::query()->firstOrCreate(['code' => $code], ['name' => $name, 'description' => $description]);
    }

    public function award(Family $family, User $user, Badge $badge): UserBadge
    {
        return UserBadge::query()->firstOrCreate(
            ['family_id' => $family->id, 'user_id' => $user->id, 'badge_id' => $badge->id],
            ['awarded_at' => now()],
        );
    }

    public function badges(Family $family, User $user): Collection
    {
        return UserBadge::query()->with('badge')->whereBelongsTo($family)->whereBelongsTo($user)->orderBy('awarded_at')->get();
    }

    public function userLeaderboard(Family $family, int $limit): Collection
    {
        return DB::table('point_transactions')
            ->join('users', 'users.id', '=', 'point_transactions.user_id')
            ->where('point_transactions.family_id', $family->id)
            ->groupBy('users.id', 'users.uuid', 'users.name', 'users.avatar')
            ->orderByDesc(DB::raw('SUM(point_transactions.points)'))
            ->orderBy('users.name')
            ->limit($limit)
            ->get(['users.uuid', 'users.name', 'users.avatar', DB::raw('SUM(point_transactions.points) AS points')]);
    }

    public function familyLeaderboard(int $limit): Collection
    {
        return DB::table('point_transactions')
            ->join('families', 'families.id', '=', 'point_transactions.family_id')
            ->whereNull('families.deleted_at')
            ->groupBy('families.id', 'families.uuid', 'families.name', 'families.logo')
            ->orderByDesc(DB::raw('SUM(point_transactions.points)'))
            ->orderBy('families.name')
            ->limit($limit)
            ->get(['families.uuid', 'families.name', 'families.logo', DB::raw('SUM(point_transactions.points) AS points')]);
    }
}
