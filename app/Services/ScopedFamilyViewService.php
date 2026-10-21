<?php

namespace App\Services;

use App\Models\FamilyMember;
use App\Models\FamilyViewGrant;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Builds the redacted, anchor-centred view granted to a pre-marriage visitor.
 *
 * The full tree is always generated inside the target family, then redacted here:
 * living members other than the anchor become anonymous placeholders and no raw
 * edges, paths, photos, biographies, or internal IDs leave this service.
 */
class ScopedFamilyViewService
{
    public function __construct(
        private readonly TreePresentationService $presentation,
        private readonly MemorialNameService $memorialNames,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(FamilyViewGrant $grant): array
    {
        return $this->build($grant, 3, 'vertical');
    }

    /**
     * @return array<string, mixed>
     */
    public function tree(FamilyViewGrant $grant, int $depth = 4, string $layout = 'vertical'): array
    {
        return $this->build($grant, $depth, $layout);
    }

    /**
     * @return array<string, mixed>
     */
    private function build(FamilyViewGrant $grant, int $depth, string $layout): array
    {
        $anchor = $grant->anchorMember;

        if (! $anchor instanceof FamilyMember || $anchor->family_id !== $grant->family_id) {
            throw ValidationException::withMessages(['grant' => ['Anggota acuan untuk akses ini tidak tersedia.']]);
        }

        $anchorUuid = $anchor->uuid;
        $tree = $this->presentation->present($anchor, 'full', $depth, $layout, null);
        $visible = [];

        $nodes = array_map(function (array $node) use ($anchorUuid, &$visible): array {
            $uuid = (string) ($node['uuid'] ?? '');
            $visible[$uuid] = true;
            $redact = (bool) ($node['is_alive'] ?? false) && $uuid !== $anchorUuid;
            $name = (string) ($node['name'] ?? '');
            $prefix = (string) ($node['memorial_prefix'] ?? '');

            return [
                'uuid' => $uuid,
                'display_name' => $redact ? 'Anggota Hidup' : trim($prefix.$name),
                'relationship_label' => $redact ? 'Kerabat' : ($node['relationship_to_root'] ?? null),
                'generation' => (int) ($node['generation'] ?? 0),
                'is_alive' => (bool) ($node['is_alive'] ?? true),
                'is_redacted' => $redact,
                'is_root' => (bool) ($node['is_root'] ?? false),
                'gender' => $redact ? null : ($node['gender'] ?? null),
                'birth_year' => $redact ? null : ($node['birth_year'] ?? null),
                'death_year' => $redact ? null : ($node['death_year'] ?? null),
                'profile_photo_url' => $redact || empty($node['profile_photo'])
                    ? null
                    : Storage::url($node['profile_photo']),
                'position' => [
                    'x' => (int) ($node['position']['x'] ?? 0),
                    'y' => (int) ($node['position']['y'] ?? 0),
                ],
            ];
        }, $tree['nodes']);

        $edges = array_values(array_map(
            fn (array $edge): array => [
                'source_uuid' => $edge['source_uuid'],
                'target_uuid' => $edge['target_uuid'],
            ],
            array_filter(
                $tree['edges'],
                fn (array $edge): bool => isset($visible[$edge['source_uuid']], $visible[$edge['target_uuid']]),
            ),
        ));

        return [
            'family' => [
                'uuid' => $grant->family->uuid,
                'name' => $grant->family->name,
                'origin_city' => $grant->family->origin_city,
                'description' => $grant->family->description,
            ],
            'anchor' => [
                'uuid' => $anchor->uuid,
                'display_name' => $this->memorialNames->prefix($anchor->is_alive, $anchor->gender, $anchor->religion).$anchor->full_name,
            ],
            'grant' => [
                'uuid' => $grant->uuid,
                'expires_at' => $grant->expires_at->toISOString(),
                'can_view_living' => false,
                'read_only' => true,
            ],
            'statistics' => $tree['statistics'],
            'tree' => [
                'root_member_uuid' => $anchorUuid,
                'mode' => 'full',
                'depth' => $depth,
                'layout' => $layout,
                'viewport' => $tree['viewport'],
                'nodes' => $nodes,
                'edges' => $edges,
            ],
        ];
    }
}
