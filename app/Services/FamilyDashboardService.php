<?php

namespace App\Services;

use App\DTOs\FamilyDashboardData;
use App\Models\Family;
use App\Repositories\Contracts\FamilyDashboardRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class FamilyDashboardService
{
    public function __construct(
        private readonly FamilyDashboardRepositoryInterface $dashboard,
        private readonly FamilyService $families,
    ) {}

    public function summary(Family $family): FamilyDashboardData
    {
        return Cache::remember($this->families->dashboardCacheKey($family), now()->addMinutes(5), function () use ($family): FamilyDashboardData {
            return new FamilyDashboardData(
                totalMembers: $this->dashboard->totalMembers($family),
                livingMembers: $this->dashboard->livingMembers($family),
                deceasedMembers: $this->dashboard->deceasedMembers($family),
                totalArticles: $this->dashboard->totalArticles($family),
                totalPhotos: $this->dashboard->totalPhotos($family),
                totalEvents: $this->dashboard->totalEvents($family),
            );
        });
    }
}
