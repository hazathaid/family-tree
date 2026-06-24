<?php

namespace Tests\Performance;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\User;
use App\Services\RelationshipResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RelationshipCachePerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_relationship_cache_benchmarks_large_families(): void
    {
        if (env('RUN_RELATIONSHIP_BENCHMARKS') !== '1') {
            $this->markTestSkipped('Set RUN_RELATIONSHIP_BENCHMARKS=1 to run large relationship cache benchmarks.');
        }

        foreach ([1000, 10000, 100000] as $memberCount) {
            $result = $this->benchmarkFamily($memberCount);

            fwrite(STDERR, sprintf(
                "\nFT-405 benchmark: members=%d cold_ms=%.2f cached_ms=%.2f relationship=%s path_steps=%d",
                $result['members'],
                $result['cold_ms'],
                $result['cached_ms'],
                $result['relationship'],
                $result['path_steps'],
            ));

            $this->assertLessThan(500, $result['cached_ms']);
            $this->assertSame('Ayah', $result['relationship']);
            $this->assertSame(1, $result['path_steps']);
        }
    }

    /**
     * @return array{members: int, cold_ms: float, cached_ms: float, relationship: string|null, path_steps: int}
     */
    private function benchmarkFamily(int $memberCount): array
    {
        DB::table('member_relationship_cache')->delete();
        DB::table('member_relationships')->delete();
        DB::table('family_members')->delete();
        DB::table('families')->delete();
        DB::table('users')->delete();

        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $now = now();

        for ($offset = 1; $offset <= $memberCount; $offset += 1000) {
            $members = [];
            $limit = min($offset + 999, $memberCount);

            for ($id = $offset; $id <= $limit; $id++) {
                $members[] = [
                    'id' => $id,
                    'uuid' => sprintf('benchmark-member-%012d', $id),
                    'family_id' => $family->id,
                    'family_branch_id' => null,
                    'full_name' => 'Benchmark Member '.$id,
                    'nickname' => null,
                    'gender' => $id % 2 === 0 ? 'male' : 'female',
                    'birth_date' => '1980-01-01',
                    'birth_place' => null,
                    'is_alive' => true,
                    'death_date' => null,
                    'death_place' => null,
                    'biography' => null,
                    'profile_photo' => null,
                    'profile_photo_thumbnail' => null,
                    'created_by' => $user->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('family_members')->insert($members);
        }

        for ($offset = 1; $offset < $memberCount; $offset += 1000) {
            $relationships = [];
            $limit = min($offset + 999, $memberCount - 1);

            for ($id = $offset; $id <= $limit; $id++) {
                $relationships[] = [
                    'uuid' => sprintf('benchmark-rel-%015d', $id),
                    'family_id' => $family->id,
                    'source_member_id' => $id,
                    'target_member_id' => $id + 1,
                    'relationship_type' => 'father',
                    'start_date' => null,
                    'end_date' => null,
                    'notes' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
            }

            DB::table('member_relationships')->insert($relationships);
        }

        $source = FamilyMember::query()->findOrFail(2);
        $target = FamilyMember::query()->findOrFail(1);
        $resolver = app(RelationshipResolverService::class);

        $coldStart = hrtime(true);
        $cold = $resolver->resolve($source, $target);
        $coldMs = (hrtime(true) - $coldStart) / 1_000_000;

        $cachedStart = hrtime(true);
        $cached = $resolver->resolve($source, $target);
        $cachedMs = (hrtime(true) - $cachedStart) / 1_000_000;

        return [
            'members' => $memberCount,
            'cold_ms' => $coldMs,
            'cached_ms' => $cachedMs,
            'relationship' => $cached['relationship'],
            'path_steps' => count($cached['path']),
        ];
    }
}
