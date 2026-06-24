<?php

namespace App\Services;

use App\Models\FamilyMember;
use App\Models\MemberRelationship;
use App\Repositories\Contracts\RelationshipRepositoryInterface;
use Illuminate\Support\Collection;
use SplQueue;

class RelationshipTraversalService
{
    public function __construct(
        private readonly RelationshipRepositoryInterface $relationships,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function shortestPath(FamilyMember $source, FamilyMember $target): array
    {
        if ($source->family_id !== $target->family_id) {
            return [];
        }

        if ($source->id === $target->id) {
            return [];
        }

        /** @var Collection<int, FamilyMember> $members */
        $members = FamilyMember::query()
            ->select(['id', 'uuid', 'full_name', 'gender', 'birth_date'])
            ->where('family_id', $source->family_id)
            ->get()
            ->keyBy('id');

        $graph = $this->buildGraph($source->family_id, $members);
        $queue = new SplQueue;
        $queue->enqueue($source->id);

        $visited = [$source->id => true];
        $previous = [];

        while (! $queue->isEmpty()) {
            $current = $queue->dequeue();

            foreach ($graph[$current] ?? [] as $edge) {
                $next = $edge['to_member_id'];

                if (isset($visited[$next])) {
                    continue;
                }

                $visited[$next] = true;
                $previous[$next] = [
                    'from_member_id' => $current,
                    'edge' => $edge,
                ];

                if ($next === $target->id) {
                    return $this->reconstructPath($source->id, $target->id, $previous);
                }

                $queue->enqueue($next);
            }
        }

        return [];
    }

    /**
     * @param  Collection<int, FamilyMember>  $members
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function buildGraph(int $familyId, Collection $members): array
    {
        $graph = [];

        foreach ($this->relationships->graphEdgesForFamily($familyId) as $relationship) {
            foreach ($this->normalizedEdges($relationship, $members) as $edge) {
                $graph[$edge['from_member_id']][] = $edge;
            }
        }

        return $graph;
    }

    /**
     * @param  Collection<int, FamilyMember>  $members
     * @return array<int, array<string, mixed>>
     */
    private function normalizedEdges(MemberRelationship $relationship, Collection $members): array
    {
        $source = $members->get($relationship->source_member_id);
        $target = $members->get($relationship->target_member_id);

        if (! $source instanceof FamilyMember || ! $target instanceof FamilyMember) {
            return [];
        }

        return match ($relationship->relationship_type) {
            MemberRelationship::TYPE_FATHER, MemberRelationship::TYPE_MOTHER => [
                $this->edge($target, $source, $relationship->relationship_type, $relationship),
                $this->edge($source, $target, 'child', $relationship),
            ],
            MemberRelationship::TYPE_CHILD => [
                $this->edge($source, $target, $this->parentRelationFor($target), $relationship),
                $this->edge($target, $source, 'child', $relationship),
            ],
            MemberRelationship::TYPE_HUSBAND, MemberRelationship::TYPE_WIFE => [
                $this->edge($source, $target, 'spouse', $relationship),
                $this->edge($target, $source, 'spouse', $relationship),
            ],
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function edge(
        FamilyMember $from,
        FamilyMember $to,
        string $relationship,
        MemberRelationship $sourceRelationship
    ): array {
        return [
            'from_member_id' => $from->id,
            'from_member_uuid' => $from->uuid,
            'from_member_name' => $from->full_name,
            'to_member_id' => $to->id,
            'to_member_uuid' => $to->uuid,
            'to_member_name' => $to->full_name,
            'relationship' => $relationship,
            'relationship_id' => $sourceRelationship->id,
            'relationship_type' => $sourceRelationship->relationship_type,
        ];
    }

    private function parentRelationFor(FamilyMember $member): string
    {
        return match ($member->gender) {
            'male' => 'father',
            'female' => 'mother',
            default => 'parent',
        };
    }

    /**
     * @param  array<int, array{from_member_id: int, edge: array<string, mixed>}>  $previous
     * @return array<int, array<string, mixed>>
     */
    private function reconstructPath(int $sourceMemberId, int $targetMemberId, array $previous): array
    {
        $path = [];
        $current = $targetMemberId;

        while ($current !== $sourceMemberId && isset($previous[$current])) {
            array_unshift($path, $previous[$current]['edge']);
            $current = $previous[$current]['from_member_id'];
        }

        return $path;
    }
}
