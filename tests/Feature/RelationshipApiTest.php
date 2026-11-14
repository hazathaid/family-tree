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

        $this->getJson('/api/v1/relationship-engine?source_member_uuid='.$source->uuid.'&target_member_uuid='.$cousin->uuid)
            ->assertOk()
            ->assertJsonPath('data.relationship', 'Sepupu')
            ->assertJsonCount(4, 'data.path')
            ->assertJsonPath('data.path.0.relationship', 'father')
            ->assertJsonPath('data.path.3.relationship', 'child')
            ->assertJsonMissingPath('data.path.0.from_member_id')
            ->assertJsonMissingPath('data.path.0.relationship_id');
    }

    public function test_ended_marriage_is_exposed_in_relationship_and_spouses_endpoint(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        $husband = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'male']);
        $wife = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'female']);
        FamilyUserRole::factory()->owner()->create([
            'family_id' => $family->id,
            'user_id' => $owner->id,
        ]);
        Sanctum::actingAs($owner);

        $this->postJson('/api/v1/relationships', [
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $husband->uuid,
            'target_member_uuid' => $wife->uuid,
            'relationship_type' => 'husband',
            'start_date' => '2010-01-01',
            'end_date' => '2024-06-01',
            'end_reason' => 'divorced',
        ])->assertCreated()
            ->assertJsonPath('data.end_reason', 'divorced');

        $this->getJson('/api/v1/family-members/'.$husband->uuid.'/spouses')
            ->assertOk()
            ->assertJsonPath('data.0.spouse_uuid', $wife->uuid)
            ->assertJsonPath('data.0.label', 'Mantan Istri')
            ->assertJsonPath('data.0.is_ended', true);
    }

    public function test_end_reason_is_required_when_marriage_end_date_is_supplied(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        $husband = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'male']);
        $wife = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'female']);
        FamilyUserRole::factory()->owner()->create([
            'family_id' => $family->id,
            'user_id' => $owner->id,
        ]);
        Sanctum::actingAs($owner);

        $this->postJson('/api/v1/relationships', [
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $husband->uuid,
            'target_member_uuid' => $wife->uuid,
            'relationship_type' => 'husband',
            'end_date' => '2024-06-01',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('end_reason');
    }

    public function test_owner_can_end_correct_and_reopen_a_marriage_through_spouse_endpoints(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        $husband = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'male']);
        $wife = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'female']);
        FamilyUserRole::factory()->owner()->create([
            'family_id' => $family->id,
            'user_id' => $owner->id,
        ]);
        Sanctum::actingAs($owner);

        $relationshipUuid = $this->postJson('/api/v1/relationships', [
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $husband->uuid,
            'target_member_uuid' => $wife->uuid,
            'relationship_type' => 'husband',
            'start_date' => '2010-01-01',
        ])->assertCreated()->json('data.uuid');

        $this->getJson('/api/v1/family-members/'.$husband->uuid.'/spouses')
            ->assertOk()
            ->assertJsonPath('data.0.can_end', true)
            ->assertJsonPath('data.0.can_correct', false)
            ->assertJsonPath('data.0.can_reopen', false);

        $this->postJson('/api/v1/family-members/'.$husband->uuid.'/spouses/'.$relationshipUuid.'/end', [
            'end_date' => '2024-06-01',
            'end_reason' => 'divorced',
        ])->assertOk()
            ->assertJsonPath('data.end_date', '2024-06-01')
            ->assertJsonPath('data.end_reason', 'divorced');

        $this->assertDatabaseHas('member_relationships', [
            'source_member_id' => $wife->id,
            'target_member_id' => $husband->id,
            'end_reason' => 'divorced',
        ]);

        $this->getJson('/api/v1/family-members/'.$husband->uuid.'/spouses')
            ->assertOk()
            ->assertJsonPath('data.0.label', 'Mantan Istri')
            ->assertJsonPath('data.0.can_end', false)
            ->assertJsonPath('data.0.can_correct', true)
            ->assertJsonPath('data.0.can_reopen', true);

        $this->patchJson('/api/v1/family-members/'.$husband->uuid.'/spouses/'.$relationshipUuid, [
            'end_date' => '2023-12-31',
            'end_reason' => 'widowed',
        ])->assertOk()
            ->assertJsonPath('data.end_date', '2023-12-31')
            ->assertJsonPath('data.end_reason', 'widowed');

        $this->postJson('/api/v1/family-members/'.$husband->uuid.'/spouses/'.$relationshipUuid.'/reopen')
            ->assertOk()
            ->assertJsonPath('data.end_date', null)
            ->assertJsonPath('data.end_reason', null);

        $this->getJson('/api/v1/family-members/'.$husband->uuid.'/spouses')
            ->assertOk()
            ->assertJsonPath('data.0.label', 'Istri')
            ->assertJsonPath('data.0.can_end', true);
    }

    public function test_marriage_state_conflicts_return_409(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        $husband = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'male']);
        $wife = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'female']);
        FamilyUserRole::factory()->owner()->create([
            'family_id' => $family->id,
            'user_id' => $owner->id,
        ]);
        $marriage = MemberRelationship::factory()->create([
            'family_id' => $family->id,
            'source_member_id' => $husband->id,
            'target_member_id' => $wife->id,
            'relationship_type' => 'husband',
        ]);
        $ended = MemberRelationship::factory()->create([
            'family_id' => $family->id,
            'source_member_id' => $husband->id,
            'target_member_id' => $wife->id,
            'relationship_type' => 'husband',
            'start_date' => '2010-01-01',
            'end_date' => '2024-06-01',
            'end_reason' => 'divorced',
        ]);
        Sanctum::actingAs($owner);

        $this->postJson('/api/v1/family-members/'.$husband->uuid.'/spouses/'.$marriage->uuid.'/reopen')
            ->assertStatus(409);

        $this->postJson('/api/v1/family-members/'.$husband->uuid.'/spouses/'.$ended->uuid.'/end', [
            'end_date' => '2025-01-01',
            'end_reason' => 'divorced',
        ])->assertStatus(409);
    }

    public function test_marriage_endpoints_require_update_permission(): void
    {
        [$owner, $user] = User::factory()->count(2)->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        $husband = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'male']);
        $wife = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'female']);
        $marriage = MemberRelationship::factory()->create([
            'family_id' => $family->id,
            'source_member_id' => $husband->id,
            'target_member_id' => $wife->id,
            'relationship_type' => 'husband',
        ]);
        FamilyUserRole::factory()->create([
            'family_id' => $family->id,
            'user_id' => $user->id,
            'role' => FamilyUserRole::ROLE_MEMBER,
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/family-members/'.$husband->uuid.'/spouses/'.$marriage->uuid.'/end', [
            'end_date' => '2024-06-01',
            'end_reason' => 'divorced',
        ])->assertForbidden();
    }

    public function test_marriage_endpoint_rejects_a_relationship_from_another_member(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        $husband = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'male']);
        $wife = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'female']);
        $outsider = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id]);
        $marriage = MemberRelationship::factory()->create([
            'family_id' => $family->id,
            'source_member_id' => $husband->id,
            'target_member_id' => $wife->id,
            'relationship_type' => 'husband',
        ]);
        FamilyUserRole::factory()->owner()->create([
            'family_id' => $family->id,
            'user_id' => $owner->id,
        ]);
        Sanctum::actingAs($owner);

        $this->postJson('/api/v1/family-members/'.$outsider->uuid.'/spouses/'.$marriage->uuid.'/end', [
            'end_date' => '2024-06-01',
            'end_reason' => 'divorced',
        ])->assertNotFound();
    }

    public function test_marriage_end_endpoint_validates_dates_and_reasons(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        $husband = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'male']);
        $wife = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id, 'gender' => 'female']);
        $marriage = MemberRelationship::factory()->create([
            'family_id' => $family->id,
            'source_member_id' => $husband->id,
            'target_member_id' => $wife->id,
            'relationship_type' => 'husband',
            'start_date' => '2010-01-01',
        ]);
        FamilyUserRole::factory()->owner()->create([
            'family_id' => $family->id,
            'user_id' => $owner->id,
        ]);
        Sanctum::actingAs($owner);

        $this->postJson('/api/v1/family-members/'.$husband->uuid.'/spouses/'.$marriage->uuid.'/end', [
            'end_date' => '2024-06-01',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('end_reason');

        $this->postJson('/api/v1/family-members/'.$husband->uuid.'/spouses/'.$marriage->uuid.'/end', [
            'end_date' => '2009-12-31',
            'end_reason' => 'divorced',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('end_date');
    }
}
