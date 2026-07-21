<?php

namespace App\Repositories\Eloquent;

use App\Models\FamilyMember;
use App\Models\MemberRelationship;
use App\Models\MemberTreeCache;
use App\Repositories\Contracts\TreeRepositoryInterface;
use Illuminate\Support\Facades\DB;

class EloquentTreeRepository implements TreeRepositoryInterface
{
    public function members(int $familyId): iterable
    {
        return DB::table('family_members')->select([
            'id', 'uuid', 'full_name', 'nickname', 'gender', 'birth_date', 'death_date', 'is_alive',
            'profile_photo', 'biography',
        ])
            ->where('family_id', $familyId)->whereNull('deleted_at')->orderBy('id')->cursor();
    }

    public function relationships(int $familyId): iterable
    {
        return MemberRelationship::query()->select(['id', 'source_member_id', 'target_member_id', 'relationship_type'])
            ->where('family_id', $familyId)->orderBy('id')->cursor();
    }

    public function cached(FamilyMember $root, string $mode, int $depth): ?array
    {
        $cache = MemberTreeCache::query()->where('member_id', $root->id)->where('mode', $mode)->where('depth', $depth)
            ->where('expires_at', '>', now())->first();

        return $cache?->tree_json;
    }

    public function cache(FamilyMember $root, string $mode, int $depth, array $tree): void
    {
        MemberTreeCache::query()->updateOrCreate(
            ['member_id' => $root->id, 'mode' => $mode, 'depth' => $depth],
            ['family_id' => $root->family_id, 'tree_json' => $tree, 'generated_at' => now(), 'expires_at' => now()->addDay()]
        );
    }

    public function invalidateFamily(int $familyId): void
    {
        MemberTreeCache::query()->where('family_id', $familyId)->delete();
    }
}
