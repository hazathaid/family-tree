<?php

namespace App\Services;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Repositories\Contracts\FamilyMemberRepositoryInterface;
use Illuminate\Support\Facades\Storage;

class WebTreeViewerService
{
    public function __construct(
        private readonly FamilyMemberRepositoryInterface $members,
        private readonly FamilyTreeService $trees,
        private readonly TreeLayoutService $layouts,
        private readonly RelationshipResolverService $relationships,
    ) {}

    public function present(Family $family, array $filters, bool $compactDefault = false): array
    {
        $memberOptions = $this->members->paginateForFamily($family, [
            'search' => $filters['member_search'] ?? null,
            'sort' => 'name',
        ], 50);
        $root = isset($filters['root'])
            ? $this->members->findByUuid($filters['root'])
            : collect($memberOptions->items())->first();

        abort_if($root instanceof FamilyMember && $root->family_id !== $family->id, 404);

        if (! $root instanceof FamilyMember) {
            return compact('memberOptions') + ['root' => null, 'tree' => null, 'filters' => $filters];
        }

        $mode = $filters['mode'] ?? 'full';
        $depth = (int) ($filters['depth'] ?? ($compactDefault ? 3 : 5));
        $layout = $filters['layout'] ?? ($compactDefault ? 'compact' : 'vertical');
        $tree = $this->layouts->layout($this->trees->generate($root, $mode, $depth), $layout);
        $showRelationships = ($filters['show_relationships'] ?? '1') === '1';

        $tree['nodes'] = array_map(function (array $node) use ($root, $showRelationships): array {
            $member = $this->members->findByUuid($node['uuid']);
            $node['relationship_label'] = $showRelationships && $member instanceof FamilyMember
                ? $this->relationships->resolve($root, $member)['relationship']
                : null;
            $node['profile_photo_url'] = $node['profile_photo'] ? Storage::url($node['profile_photo']) : null;
            unset($node['id'], $node['profile_photo']);

            return $node;
        }, $tree['nodes']);

        return compact('memberOptions', 'root', 'tree') + [
            'filters' => compact('mode', 'depth', 'layout') + $filters,
        ];
    }
}
