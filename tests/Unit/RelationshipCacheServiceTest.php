<?php

namespace Tests\Unit;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\MemberRelationship;
use App\Models\MemberRelationshipCache;
use App\Models\User;
use App\Services\FamilyMemberService;
use App\Services\RelationshipCacheService;
use App\Services\RelationshipResolverService;
use App\Services\RelationshipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelationshipCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolver_caches_relationship_lookup_and_path(): void
    {
        [$source, $target] = $this->parentGraph();

        $result = app(RelationshipResolverService::class)->resolve($source, $target);

        $this->assertSame('Ayah', $result['relationship']);
        $this->assertCount(1, $result['path']);
        $this->assertDatabaseHas('member_relationship_cache', [
            'family_id' => $source->family_id,
            'source_member_id' => $source->id,
            'target_member_id' => $target->id,
            'relationship_name' => 'Ayah',
            'is_connected' => true,
        ]);

        MemberRelationship::query()->delete();

        $cached = app(RelationshipResolverService::class)->resolve($source, $target);

        $this->assertSame('Ayah', $cached['relationship']);
        $this->assertCount(1, $cached['path']);
    }

    public function test_expired_cache_entry_is_ignored_and_refreshed(): void
    {
        [$source, $target] = $this->parentGraph();

        MemberRelationshipCache::query()->create([
            'family_id' => $source->family_id,
            'source_member_id' => $source->id,
            'target_member_id' => $target->id,
            'relationship_name' => 'Expired',
            'relationship_path' => [],
            'is_connected' => false,
            'expires_at' => now()->subMinute(),
        ]);

        $result = app(RelationshipResolverService::class)->resolve($source, $target);

        $this->assertSame('Ayah', $result['relationship']);
        $this->assertDatabaseHas('member_relationship_cache', [
            'family_id' => $source->family_id,
            'source_member_id' => $source->id,
            'target_member_id' => $target->id,
            'relationship_name' => 'Ayah',
            'is_connected' => true,
        ]);
    }

    public function test_relationship_service_invalidates_family_cache_when_relationship_changes(): void
    {
        [$source, $target] = $this->parentGraph();
        $relationship = MemberRelationship::query()->firstOrFail();

        app(RelationshipResolverService::class)->resolve($source, $target);

        $this->assertDatabaseCount('member_relationship_cache', 1);

        app(RelationshipService::class)->delete($relationship);

        $this->assertDatabaseCount('member_relationship_cache', 0);
    }

    public function test_member_service_invalidates_family_cache_when_member_changes(): void
    {
        [$source, $target, $user] = $this->parentGraph();

        app(RelationshipResolverService::class)->resolve($source, $target);

        $this->assertDatabaseCount('member_relationship_cache', 1);

        app(FamilyMemberService::class)->update($target, [
            'full_name' => 'Updated Father',
            'gender' => 'male',
            'birth_date' => '1970-01-01',
        ]);

        $this->assertDatabaseCount('member_relationship_cache', 0);

        app(FamilyMemberService::class)->create($user, $source->family, [
            'full_name' => 'New Member',
            'gender' => 'female',
            'birth_date' => '2000-01-01',
        ]);

        $this->assertDatabaseCount('member_relationship_cache', 0);
    }

    public function test_cache_service_uses_twenty_four_hour_ttl(): void
    {
        [$source, $target] = $this->parentGraph();

        app(RelationshipCacheService::class)->putResolved($source, $target, 'Ayah', [
            [
                'from_member_id' => $source->id,
                'to_member_id' => $target->id,
                'relationship' => 'father',
            ],
        ]);

        $cache = MemberRelationshipCache::query()->firstOrFail();

        $this->assertTrue($cache->expires_at->between(now()->addHours(23)->addMinutes(59), now()->addHours(24)->addMinute()));
    }

    /**
     * @return array{0: FamilyMember, 1: FamilyMember, 2: User}
     */
    private function parentGraph(): array
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $father = FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $user->id,
            'full_name' => 'Father',
            'gender' => 'male',
        ]);
        $source = FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $user->id,
            'full_name' => 'Child',
            'gender' => 'male',
        ]);

        MemberRelationship::factory()->create([
            'family_id' => $family->id,
            'source_member_id' => $father->id,
            'target_member_id' => $source->id,
            'relationship_type' => 'father',
        ]);

        return [$source, $father, $user];
    }
}
