<?php

namespace App\DTOs;

class FamilyDashboardData
{
    public function __construct(
        public readonly int $totalMembers,
        public readonly int $livingMembers,
        public readonly int $deceasedMembers,
        public readonly int $totalArticles,
        public readonly int $totalPhotos,
        public readonly int $totalEvents,
        public readonly array $recentActivity,
        public readonly array $upcomingBirthdays,
        public readonly array $upcomingEvents,
        public readonly array $notifications,
        public readonly int $unreadNotifications,
        public readonly array $familyFacts,
        public readonly array $recentMembers,
    ) {}

    public function toArray(): array
    {
        return [
            'total_members' => $this->totalMembers,
            'living_members' => $this->livingMembers,
            'deceased_members' => $this->deceasedMembers,
            'total_articles' => $this->totalArticles,
            'total_photos' => $this->totalPhotos,
            'total_events' => $this->totalEvents,
            'recent_activity' => $this->recentActivity,
            'upcoming_birthdays' => $this->upcomingBirthdays,
            'upcoming_events' => $this->upcomingEvents,
            'notification_summary' => [
                'unread_count' => $this->unreadNotifications,
                'recent' => $this->notifications,
            ],
            'family_facts' => $this->familyFacts,
            'recent_members' => $this->recentMembers,
        ];
    }
}
