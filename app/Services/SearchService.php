<?php

namespace App\Services;

use App\DTOs\SearchCriteria;
use App\Models\User;
use App\Repositories\Contracts\SearchRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SearchService
{
    public function __construct(
        private readonly SearchRepositoryInterface $search,
        private readonly GenerationMapService $generations,
    ) {}

    public function search(User $user, SearchCriteria $criteria): array
    {
        $members = $criteria->generation === null
            ? $this->search->members($user, $criteria)
            : $this->generationMembers($user, $criteria);

        return [
            'members' => $members,
            'articles' => $this->search->articles($user, $criteria),
            'events' => $this->search->events($user, $criteria),
        ];
    }

    private function generationMembers(User $user, SearchCriteria $criteria): Collection
    {
        $root = $this->search->rootMember($user, (string) $criteria->rootMemberUuid);
        if (! $root || ($criteria->familyUuid && $root->family->uuid !== $criteria->familyUuid) || ($criteria->familyId && $root->family_id !== $criteria->familyId)) {
            throw ValidationException::withMessages(['root_member_uuid' => ['Root member must belong to the selected family.']]);
        }

        $memberIds = $this->generations->memberIdsAtGeneration($root->family_id, $root->id, (int) $criteria->generation);
        $members = $this->search->membersForIds($user, $criteria, $memberIds);

        return $members->each(fn ($member) => $member->setAttribute('generation', $criteria->generation));
    }
}
