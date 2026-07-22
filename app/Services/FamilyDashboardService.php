<?php

namespace App\Services;

use App\DTOs\FamilyDashboardData;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\User;
use App\Repositories\Contracts\FamilyDashboardRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class FamilyDashboardService
{
    public function __construct(
        private readonly FamilyDashboardRepositoryInterface $dashboard,
        private readonly FamilyService $families,
    ) {}

    public function summary(Family $family, User $user): FamilyDashboardData
    {
        return Cache::remember($this->families->dashboardCacheKey($family, $user), now()->addMinutes(5), function () use ($family, $user): FamilyDashboardData {
            $oldest = $this->dashboard->oldestLivingMember($family);
            $youngest = $this->dashboard->youngestLivingMember($family);

            return $this->build($family, $user, $oldest, $youngest);
        });
    }

    public function statistics(Family $family): FamilyDashboardData
    {
        return Cache::remember($this->families->dashboardCacheKey($family), now()->addMinutes(5), fn (): FamilyDashboardData => new FamilyDashboardData(
            totalMembers: $this->dashboard->totalMembers($family),
            livingMembers: $this->dashboard->livingMembers($family),
            deceasedMembers: $this->dashboard->deceasedMembers($family),
            totalArticles: $this->dashboard->totalArticles($family),
            totalPhotos: $this->dashboard->totalPhotos($family),
            totalEvents: $this->dashboard->totalEvents($family),
            recentActivity: [], upcomingBirthdays: [], upcomingEvents: [], notifications: [],
            unreadNotifications: 0, familyFacts: [], recentMembers: [],
        ));
    }

    private function build(Family $family, User $user, ?FamilyMember $oldest, ?FamilyMember $youngest): FamilyDashboardData
    {
        return new FamilyDashboardData(
            totalMembers: $this->dashboard->totalMembers($family),
            livingMembers: $this->dashboard->livingMembers($family),
            deceasedMembers: $this->dashboard->deceasedMembers($family),
            totalArticles: $this->dashboard->totalArticles($family),
            totalPhotos: $this->dashboard->totalPhotos($family),
            totalEvents: $this->dashboard->totalEvents($family),
            recentActivity: $this->dashboard->recentActivity($family, 8)->map(fn ($activity): array => [
                'uuid' => $activity->uuid,
                'type' => $activity->activity_type,
                'message' => $activity->payload['name'] ?? $activity->payload['title'] ?? $activity->payload['caption'] ?? 'Aktivitas keluarga',
                'created_at' => $activity->created_at?->toISOString(),
            ])->all(),
            upcomingBirthdays: $this->dashboard->upcomingBirthdays($family, 30, 8)->map(fn (FamilyMember $member): array => $this->member($member) + [
                'next_birthday' => $member->getAttribute('next_birthday'),
            ])->all(),
            upcomingEvents: $this->dashboard->upcomingEvents($family, 5)->map(fn ($event): array => [
                'uuid' => $event->uuid,
                'title' => $event->title,
                'event_date' => $event->event_date?->toISOString(),
                'location' => $event->location,
            ])->all(),
            notifications: $this->dashboard->recentNotifications($family, $user, 5)->map(fn ($notification): array => [
                'uuid' => $notification->uuid,
                'title' => $notification->title,
                'body' => $notification->body,
                'is_read' => $notification->is_read,
                'created_at' => $notification->created_at?->toISOString(),
            ])->all(),
            unreadNotifications: $this->dashboard->unreadNotificationsCount($family, $user),
            familyFacts: [
                'origin_city' => $family->origin_city,
                'oldest_living_member' => $oldest ? $this->member($oldest) : null,
                'youngest_living_member' => $youngest ? $this->member($youngest) : null,
            ],
            recentMembers: $this->dashboard->recentlyAddedMembers($family, 6)->map(fn (FamilyMember $member): array => $this->member($member))->all(),
        );
    }

    private function member(FamilyMember $member): array
    {
        return [
            'uuid' => $member->uuid,
            'full_name' => $member->full_name,
            'birth_date' => $member->birth_date?->toDateString(),
            'is_alive' => $member->is_alive,
            'profile_photo_url' => $member->profile_photo ? asset('storage/'.$member->profile_photo) : null,
        ];
    }
}
