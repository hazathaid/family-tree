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
        private readonly TreeGraphBuilderService $graphBuilder,
    ) {}

    public function search(User $user, SearchCriteria $criteria): array
    {
        $members = $this->search->members($user, $criteria);

        if ($criteria->generation !== null) {
            $members = $this->filterGeneration($user, $members, $criteria);
        }

        return [
            'members' => $members,
            'articles' => $this->search->articles($user, $criteria),
            'events' => $this->search->events($user, $criteria),
        ];
    }

    private function filterGeneration(User $user, Collection $members, SearchCriteria $criteria): Collection
    {
        $root = $this->search->rootMember($user, (string) $criteria->rootMemberUuid);
        if (! $root || ($criteria->familyUuid && $root->family->uuid !== $criteria->familyUuid) || ($criteria->familyId && $root->family_id !== $criteria->familyId)) {
            throw ValidationException::withMessages(['root_member_uuid' => ['Root member must belong to the selected family.']]);
        }

        $graph = $this->graphBuilder->build($root->family_id);
        $generations = [$root->id => 0];
        $queue = new \SplQueue;
        $queue->enqueue($root->id);

        while (! $queue->isEmpty()) {
            $current = $queue->dequeue();
            foreach ($graph['adjacency'][$current] ?? [] as $edge) {
                if (isset($generations[$edge['to']])) {
                    continue;
                }
                $generations[$edge['to']] = $generations[$current] + $edge['delta'];
                $queue->enqueue($edge['to']);
            }
        }

        return $members->filter(function ($member) use ($criteria, $generations): bool {
            $generation = $generations[$member->id] ?? null;
            $member->setAttribute('generation', $generation);

            return $generation === $criteria->generation;
        })->take($criteria->limit)->values();
    }
}
