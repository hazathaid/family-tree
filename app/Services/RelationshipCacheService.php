<?php

namespace App\Services;

use App\Models\FamilyMember;
use App\Models\MemberRelationshipCache;
use Carbon\CarbonInterface;

class RelationshipCacheService
{
    private const TTL_HOURS = 24;

    /**
     * @return array{relationship: string|null, path: array<int, array<string, mixed>>, is_connected: bool}|null
     */
    public function get(FamilyMember $source, FamilyMember $target): ?array
    {
        $cache = MemberRelationshipCache::query()
            ->where('family_id', $source->family_id)
            ->where('source_member_id', $source->id)
            ->where('target_member_id', $target->id)
            ->where('expires_at', '>', now())
            ->first();

        if (! $cache instanceof MemberRelationshipCache) {
            return null;
        }

        return [
            'relationship' => $cache->relationship_name,
            'path' => $cache->relationship_path ?? [],
            'is_connected' => $cache->is_connected,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function getPath(FamilyMember $source, FamilyMember $target): ?array
    {
        $cache = $this->get($source, $target);

        return $cache === null ? null : $cache['path'];
    }

    /**
     * @param  array<int, array<string, mixed>>  $path
     */
    public function putPath(FamilyMember $source, FamilyMember $target, array $path): void
    {
        $existing = $this->findAny($source, $target);

        $this->put(
            $source,
            $target,
            $existing?->relationship_name,
            $path,
            $path !== []
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $path
     */
    public function putResolved(FamilyMember $source, FamilyMember $target, ?string $relationship, array $path): void
    {
        $this->put($source, $target, $relationship, $path, $path !== []);
    }

    public function invalidateFamily(int $familyId): void
    {
        MemberRelationshipCache::query()
            ->where('family_id', $familyId)
            ->delete();
    }

    public function invalidateMember(FamilyMember $member): void
    {
        $this->invalidateFamily($member->family_id);
    }

    public function forgetExpired(): void
    {
        MemberRelationshipCache::query()
            ->where('expires_at', '<=', now())
            ->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $path
     */
    private function put(
        FamilyMember $source,
        FamilyMember $target,
        ?string $relationship,
        array $path,
        bool $isConnected
    ): void {
        MemberRelationshipCache::query()->updateOrCreate(
            [
                'family_id' => $source->family_id,
                'source_member_id' => $source->id,
                'target_member_id' => $target->id,
            ],
            [
                'relationship_name' => $relationship,
                'relationship_path' => $path,
                'is_connected' => $isConnected,
                'expires_at' => $this->expiresAt(),
            ]
        );
    }

    private function findAny(FamilyMember $source, FamilyMember $target): ?MemberRelationshipCache
    {
        return MemberRelationshipCache::query()
            ->where('family_id', $source->family_id)
            ->where('source_member_id', $source->id)
            ->where('target_member_id', $target->id)
            ->first();
    }

    private function expiresAt(): CarbonInterface
    {
        return now()->addHours(self::TTL_HOURS);
    }
}
