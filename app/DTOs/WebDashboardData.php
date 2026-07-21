<?php

namespace App\DTOs;

use Illuminate\Support\Collection;

class WebDashboardData
{
    public function __construct(
        public readonly FamilyDashboardData $statistics,
        public readonly Collection $recentActivity,
        public readonly Collection $upcomingBirthdays,
        public readonly Collection $upcomingEvents,
        public readonly Collection $notifications,
        public readonly int $unreadNotifications,
        public readonly Collection $recentMembers,
        public readonly array $facts,
    ) {}
}
