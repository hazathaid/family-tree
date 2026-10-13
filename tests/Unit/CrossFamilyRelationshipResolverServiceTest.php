<?php

namespace Tests\Unit;

use App\Models\Family;
use App\Models\FamilyLink;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\MemberRelationship;
use App\Models\User;
use App\Services\CrossFamilyRelationshipResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossFamilyRelationshipResolverServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_spouse_across_an_active_link(): void
    {
        [$ownerA, $familyA, $ownerB, $familyB] = $this->twoFamilies();
        $memberA = $this->member($familyA, $ownerA, 'male');
        $memberB = $this->member($familyB, $ownerB, 'female');
        FamilyLink::factory()->active()->create([
            'family_id' => $familyA->id,
            'linked_family_id' => $familyB->id,
            'member_id' => $memberA->id,
            'linked_member_id' => $memberB->id,
        ]);

        $result = app(CrossFamilyRelationshipResolverService::class)->resolve($memberA, $memberB);

        $this->assertTrue($result['connected']);
        $this->assertSame('Istri', $result['relationship']);
        $this->assertCount(1, $result['path']);
        $this->assertSame('spouse', $result['path'][0]['relationship']);
    }

    public function test_resolves_in_law_label_two_hops_across_the_link(): void
    {
        [$ownerA, $familyA, $ownerB, $familyB] = $this->twoFamilies();
        $memberA = $this->member($familyA, $ownerA, 'male');
        $memberB = $this->member($familyB, $ownerB, 'female');
        $fatherB = $this->member($familyB, $ownerB, 'male');
        MemberRelationship::factory()->create([
            'family_id' => $familyB->id,
            'source_member_id' => $fatherB->id,
            'target_member_id' => $memberB->id,
            'relationship_type' => MemberRelationship::TYPE_FATHER,
        ]);
        FamilyLink::factory()->active()->create([
            'family_id' => $familyA->id,
            'linked_family_id' => $familyB->id,
            'member_id' => $memberA->id,
            'linked_member_id' => $memberB->id,
        ]);

        $result = app(CrossFamilyRelationshipResolverService::class)->resolve($memberA, $fatherB);

        $this->assertTrue($result['connected']);
        $this->assertSame('Mertua', $result['relationship']);
        $this->assertSame(['spouse', 'father'], array_column($result['path'], 'relationship'));
    }

    public function test_pending_link_does_not_connect_the_families(): void
    {
        [$ownerA, $familyA, $ownerB, $familyB] = $this->twoFamilies();
        $memberA = $this->member($familyA, $ownerA, 'male');
        $memberB = $this->member($familyB, $ownerB, 'female');
        FamilyLink::factory()->create([
            'family_id' => $familyA->id,
            'linked_family_id' => $familyB->id,
            'member_id' => $memberA->id,
            'linked_member_id' => $memberB->id,
            'status' => FamilyLink::STATUS_PENDING,
        ]);

        $result = app(CrossFamilyRelationshipResolverService::class)->resolve($memberA, $memberB);

        $this->assertFalse($result['connected']);
        $this->assertNull($result['relationship']);
        $this->assertSame([], $result['path']);
    }

    public function test_self_resolves_as_saya(): void
    {
        [$ownerA, $familyA] = $this->twoFamilies();
        $memberA = $this->member($familyA, $ownerA, 'male');

        $result = app(CrossFamilyRelationshipResolverService::class)->resolve($memberA, $memberA);

        $this->assertTrue($result['connected']);
        $this->assertSame('Saya', $result['relationship']);
        $this->assertSame([], $result['path']);
    }

    public function test_disconnected_members_within_different_families_are_not_connected(): void
    {
        [$ownerA, $familyA, $ownerB, $familyB] = $this->twoFamilies();
        $memberA = $this->member($familyA, $ownerA, 'male');
        $memberB = $this->member($familyB, $ownerB, 'female');

        $result = app(CrossFamilyRelationshipResolverService::class)->resolve($memberA, $memberB);

        $this->assertFalse($result['connected']);
        $this->assertFalse(app(CrossFamilyRelationshipResolverService::class)->connected($memberA, $memberB));
    }

    /**
     * @return array{0: User, 1: Family, 2: User, 3: Family}
     */
    private function twoFamilies(): array
    {
        [$ownerA, $familyA] = $this->familyWithOwner();
        [$ownerB, $familyB] = $this->familyWithOwner();

        return [$ownerA, $familyA, $ownerB, $familyB];
    }

    /** @return array{0: User, 1: Family} */
    private function familyWithOwner(): array
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $owner->id]);

        return [$owner, $family];
    }

    private function member(Family $family, User $creator, string $gender): FamilyMember
    {
        return FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $creator->id,
            'gender' => $gender,
        ]);
    }
}
