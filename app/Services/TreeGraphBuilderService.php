<?php

namespace App\Services;

use App\Models\MemberRelationship;
use App\Repositories\Contracts\TreeRepositoryInterface;

class TreeGraphBuilderService
{
    public function __construct(
        private readonly TreeRepositoryInterface $repository,
        private readonly MemorialNameService $memorialNames,
    ) {}

    public function build(int $familyId): array
    {
        $nodes = $adjacency = [];
        foreach ($this->repository->members($familyId) as $member) {
            $nodes[$member->id] = ['id' => $member->id, 'uuid' => $member->uuid, 'name' => $member->full_name,
                'nickname' => $member->nickname, 'gender' => $member->gender,
                'religion' => $member->religion,
                'memorial_prefix' => $this->memorialNames->prefix(
                    (bool) $member->is_alive,
                    $member->gender,
                    $member->religion,
                ),
                'birth_year' => $member->birth_date ? (int) substr((string) $member->birth_date, 0, 4) : null,
                'death_year' => $member->death_date ? (int) substr((string) $member->death_date, 0, 4) : null,
                'is_alive' => (bool) $member->is_alive, 'profile_photo' => $member->profile_photo,
                'biography' => $member->biography];
            $adjacency[$member->id] = [];
        }
        foreach ($this->repository->relationships($familyId) as $relation) {
            if (! isset($nodes[$relation->source_member_id], $nodes[$relation->target_member_id])) {
                continue;
            }
            $source = $relation->source_member_id;
            $target = $relation->target_member_id;
            if (in_array($relation->relationship_type, [MemberRelationship::TYPE_FATHER, MemberRelationship::TYPE_MOTHER], true)) {
                $this->add($adjacency, $target, $source, $relation->relationship_type, -1);
                $this->add($adjacency, $source, $target, 'child', 1);
            } elseif ($relation->relationship_type === MemberRelationship::TYPE_CHILD) {
                $parent = $nodes[$target]['gender'] === 'female' ? 'mother' : 'father';
                $this->add($adjacency, $source, $target, $parent, -1);
                $this->add($adjacency, $target, $source, 'child', 1);
            } else {
                $this->add($adjacency, $source, $target, 'spouse', 0, $relation, $nodes[$target]);
                $this->add($adjacency, $target, $source, 'spouse', 0, $relation, $nodes[$source]);
            }
        }

        return compact('nodes', 'adjacency');
    }

    private function add(
        array &$adjacency,
        int $from,
        int $to,
        string $relationship,
        int $delta,
        ?MemberRelationship $relation = null,
        ?array $targetNode = null,
    ): void {
        $edge = compact('from', 'to', 'relationship', 'delta');

        if ($relationship === 'spouse' && $relation !== null) {
            $edge['end_date'] = $relation->end_date?->toDateString();
            $edge['end_reason'] = $relation->end_reason;
            $edge['is_ended'] = $relation->end_date !== null;
            $edge['label'] = $this->spouseLabel($targetNode['gender'] ?? null, $relation);
        }

        $adjacency[$from][] = $edge;
    }

    private function spouseLabel(?string $gender, MemberRelationship $relation): string
    {
        $divorced = $relation->end_date !== null
            && $relation->end_reason === MemberRelationship::END_REASON_DIVORCED;

        return match (true) {
            $gender === 'male' => $divorced ? 'Mantan Suami' : 'Suami',
            $gender === 'female' => $divorced ? 'Mantan Istri' : 'Istri',
            default => $divorced ? 'Mantan Pasangan' : 'Pasangan',
        };
    }
}
