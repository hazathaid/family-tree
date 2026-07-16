<?php

namespace App\Services;

use App\Models\MemberRelationship;
use App\Repositories\Contracts\TreeRepositoryInterface;

class TreeGraphBuilderService
{
    public function __construct(private readonly TreeRepositoryInterface $repository) {}

    public function build(int $familyId): array
    {
        $nodes = $adjacency = [];
        foreach ($this->repository->members($familyId) as $member) {
            $nodes[$member->id] = ['id' => $member->id, 'uuid' => $member->uuid, 'name' => $member->full_name,
                'nickname' => $member->nickname, 'gender' => $member->gender,
                'birth_year' => $member->birth_date ? (int) substr((string) $member->birth_date, 0, 4) : null,
                'is_alive' => (bool) $member->is_alive, 'profile_photo' => $member->profile_photo];
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
                $this->add($adjacency, $source, $target, 'spouse', 0);
                $this->add($adjacency, $target, $source, 'spouse', 0);
            }
        }

        return compact('nodes', 'adjacency');
    }

    private function add(array &$adjacency, int $from, int $to, string $relationship, int $delta): void
    {
        $adjacency[$from][] = compact('from', 'to', 'relationship', 'delta');
    }
}
