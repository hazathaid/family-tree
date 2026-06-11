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
        ];
    }
}
