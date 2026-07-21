<?php

namespace App\Repositories\Contracts;

use App\DTOs\ReportCriteria;
use App\Models\Family;

interface ReportRepositoryInterface
{
    public function memberStatistics(Family $family): array;

    public function activityReport(Family $family, ReportCriteria $criteria): array;

    public function webInsights(Family $family, ReportCriteria $criteria): array;
}
