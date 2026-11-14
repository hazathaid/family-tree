<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\MemberRelationship;
use App\Models\User;
use App\Services\WebOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebFamilyTreeTest extends TestCase
{
    use RefreshDatabase;

    public function test_family_member_can_view_interactive_tree_from_active_family(): void
    {
        [$user, $family] = $this->userWithFamily();
        $root = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id, 'full_name' => 'Budi Anak']);
        $father = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id, 'full_name' => 'Hasan Ayah', 'gender' => 'male']);
        MemberRelationship::factory()->create([
            'family_id' => $family->id,
            'source_member_id' => $father->id,
            'target_member_id' => $root->id,
            'relationship_type' => MemberRelationship::TYPE_FATHER,
        ]);

        $this->active($user, $family)->get(route('tree.index', [
            'root' => $root->uuid, 'mode' => 'ancestor', 'depth' => 3, 'layout' => 'vertical',
        ]))->assertOk()
            ->assertSee('Pohon Keluarga')
            ->assertSee('Budi Anak')
            ->assertSee('Hasan Ayah')
            ->assertSee('Ayah')
            ->assertSee('data-tree-action="zoom-in"', false)
            ->assertSee('tree-member-drawer')
            ->assertSee('data-spouses-url-template', false)
            ->assertSee('data-tree-relative-coparent', false)
            ->assertSee('name="co_parent_uuid"', false);
    }

    public function test_tree_renders_a_divorced_spouse_with_a_shared_child(): void
    {
        [$user, $family] = $this->userWithFamily();
        $father = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id, 'full_name' => 'Ayah', 'gender' => 'male']);
        $mother = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id, 'full_name' => 'Ibu Mantan', 'gender' => 'female']);
        $child = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id, 'full_name' => 'Anak']);
        MemberRelationship::factory()->create([
            'family_id' => $family->id,
            'source_member_id' => $father->id,
            'target_member_id' => $mother->id,
            'relationship_type' => MemberRelationship::TYPE_HUSBAND,
            'start_date' => '2010-01-01',
            'end_date' => '2024-06-01',
            'end_reason' => 'divorced',
        ]);
        MemberRelationship::factory()->create(['family_id' => $family->id, 'source_member_id' => $father->id, 'target_member_id' => $child->id, 'relationship_type' => MemberRelationship::TYPE_FATHER]);
        MemberRelationship::factory()->create(['family_id' => $family->id, 'source_member_id' => $mother->id, 'target_member_id' => $child->id, 'relationship_type' => MemberRelationship::TYPE_MOTHER]);

        $this->active($user, $family)->get(route('tree.index', [
            'root' => $father->uuid, 'mode' => 'descendant', 'depth' => 1, 'layout' => 'vertical',
        ]))->assertOk()
            ->assertSee('Ibu Mantan')
            ->assertSee('Mantan Istri')
            ->assertSee('"is_ended":true', false);
    }

    public function test_tree_rejects_invalid_controls_and_foreign_root(): void
    {
        [$user, $family] = $this->userWithFamily();
        FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id]);
        $foreign = FamilyMember::factory()->create();

        $this->active($user, $family)->get(route('tree.index', ['mode' => 'invalid']))->assertSessionHasErrors('mode');
        $this->active($user, $family)->get(route('tree.index', ['root' => $foreign->uuid]))->assertNotFound();
    }

    public function test_mobile_tree_defaults_to_compact_three_generation_layout(): void
    {
        [$user, $family] = $this->userWithFamily();
        FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id]);

        $this->active($user, $family)->withHeader('User-Agent', 'Mozilla/5.0 (iPhone)')->get(route('tree.index'))
            ->assertOk()->assertSee('value="compact" selected', false)->assertSee('value="3" selected', false);
    }

    public function test_tree_without_members_renders_empty_state(): void
    {
        [$user, $family] = $this->userWithFamily();

        $this->active($user, $family)->get(route('tree.index'))
            ->assertOk()
            ->assertSee('Pohon Keluarga');
    }

    public function test_tree_hides_relationship_labels_when_control_disabled(): void
    {
        [$user, $family] = $this->userWithFamily();
        $root = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id, 'full_name' => 'Budi']);

        $this->active($user, $family)->get(route('tree.index', [
            'root' => $root->uuid, 'show_relationships' => '0',
        ]))->assertOk();
    }

    private function userWithFamily(): array
    {
        $user = User::factory()->create();
        $family = Family::factory()->create();
        FamilyUserRole::factory()->create(['family_id' => $family->id, 'user_id' => $user->id, 'role' => FamilyUserRole::ROLE_MEMBER]);

        return [$user, $family];
    }

    private function active(User $user, Family $family): static
    {
        return $this->withSession([WebOnboardingService::ACTIVE_FAMILY_KEY => $family->uuid])->actingAs($user);
    }
}
