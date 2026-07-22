<?php

namespace App\Services;

use App\Models\FamilyMember;

class TreePresentationService
{
    public function __construct(
        private readonly FamilyTreeService $trees,
        private readonly TreeLayoutService $layouts,
        private readonly RelationshipResolverService $relationships,
    ) {}

    public function present(FamilyMember $root, string $mode, int $depth, string $layout): array
    {
        $tree = $this->layouts->layout($this->trees->generate($root, $mode, $depth), $layout);
        $members = FamilyMember::query()
            ->whereIn('id', array_column($tree['nodes'], 'id'))
            ->get()
            ->keyBy('id');
        $membersByUuid = $members->keyBy('uuid');
        $paths = [$root->uuid => []];

        foreach ($tree['edges'] as $edge) {
            /** @var FamilyMember|null $from */
            $from = $membersByUuid->get($edge['source_uuid']);
            /** @var FamilyMember|null $to */
            $to = $membersByUuid->get($edge['target_uuid']);

            if (! $from instanceof FamilyMember || ! $to instanceof FamilyMember) {
                continue;
            }

            $paths[$to->uuid] = [...($paths[$from->uuid] ?? []), [
                'from_member_id' => $from->id,
                'to_member_id' => $to->id,
                'relationship' => $edge['relationship'],
            ]];
        }

        $tree['nodes'] = array_map(function (array $node) use ($root, $members, $paths, $depth): array {
            /** @var FamilyMember|null $member */
            $member = $members->get($node['id']);
            $node['relationship_to_root'] = $member instanceof FamilyMember
                ? $this->relationships->nameFromPath($root, $member, $paths[$member->uuid] ?? [])
                : null;
            $node['distance'] ??= abs((int) $node['generation']);
            $node['is_boundary'] = $node['distance'] === $depth;

            return $node;
        }, $tree['nodes']);

        return $tree + [
            'expansion' => [
                'strategy' => 'replace_depth',
                'can_expand' => $depth < 20,
                'next_depth' => $depth < 20 ? $depth + 1 : null,
                'can_collapse' => $depth > 1,
                'previous_depth' => $depth > 1 ? $depth - 1 : null,
            ],
        ];
    }
}
