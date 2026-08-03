<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\User;
use App\Services\WebOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebTreeRelativeMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_add_parent_spouse_and_child_from_tree_and_creator_is_recorded(): void
    {
        [$owner, $family] = $this->userWithFamily(FamilyUserRole::ROLE_OWNER);
        $member = FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $owner->id,
            'gender' => 'male',
        ]);

        foreach ([
            ['parent', 'Ibu Baru', 'female'],
            ['spouse', 'Pasangan Baru', 'female'],
            ['child', 'Anak Baru', 'male'],
        ] as [$relation, $name, $gender]) {
            $this->active($owner, $family)->post(route('tree.relatives.store', $member), [
                'relation' => $relation,
                'full_name' => $name,
                'gender' => $gender,
                'is_alive' => '1',
            ])->assertRedirect(route('tree.index', ['root' => $member->uuid]));
        }

        $this->assertDatabaseHas('family_members', ['family_id' => $family->id, 'full_name' => 'Ibu Baru', 'created_by' => $owner->id]);
        $this->assertDatabaseHas('family_members', ['family_id' => $family->id, 'full_name' => 'Pasangan Baru', 'created_by' => $owner->id]);
        $this->assertDatabaseHas('family_members', ['family_id' => $family->id, 'full_name' => 'Anak Baru', 'created_by' => $owner->id]);
        $this->assertDatabaseCount('member_relationships', 4); // spouse has an inverse edge
        $this->assertDatabaseHas('activity_logs', ['family_id' => $family->id, 'user_id' => $owner->id, 'activity_type' => ActivityLog::TREE_RELATIVE_CREATED]);
    }

    public function test_linked_member_can_add_relatives_only_from_own_member_node(): void
    {
        [$user, $family] = $this->userWithFamily(FamilyUserRole::ROLE_MEMBER);
        $ownMember = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id, 'user_id' => $user->id, 'gender' => 'female']);
        $otherMember = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id]);

        $this->active($user, $family)->post(route('tree.relatives.store', $ownMember), [
            'relation' => 'child', 'full_name' => 'Anak Saya', 'gender' => 'male', 'is_alive' => '1',
        ])->assertRedirect();

        $this->active($user, $family)->post(route('tree.relatives.store', $otherMember), [
            'relation' => 'child', 'full_name' => 'Tidak Boleh', 'gender' => 'male', 'is_alive' => '1',
        ])->assertForbidden();
    }

    private function userWithFamily(string $role): array
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        FamilyUserRole::factory()->create(['family_id' => $family->id, 'user_id' => $user->id, 'role' => $role]);

        return [$user, $family];
    }

    private function active(User $user, Family $family): static
    {
        return $this->withSession([WebOnboardingService::ACTIVE_FAMILY_KEY => $family->uuid])->actingAs($user);
    }
}
