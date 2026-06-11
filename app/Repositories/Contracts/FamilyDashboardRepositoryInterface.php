<?php

namespace App\Repositories\Contracts;

use App\Models\Family;

interface FamilyDashboardRepositoryInterface
{
    public function totalMembers(Family $family): int;

    public function livingMembers(Family $family): int;

    public function deceasedMembers(Family $family): int;

    public function totalArticles(Family $family): int;

    public function totalPhotos(Family $family): int;

    public function totalEvents(Family $family): int;
}
