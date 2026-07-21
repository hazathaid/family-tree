<?php

namespace App\Repositories\Contracts;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\User;
use Illuminate\Support\Collection;

interface FamilyDashboardRepositoryInterface
{
    public function totalMembers(Family $family): int;

    public function livingMembers(Family $family): int;

    public function deceasedMembers(Family $family): int;

    public function totalArticles(Family $family): int;

    public function totalPhotos(Family $family): int;

    public function totalEvents(Family $family): int;

    public function recentActivity(Family $family, int $limit): Collection;

    public function upcomingBirthdays(Family $family, int $days, int $limit): Collection;

    public function upcomingEvents(Family $family, int $limit): Collection;

    public function recentNotifications(Family $family, User $user, int $limit): Collection;

    public function unreadNotificationsCount(Family $family, User $user): int;

    public function recentlyAddedMembers(Family $family, int $limit): Collection;

    public function oldestLivingMember(Family $family): ?FamilyMember;

    public function youngestLivingMember(Family $family): ?FamilyMember;
}
