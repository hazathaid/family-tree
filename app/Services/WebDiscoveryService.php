<?php

namespace App\Services;

use App\DTOs\SearchCriteria;
use App\Models\Family;
use App\Models\User;
use App\Repositories\Contracts\FamilyMemberRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class WebDiscoveryService
{
    public function __construct(
        private readonly SearchService $search,
        private readonly FamilyMemberRepositoryInterface $members,
    ) {}

    public function search(Family $family, User $user, array $filters): array
    {
        $criteria = new SearchCriteria(
            $filters['keyword'] ?? null,
            $family->uuid,
            null,
            $filters['name'] ?? null,
            $filters['city'] ?? null,
            isset($filters['generation']) ? (int) $filters['generation'] : null,
            $filters['status'] ?? null,
            $filters['root_member_uuid'] ?? null,
            100,
        );
        $result = $this->search->search($user, $criteria);

        return [
            'members' => $this->paginate($result['members'], 'members_page'),
            'articles' => $this->paginate($result['articles'], 'articles_page'),
            'events' => $this->paginate($result['events'], 'events_page'),
            'roots' => $this->members->paginateForFamily($family, [], 100),
        ];
    }

    private function paginate(Collection $items, string $pageName): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage($pageName);

        return new LengthAwarePaginator(
            $items->forPage($page, 10)->values(),
            $items->count(),
            10,
            $page,
            ['path' => request()->url(), 'query' => request()->query(), 'pageName' => $pageName],
        );
    }
}
