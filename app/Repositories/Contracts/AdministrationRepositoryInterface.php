<?php

namespace App\Repositories\Contracts;

use App\Models\Family;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

interface AdministrationRepositoryInterface
{
    public function paginateFamilies(int $perPage): LengthAwarePaginator;

    public function findFamilyContent(Family $family, string $type, string $uuid): ?Model;

    public function deleteContent(Model $content): void;
}
