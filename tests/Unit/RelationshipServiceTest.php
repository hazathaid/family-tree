<?php

namespace Tests\Unit;

use App\Models\ActivityLog;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\MemberRelationship;
use App\Models\User;
use App\Services\RelationshipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RelationshipServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_relationship_rejects_second_biological_father(): void
    {
        [$family, $firstFather, $secondFather, $child] = $this->familyWithMembers(3);

        app(RelationshipService::class)->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $firstFather->uuid,
            'target_member_uuid' => $child->uuid,
            'relationship_type' => 'father',
        ]);

        $this->expectException(ValidationException::class);

        app(RelationshipService::class)->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $secondFather->uuid,
            'target_member_uuid' => $child->uuid,
            'relationship_type' => 'father',
        ]);
    }

    public function test_create_relationship_rejects_second_biological_mother(): void
    {
        [$family, $firstMother, $secondMother, $child] = $this->familyWithMembers(3);

        app(RelationshipService::class)->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $firstMother->uuid,
            'target_member_uuid' => $child->uuid,
            'relationship_type' => 'mother',
        ]);

        $this->expectException(ValidationException::class);

        app(RelationshipService::class)->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $secondMother->uuid,
            'target_member_uuid' => $child->uuid,
            'relationship_type' => 'mother',
        ]);
    }

    public function test_create_relationship_rejects_circular_parent_graph(): void
    {
        [$family, $grandparent, $parent, $child] = $this->familyWithMembers(3);

        app(RelationshipService::class)->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $grandparent->uuid,
            'target_member_uuid' => $parent->uuid,
            'relationship_type' => 'father',
        ]);
        app(RelationshipService::class)->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $parent->uuid,
            'target_member_uuid' => $child->uuid,
            'relationship_type' => 'father',
        ]);

        $this->expectException(ValidationException::class);

        app(RelationshipService::class)->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $child->uuid,
            'target_member_uuid' => $grandparent->uuid,
            'relationship_type' => 'father',
        ]);
    }

    public function test_update_relationship_preserves_graph_integrity(): void
    {
        [$family, $grandparent, $parent, $child] = $this->familyWithMembers(3);
        app(RelationshipService::class)->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $grandparent->uuid,
            'target_member_uuid' => $parent->uuid,
            'relationship_type' => 'father',
        ]);
        app(RelationshipService::class)->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $parent->uuid,
            'target_member_uuid' => $child->uuid,
            'relationship_type' => 'father',
        ]);
        $relationship = app(RelationshipService::class)->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $child->uuid,
            'target_member_uuid' => $grandparent->uuid,
            'relationship_type' => 'husband',
        ]);

        $this->expectException(ValidationException::class);

        app(RelationshipService::class)->update($relationship, [
            'relationship_type' => 'father',
        ]);
    }

    public function test_husband_relationship_creates_and_deletes_wife_inverse(): void
    {
        [$family, $husband, $wife] = $this->familyWithMembers(2);

        $relationship = app(RelationshipService::class)->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $husband->uuid,
            'target_member_uuid' => $wife->uuid,
            'relationship_type' => 'husband',
        ]);

        $this->assertDatabaseHas('member_relationships', [
            'family_id' => $family->id,
            'source_member_id' => $wife->id,
            'target_member_id' => $husband->id,
            'relationship_type' => 'wife',
            'deleted_at' => null,
        ]);

        app(RelationshipService::class)->delete($relationship);

        $this->assertSoftDeleted('member_relationships', [
            'family_id' => $family->id,
            'source_member_id' => $wife->id,
            'target_member_id' => $husband->id,
            'relationship_type' => 'wife',
        ]);
    }

    public function test_end_marriage_updates_both_spouse_edges(): void
    {
        [$family, $husband, $wife] = $this->familyWithMembers(2);
        $relationship = app(RelationshipService::class)->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $husband->uuid,
            'target_member_uuid' => $wife->uuid,
            'relationship_type' => 'husband',
            'start_date' => '2010-01-01',
        ]);

        app(RelationshipService::class)->endMarriage($relationship, '2024-06-01', 'divorced');

        $this->assertDatabaseHas('member_relationships', [
            'family_id' => $family->id,
            'source_member_id' => $husband->id,
            'target_member_id' => $wife->id,
            'relationship_type' => 'husband',
            'end_reason' => 'divorced',
        ]);
        $this->assertDatabaseHas('member_relationships', [
            'family_id' => $family->id,
            'source_member_id' => $wife->id,
            'target_member_id' => $husband->id,
            'relationship_type' => 'wife',
            'end_reason' => 'divorced',
        ]);
    }

    public function test_end_marriage_rejects_an_already_ended_marriage(): void
    {
        [$family, $husband, $wife] = $this->familyWithMembers(2);
        $relationship = app(RelationshipService::class)->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $husband->uuid,
            'target_member_uuid' => $wife->uuid,
            'relationship_type' => 'husband',
        ]);

        app(RelationshipService::class)->endMarriage($relationship, '2024-06-01', 'divorced');

        $this->expectException(ValidationException::class);

        app(RelationshipService::class)->endMarriage($relationship->refresh(), '2025-01-01', 'divorced');
    }

    public function test_correct_marriage_end_updates_both_spouse_edges(): void
    {
        [$family, $husband, $wife] = $this->familyWithMembers(2);
        $service = app(RelationshipService::class);
        $relationship = $service->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $husband->uuid,
            'target_member_uuid' => $wife->uuid,
            'relationship_type' => 'husband',
            'start_date' => '2010-01-01',
        ]);
        $service->endMarriage($relationship, '2024-06-01', 'divorced');

        $service->correctMarriageEnd($relationship->refresh(), '2023-12-31', 'widowed');

        $husbandEdge = MemberRelationship::query()
            ->where('source_member_id', $husband->id)
            ->where('target_member_id', $wife->id)
            ->firstOrFail();
        $wifeEdge = MemberRelationship::query()
            ->where('source_member_id', $wife->id)
            ->where('target_member_id', $husband->id)
            ->firstOrFail();

        $this->assertSame('2023-12-31', $husbandEdge->end_date?->toDateString());
        $this->assertSame('widowed', $husbandEdge->end_reason);
        $this->assertSame('2023-12-31', $wifeEdge->end_date?->toDateString());
        $this->assertSame('widowed', $wifeEdge->end_reason);
    }

    public function test_correct_marriage_end_rejects_a_marriage_that_has_not_ended(): void
    {
        [$family, $husband, $wife] = $this->familyWithMembers(2);
        $relationship = app(RelationshipService::class)->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $husband->uuid,
            'target_member_uuid' => $wife->uuid,
            'relationship_type' => 'husband',
        ]);

        $this->expectException(ValidationException::class);

        app(RelationshipService::class)->correctMarriageEnd($relationship, '2024-06-01', 'divorced');
    }

    public function test_correct_marriage_end_rejects_end_date_before_marriage_start(): void
    {
        [$family, $husband, $wife] = $this->familyWithMembers(2);
        $service = app(RelationshipService::class);
        $relationship = $service->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $husband->uuid,
            'target_member_uuid' => $wife->uuid,
            'relationship_type' => 'husband',
            'start_date' => '2010-01-01',
        ]);
        $service->endMarriage($relationship, '2024-06-01', 'divorced');

        $this->expectException(ValidationException::class);

        $service->correctMarriageEnd($relationship->refresh(), '2009-12-31', 'divorced');
    }

    public function test_correct_marriage_end_rejects_invalid_end_reason(): void
    {
        [$family, $husband, $wife] = $this->familyWithMembers(2);
        $service = app(RelationshipService::class);
        $relationship = $service->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $husband->uuid,
            'target_member_uuid' => $wife->uuid,
            'relationship_type' => 'husband',
        ]);
        $service->endMarriage($relationship, '2024-06-01', 'divorced');

        $this->expectException(ValidationException::class);

        $service->correctMarriageEnd($relationship->refresh(), '2024-06-01', 'separated');
    }

    public function test_correct_marriage_end_rejects_a_non_spouse_relationship(): void
    {
        [$family, $father, $child] = $this->familyWithMembers(2);
        $relationship = app(RelationshipService::class)->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $father->uuid,
            'target_member_uuid' => $child->uuid,
            'relationship_type' => 'father',
        ]);

        $this->expectException(ValidationException::class);

        app(RelationshipService::class)->correctMarriageEnd($relationship, '2024-06-01', 'divorced');
    }

    public function test_reopen_marriage_clears_both_spouse_edges_and_keeps_parent_edges(): void
    {
        [$family, $husband, $wife, $user] = $this->familyWithMembers(2);
        $child = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id]);
        $service = app(RelationshipService::class);
        $relationship = $service->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $husband->uuid,
            'target_member_uuid' => $wife->uuid,
            'relationship_type' => 'husband',
            'start_date' => '2010-01-01',
        ]);
        $service->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $husband->uuid,
            'target_member_uuid' => $child->uuid,
            'relationship_type' => 'father',
        ]);
        $service->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $wife->uuid,
            'target_member_uuid' => $child->uuid,
            'relationship_type' => 'mother',
        ]);
        $service->endMarriage($relationship, '2024-06-01', 'divorced');

        $service->reopenMarriage($relationship->refresh(), $user);

        $husbandEdge = MemberRelationship::query()
            ->where('source_member_id', $husband->id)
            ->where('target_member_id', $wife->id)
            ->firstOrFail();
        $wifeEdge = MemberRelationship::query()
            ->where('source_member_id', $wife->id)
            ->where('target_member_id', $husband->id)
            ->firstOrFail();

        $this->assertNull($husbandEdge->end_date);
        $this->assertNull($husbandEdge->end_reason);
        $this->assertNull($wifeEdge->end_date);
        $this->assertNull($wifeEdge->end_reason);
        $this->assertDatabaseHas('member_relationships', [
            'source_member_id' => $husband->id,
            'target_member_id' => $child->id,
            'relationship_type' => 'father',
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('member_relationships', [
            'source_member_id' => $wife->id,
            'target_member_id' => $child->id,
            'relationship_type' => 'mother',
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'family_id' => $family->id,
            'user_id' => $user->id,
            'activity_type' => ActivityLog::MARRIAGE_REOPENED,
        ]);
    }

    public function test_reopen_marriage_rejects_a_marriage_that_has_not_ended(): void
    {
        [$family, $husband, $wife] = $this->familyWithMembers(2);
        $relationship = app(RelationshipService::class)->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $husband->uuid,
            'target_member_uuid' => $wife->uuid,
            'relationship_type' => 'husband',
        ]);

        $this->expectException(ValidationException::class);

        app(RelationshipService::class)->reopenMarriage($relationship);
    }

    public function test_reopen_marriage_rejects_a_non_spouse_relationship(): void
    {
        [$family, $father, $child] = $this->familyWithMembers(2);
        $relationship = app(RelationshipService::class)->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $father->uuid,
            'target_member_uuid' => $child->uuid,
            'relationship_type' => 'father',
        ]);

        $this->expectException(ValidationException::class);

        app(RelationshipService::class)->reopenMarriage($relationship);
    }

    public function test_correct_marriage_end_records_activity_log(): void
    {
        [$family, $husband, $wife, $user] = $this->familyWithMembers(2);
        $service = app(RelationshipService::class);
        $relationship = $service->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $husband->uuid,
            'target_member_uuid' => $wife->uuid,
            'relationship_type' => 'husband',
            'start_date' => '2010-01-01',
        ]);
        $service->endMarriage($relationship, '2024-06-01', 'divorced');

        $service->correctMarriageEnd($relationship->refresh(), '2023-12-31', 'widowed', $user);

        $log = ActivityLog::query()
            ->where('family_id', $family->id)
            ->where('activity_type', ActivityLog::MARRIAGE_END_CORRECTED)
            ->firstOrFail();

        $this->assertSame($user->id, $log->user_id);
        $this->assertSame($relationship->uuid, $log->payload['subject_uuid']);
        $this->assertSame('2024-06-01', $log->payload['previous_end_date']);
        $this->assertSame('divorced', $log->payload['previous_end_reason']);
        $this->assertSame('2023-12-31', $log->payload['end_date']);
        $this->assertSame('widowed', $log->payload['end_reason']);
    }

    public function test_relationship_rejects_member_from_another_family(): void
    {
        [$family, $source] = $this->familyWithMembers(1);
        [, $target] = $this->familyWithMembers(1);

        $this->expectException(ValidationException::class);

        app(RelationshipService::class)->create([
            'family_uuid' => $family->uuid,
            'source_member_uuid' => $source->uuid,
            'target_member_uuid' => $target->uuid,
            'relationship_type' => 'father',
        ]);
    }

    private function familyWithMembers(int $additionalMembers): array
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $members = FamilyMember::factory()
            ->count($additionalMembers)
            ->create([
                'family_id' => $family->id,
                'created_by' => $user->id,
            ])
            ->all();

        return [$family, ...$members, $user];
    }
}
