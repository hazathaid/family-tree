<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyBranch;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\MemberRelationship;
use App\Models\User;
use Database\Seeders\BaniFahrurojiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BaniFahrurojiSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_drawio_family_idempotently_for_an_existing_owner(): void
    {
        User::factory()->create([
            'email' => 'owner@bani-fahruroji.test',
            'status' => 'active',
        ]);
        $owner = User::query()->where('email', 'owner@bani-fahruroji.test')->firstOrFail();
        config()->set('family-tree.seeders.bani_fahruroji_owner_email', $owner->email);

        $this->seed(BaniFahrurojiSeeder::class);
        $this->seed(BaniFahrurojiSeeder::class);

        $family = Family::query()->where('slug', 'bani-fahruroji')->firstOrFail();

        $this->assertSame(122, FamilyMember::query()->where('family_id', $family->id)->count());
        $this->assertSame(5, FamilyBranch::query()->where('family_id', $family->id)->count());
        $this->assertSame(
            1,
            FamilyUserRole::query()
                ->where('family_id', $family->id)
                ->where('user_id', $owner->id)
                ->where('role', FamilyUserRole::ROLE_OWNER)
                ->count(),
        );
        $this->assertSame(
            234,
            MemberRelationship::query()->where('family_id', $family->id)->count(),
        );
        $this->assertSame(
            234,
            MemberRelationship::query()
                ->where('family_id', $family->id)
                ->distinct('uuid')
                ->count('uuid'),
        );
        $this->assertFalse(
            MemberRelationship::query()
                ->where('family_id', $family->id)
                ->whereNotIn('relationship_type', MemberRelationship::TYPES)
                ->exists(),
        );

        $pipihBranch = FamilyBranch::query()
            ->where('family_id', $family->id)
            ->where('name', 'Cabang Pipih Sopiah')
            ->firstOrFail();
        $najib = FamilyMember::query()
            ->where('family_id', $family->id)
            ->where('full_name', 'Muhammad Najib Al Fatih')
            ->firstOrFail();
        $fahrurozi = FamilyMember::query()
            ->where('family_id', $family->id)
            ->where('full_name', 'KH. Fahrurozi Bin Ama KH Sididiq')
            ->firstOrFail();

        $this->assertSame($pipihBranch->id, $najib->family_branch_id);
        $this->assertNull($fahrurozi->family_branch_id);
    }

    public function test_it_refuses_to_create_production_credentials(): void
    {
        config()->set('family-tree.seeders.bani_fahruroji_owner_email', null);

        $this->expectException(\RuntimeException::class);
        $this->seed(BaniFahrurojiSeeder::class);

        $this->assertDatabaseCount('users', 0);
    }
}
