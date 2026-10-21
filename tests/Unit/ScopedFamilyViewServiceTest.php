<?php

namespace Tests\Unit;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\FamilyViewGrant;
use App\Models\MemberRelationship;
use App\Models\User;
use App\Services\ScopedFamilyViewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScopedFamilyViewServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_overview_redacts_living_members_except_the_anchor(): void
    {
        [$owner, $family] = $this->familyWithOwner();
        $anchor = $this->member($family, $owner, ['full_name' => 'Siti Aminah', 'is_alive' => true]);
        $livingFather = $this->member($family, $owner, [
            'full_name' => 'Ayah Rahasia',
            'is_alive' => true,
            'gender' => 'male',
            'birth_date' => '1950-01-01',
        ]);
        $deceasedMother = $this->member($family, $owner, [
            'full_name' => 'Ibu Kenangan',
            'is_alive' => false,
            'gender' => 'female',
            'birth_date' => '1955-01-01',
            'death_date' => '2020-01-01',
        ]);
        MemberRelationship::factory()->create([
            'family_id' => $family->id,
            'source_member_id' => $livingFather->id,
            'target_member_id' => $anchor->id,
            'relationship_type' => MemberRelationship::TYPE_FATHER,
        ]);
        MemberRelationship::factory()->create([
            'family_id' => $family->id,
            'source_member_id' => $deceasedMother->id,
            'target_member_id' => $anchor->id,
            'relationship_type' => MemberRelationship::TYPE_MOTHER,
        ]);
        $grant = $this->grant($family, $anchor, $owner);

        $view = app(ScopedFamilyViewService::class)->overview($grant);
        $nodes = collect($view['tree']['nodes'])->keyBy('uuid');

        $anchorNode = $nodes->get($anchor->uuid);
        $this->assertFalse($anchorNode['is_redacted']);
        $this->assertSame('Siti Aminah', $anchorNode['display_name']);

        $fatherNode = $nodes->get($livingFather->uuid);
        $this->assertTrue($fatherNode['is_redacted']);
        $this->assertSame('Anggota Hidup', $fatherNode['display_name']);
        $this->assertSame('Kerabat', $fatherNode['relationship_label']);
        $this->assertNull($fatherNode['birth_year']);
        $this->assertNull($fatherNode['gender']);
        $this->assertNull($fatherNode['profile_photo_url']);
        $this->assertArrayNotHasKey('id', $fatherNode);
        $this->assertArrayNotHasKey('biography', $fatherNode);

        $motherNode = $nodes->get($deceasedMother->uuid);
        $this->assertFalse($motherNode['is_redacted']);
        $this->assertStringContainsString('Ibu Kenangan', $motherNode['display_name']);
        $this->assertSame(1955, $motherNode['birth_year']);
        $this->assertSame(2020, $motherNode['death_year']);

        $this->assertTrue($view['grant']['read_only']);
        $this->assertFalse($view['grant']['can_view_living']);
        $this->assertArrayNotHasKey('id', $view['tree']['edges'][0]);
        $this->assertSame(['source_uuid', 'target_uuid'], array_keys($view['tree']['edges'][0]));
    }

    public function test_tree_respects_requested_depth_and_layout_without_internal_ids(): void
    {
        [$owner, $family] = $this->familyWithOwner();
        $anchor = $this->member($family, $owner);
        $grant = $this->grant($family, $anchor, $owner);

        $view = app(ScopedFamilyViewService::class)->tree($grant, 5, 'radial');

        $this->assertSame(5, $view['tree']['depth']);
        $this->assertSame('radial', $view['tree']['layout']);
        $this->assertSame($anchor->uuid, $view['tree']['root_member_uuid']);
        $this->assertArrayNotHasKey('id', $view['tree']['nodes'][0]);
    }

    /** @return array{0: User, 1: Family} */
    private function familyWithOwner(): array
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $owner->id]);

        return [$owner, $family];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function member(Family $family, User $creator, array $attributes = []): FamilyMember
    {
        return FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $creator->id,
            ...$attributes,
        ]);
    }

    private function grant(Family $family, FamilyMember $anchor, User $owner): FamilyViewGrant
    {
        return FamilyViewGrant::factory()->create([
            'family_id' => $family->id,
            'anchor_member_id' => $anchor->id,
            'granted_by' => $owner->id,
            'grantee_user_id' => User::factory(),
            'invited_email' => 'guest@example.test',
        ]);
    }
}
