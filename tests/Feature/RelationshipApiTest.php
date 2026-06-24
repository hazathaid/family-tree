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

class RelationshipApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_view_update_list_and_delete_relationship(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        $father = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id]);
        $mother = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id]);
        $child = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create([
            'family_id' => $family->id,
            'user_id' => $owner->id,
        ]);
        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/v1/relationships', [
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $father->uuid,
            'target_member_uuid' => $child->uuid,
            'relationship_type' => 'father',
            'notes' => 'Ayah biologis.',
        ])->assertCreated()
            ->assertJsonPath('data.source_member_uuid', $father->uuid)
            ->assertJsonPath('data.target_member_uuid', $child->uuid)
            ->assertJsonPath('data.relationship_type', 'father');

        $relationshipUuid = $response->json('data.uuid');

        $this->getJson('/api/v1/relationships/'.$relationshipUuid)
            ->assertOk()
            ->assertJsonPath('data.notes', 'Ayah biologis.');

        $this->getJson('/api/v1/relationships?family_uuid='.$family->uuid.'&member_uuid='.$child->uuid)
            ->assertOk()
            ->assertJsonFragment(['uuid' => $relationshipUuid]);

        $this->putJson('/api/v1/relationships/'.$relationshipUuid, [
            'source_member_uuid' => $mother->uuid,
            'target_member_uuid' => $child->uuid,
            'relationship_type' => 'mother',
            'notes' => 'Ibu biologis.',
        ])->assertOk()
            ->assertJsonPath('data.source_member_uuid', $mother->uuid)
            ->assertJsonPath('data.relationship_type', 'mother')
            ->assertJsonPath('data.notes', 'Ibu biologis.');

        $relationship = MemberRelationship::query()->where('uuid', $relationshipUuid)->firstOrFail();

        $this->deleteJson('/api/v1/relationships/'.$relationshipUuid)
            ->assertOk()
            ->assertJsonPath('message', 'Relationship deleted');

        $this->assertSoftDeleted('member_relationships', ['id' => $relationship->id]);
    }

    public function test_request_rejects_unsupported_relationship_type(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        $source = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id]);
        $target = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create([
            'family_id' => $family->id,
            'user_id' => $owner->id,
        ]);
        Sanctum::actingAs($owner);

        $this->postJson('/api/v1/relationships', [
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $source->uuid,
            'target_member_uuid' => $target->uuid,
            'relationship_type' => 'pakde',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['relationship_type']);
    }

    public function test_member_can_view_but_cannot_create_relationship(): void
    {
        [$owner, $user] = User::factory()->count(2)->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        $source = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id]);
        $target = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id]);
        $relationship = MemberRelationship::factory()->create([
            'family_id' => $family->id,
            'source_member_id' => $source->id,
            'target_member_id' => $target->id,
            'relationship_type' => 'father',
        ]);
        FamilyUserRole::factory()->create([
            'family_id' => $family->id,
            'user_id' => $user->id,
            'role' => FamilyUserRole::ROLE_MEMBER,
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/relationships/'.$relationship->uuid)
            ->assertOk()
            ->assertJsonPath('data.uuid', $relationship->uuid);

        $this->postJson('/api/v1/relationships', [
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $source->uuid,
            'target_member_uuid' => $target->uuid,
            'relationship_type' => 'mother',
        ])->assertForbidden();
    }

    public function test_husband_and_wife_relationships_are_kept_consistent(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        $husband = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id]);
        $wife = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create([
            'family_id' => $family->id,
            'user_id' => $owner->id,
        ]);
        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/v1/relationships', [
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $husband->uuid,
            'target_member_uuid' => $wife->uuid,
            'relationship_type' => 'husband',
        ])->assertCreated();

        $this->assertDatabaseHas('member_relationships', [
            'family_id' => $family->id,
            'source_member_id' => $wife->id,
            'target_member_id' => $husband->id,
            'relationship_type' => 'wife',
            'deleted_at' => null,
        ]);

        $this->deleteJson('/api/v1/relationships/'.$response->json('data.uuid'))
            ->assertOk();

        $this->assertSoftDeleted('member_relationships', [
            'family_id' => $family->id,
            'source_member_id' => $wife->id,
            'target_member_id' => $husband->id,
            'relationship_type' => 'wife',
        ]);
    }

    public function test_owner_can_resolve_relationship_engine_path(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        $grandfather = FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $owner->id,
            'gender' => 'male',
        ]);
        $father = FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $owner->id,
            'gender' => 'male',
        ]);
        $uncle = FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $owner->id,
            'gender' => 'male',
        ]);
        $source = FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $owner->id,
            'gender' => 'male',
        ]);
        $cousin = FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $owner->id,
            'gender' => 'female',
        ]);
        FamilyUserRole::factory()->owner()->create([
            'family_id' => $family->id,
            'user_id' => $owner->id,
        ]);
        MemberRelationship::factory()->create([
            'family_id' => $family->id,
            'source_member_id' => $grandfather->id,
            'target_member_id' => $father->id,
            'relationship_type' => 'father',
        ]);
        MemberRelationship::factory()->create([
            'family_id' => $family->id,
            'source_member_id' => $grandfather->id,
            'target_member_id' => $uncle->id,
            'relationship_type' => 'father',
        ]);
        MemberRelationship::factory()->create([
            'family_id' => $family->id,
            'source_member_id' => $father->id,
            'target_member_id' => $source->id,
            'relationship_type' => 'father',
        ]);
        MemberRelationship::factory()->create([
            'family_id' => $family->id,
            'source_member_id' => $uncle->id,
            'target_member_id' => $cousin->id,
            'relationship_type' => 'father',
        ]);
        Sanctum::actingAs($owner);

        $this->getJson('/api/v1/relationship-engine?source_member_id='.$source->id.'&target_member_id='.$cousin->id)
            ->assertOk()
            ->assertJsonPath('data.relationship', 'Sepupu')
            ->assertJsonCount(4, 'data.path')
            ->assertJsonPath('data.path.0.relationship', 'father')
            ->assertJsonPath('data.path.3.relationship', 'child');
    }
}
