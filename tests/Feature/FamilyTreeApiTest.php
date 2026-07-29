<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\MemberRelationship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FamilyTreeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_family_member_can_generate_and_export_tree(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $member = FamilyMember::factory()->deceased()->create([
            'family_id' => $family->id,
            'created_by' => $user->id,
            'gender' => 'male',
            'religion' => 'islam',
        ]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $user->id]);
        Sanctum::actingAs($user);
        $query = '?member_uuid='.$member->uuid.'&mode=full&depth=5&layout=radial';
        $this->getJson('/api/v1/tree/generate'.$query)
            ->assertOk()
            ->assertJsonPath('data.layout', 'radial')
            ->assertJsonPath('data.statistics.members', 1)
            ->assertJsonFragment([
                'uuid' => $member->uuid,
                'religion' => 'islam',
                'memorial_prefix' => 'Alm. ',
            ]);
        $this->get('/api/v1/tree/export/png'.$query)->assertOk()->assertHeader('content-type', 'image/png');
        $this->get('/api/v1/tree/export/pdf'.$query)->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_tree_request_validates_parameters(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/v1/tree/generate?member_uuid=nope&mode=wrong&depth=21')->assertUnprocessable()
            ->assertJsonValidationErrors(['member_uuid', 'mode', 'depth']);
    }

    public function test_tree_contract_supports_all_layouts_depth_expansion_and_relationship_labels(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $root = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id]);
        $father = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id, 'gender' => 'male']);
        FamilyUserRole::factory()->create(['family_id' => $family->id, 'user_id' => $user->id]);
        MemberRelationship::factory()->create(['family_id' => $family->id, 'source_member_id' => $father->id, 'target_member_id' => $root->id, 'relationship_type' => 'father']);
        Sanctum::actingAs($user);

        foreach (['vertical', 'horizontal', 'radial', 'compact'] as $layout) {
            $this->getJson('/api/v1/tree/generate?'.http_build_query([
                'member_uuid' => $root->uuid,
                'mode' => 'ancestor',
                'depth' => 1,
                'layout' => $layout,
            ]))->assertOk()
                ->assertJsonPath('data.layout', $layout)
                ->assertJsonPath('data.expansion.strategy', 'replace_depth')
                ->assertJsonPath('data.expansion.next_depth', 2)
                ->assertJsonFragment(['uuid' => $root->uuid, 'relationship_to_root' => 'Saya'])
                ->assertJsonFragment(['uuid' => $father->uuid, 'relationship_to_root' => 'Ayah'])
                ->assertJsonMissingPath('data.nodes.0.id');
        }
    }

    public function test_outsider_cannot_generate_or_export_foreign_family_tree(): void
    {
        $outsider = User::factory()->create();
        $member = FamilyMember::factory()->create();
        Sanctum::actingAs($outsider);
        $query = '?member_uuid='.$member->uuid.'&mode=full&depth=2&layout=compact';

        $this->getJson('/api/v1/tree/generate'.$query)->assertForbidden();
        $this->get('/api/v1/tree/export/png'.$query)->assertForbidden();
        $this->get('/api/v1/tree/export/pdf'.$query)->assertForbidden();
    }
}
