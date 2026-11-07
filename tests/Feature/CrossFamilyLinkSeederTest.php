<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyLink;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\User;
use Database\Seeders\BaniFahrurojiSeeder;
use Database\Seeders\CrossFamilyLinkSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossFamilyLinkSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_links_rani_apriyanti_to_devi_firdaus_fauzi_across_families(): void
    {
        User::factory()->create([
            'email' => 'owner@bani-fahruroji.test',
            'status' => 'active',
        ]);
        $baniOwner = User::query()->where('email', 'owner@bani-fahruroji.test')->firstOrFail();
        config()->set('family-tree.seeders.bani_fahruroji_owner_email', $baniOwner->email);

        $this->seed(BaniFahrurojiSeeder::class);
        $this->seed(CrossFamilyLinkSeeder::class);
        $this->seed(CrossFamilyLinkSeeder::class);

        $family = Family::query()->where('slug', 'hidayat-family')->firstOrFail();
        $bani = Family::query()->where('slug', 'bani-fahruroji')->firstOrFail();

        $owner = User::query()->where('email', 'owner-hidayat@mail.com')->firstOrFail();

        $this->assertTrue(
            FamilyUserRole::query()
                ->where('family_id', $family->id)
                ->where('user_id', $owner->id)
                ->where('role', FamilyUserRole::ROLE_OWNER)
                ->exists()
        );

        $rani = FamilyMember::query()
            ->where('family_id', $family->id)
            ->where('full_name', 'Rani Apriyanti')
            ->firstOrFail();
        $devi = FamilyMember::query()
            ->where('family_id', $bani->id)
            ->where('full_name', 'Devi Firdaus Fauzi')
            ->firstOrFail();

        $this->assertSame(
            1,
            FamilyMember::query()->where('full_name', 'Rani Apriyanti')->count(),
        );

        $this->assertDatabaseCount('family_links', 1);

        $link = FamilyLink::query()->firstOrFail();

        $this->assertSame(FamilyLink::STATUS_ACTIVE, $link->status);
        $this->assertSame($family->id, $link->family_id);
        $this->assertSame($bani->id, $link->linked_family_id);
        $this->assertSame($rani->id, $link->member_id);
        $this->assertSame($devi->id, $link->linked_member_id);
        $this->assertSame($bani->id, $link->children_home_family_id);
        $this->assertSame($owner->id, $link->requested_by);
        $this->assertSame($owner->id, $link->approved_by_family);
        $this->assertSame($baniOwner->id, $link->approved_by_linked_family);
    }
}
