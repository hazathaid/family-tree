<?php

namespace App\Services;

use App\DTOs\WebDashboardData;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\User;
use App\Repositories\Contracts\FamilyDashboardRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class WebDashboardService
{
    public function __construct(
        private readonly FamilyDashboardService $statistics,
        private readonly FamilyDashboardRepositoryInterface $dashboard,
    ) {}

    public function show(Family $family, User $user): WebDashboardData
    {
        $familyData = Cache::remember(
            "web-dashboard:family:{$family->id}",
            now()->addMinutes(5),
            fn (): array => [
                'statistics' => $this->statistics->summary($family),
                'recent_activity' => $this->dashboard->recentActivity($family, 6),
                'upcoming_birthdays' => $this->dashboard->upcomingBirthdays($family, 7, 5),
                'upcoming_events' => $this->dashboard->upcomingEvents($family, 5),
                'recent_members' => $this->dashboard->recentlyAddedMembers($family, 5),
                'facts' => $this->facts($family),
            ],
        );

        return new WebDashboardData(
            statistics: $familyData['statistics'],
            recentActivity: $familyData['recent_activity'],
            upcomingBirthdays: $familyData['upcoming_birthdays'],
            upcomingEvents: $familyData['upcoming_events'],
            notifications: $this->dashboard->recentNotifications($family, $user, 5),
            unreadNotifications: $this->dashboard->unreadNotificationsCount($family, $user),
            recentMembers: $familyData['recent_members'],
            facts: $familyData['facts'],
        );
    }

    private function facts(Family $family): array
    {
        $oldest = $this->dashboard->oldestLivingMember($family);
        $youngest = $this->dashboard->youngestLivingMember($family);

        return array_values(array_filter([
            $family->origin_city ? ['label' => 'Asal keluarga', 'value' => $family->origin_city] : null,
            $oldest instanceof FamilyMember ? ['label' => 'Anggota tertua', 'value' => $oldest->full_name] : null,
            $youngest instanceof FamilyMember ? ['label' => 'Anggota termuda', 'value' => $youngest->full_name] : null,
        ]));
    }
}
