<?php

namespace App\Services;

use App\Models\FamilyMember;
use Illuminate\Support\Facades\Storage;

/**
 * Builds the read-only, redacted view of a linked family's tree.
 *
 * Cross-family access is never a raw tree read: the single-family graph is generated
 * first (which may use cache), then this service redacts living members and strips
 * internal identifiers so only the approved link owner sees a safe projection.
 */
class LinkedFamilyTreeService
{
    public function __construct(
        private readonly TreePresentationService $presentation,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function present(FamilyMember $anchor, string $mode, int $depth, string $layout, ?FamilyMember $bridge = null): array
    {
        $tree = $this->presentation->present($anchor, $mode, $depth, $layout, null);

        $anchorUuid = $anchor->uuid;
        $bridgeUuid = $bridge?->uuid;

        $tree['nodes'] = array_map(function (array $node) use ($anchorUuid, $bridgeUuid): array {
            $isAnchor = ($node['uuid'] ?? null) === $anchorUuid;
            $redact = ! $isAnchor && (bool) ($node['is_alive'] ?? false);

            $node['is_bridge'] = ($node['uuid'] ?? null) === $bridgeUuid;
            $node['is_redacted'] = $redact;
            $node['profile_photo_url'] = ! $redact && ($node['profile_photo'] ?? null)
                ? Storage::url($node['profile_photo'])
                : null;

            if ($redact) {
                $node['name'] = 'Anggota Hidup';
                $node['nickname'] = null;
                $node['biography'] = null;
                $node['birth_year'] = null;
                $node['relationship_to_root'] = 'Kerabat';
            }

            $node['relationship_label'] = $node['relationship_to_root'] ?? null;

            unset($node['id'], $node['profile_photo']);

            return $node;
        }, $tree['nodes']);

        $tree['cached'] = $tree['cached'] ?? false;
        $tree['redacted'] = true;
        $tree['read_only'] = true;
        $tree['anchor_member_uuid'] = $anchorUuid;
        $tree['bridge_member_uuid'] = $bridgeUuid;

        return $tree;
    }
}
