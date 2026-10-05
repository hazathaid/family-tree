<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\MemberPhoto;
use App\Models\MemberRelationship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MemberMergeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_detect_duplicate_candidates(): void
    {
        [$owner, $family] = $this->familyUser(FamilyUserRole::ROLE_OWNER);
        $this->member($family, $owner, 'Budi Santoso');
        $this->member($family, $owner, 'BUDI  santoso.');
        Sanctum::actingAs($owner);

        $response = $this->getJson('/api/v1/families/'.$family->uuid.'/members/duplicates')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        $this->assertContains('same_name', $response->json('data.0.reasons'));
    }

    public function test_merge_is_restricted_to_owner_and_admin(): void
    {
        [$member, $family] = $this->familyUser(FamilyUserRole::ROLE_MEMBER);
        $primary = $this->member($family, $member, 'Budi');
        $duplicate = $this->member($family, $member, 'Budi');
        Sanctum::actingAs($member);

        $this->getJson('/api/v1/families/'.$family->uuid.'/members/duplicates')->assertForbidden();

        $this->postJson('/api/v1/family-members/'.$primary->uuid.'/merge', ['duplicate_uuid' => $duplicate->uuid])
            ->assertForbidden();
    }

    public function test_merge_reassigns_relationships_tags_and_account_without_duplicate_edges(): void
    {
        [$owner, $family] = $this->familyUser(FamilyUserRole::ROLE_OWNER);
        $father = $this->member($family, $owner, 'Ayah Kandung');
        $primary = $this->member($family, $owner, 'Budi Santoso');
        $duplicate = $this->member($family, $owner, 'Budi Santoso');

        MemberRelationship::factory()->create([
            'family_id' => $family->id,
            'source_member_id' => $duplicate->id,
            'target_member_id' => $father->id,
            'relationship_type' => MemberRelationship::TYPE_FATHER,
        ]);
        MemberRelationship::factory()->create([
            'family_id' => $family->id,
            'source_member_id' => $primary->id,
            'target_member_id' => $father->id,
            'relationship_type' => MemberRelationship::TYPE_FATHER,
        ]);

        $photo = MemberPhoto::factory()->create(['family_id' => $family->id, 'uploaded_by' => $owner->id]);
        $photo->taggedMembers()->attach($duplicate->id);

        $linked = User::factory()->create();
        $duplicate->forceFill(['user_id' => $linked->id])->save();

        Sanctum::actingAs($owner);

        $this->postJson('/api/v1/family-members/'.$primary->uuid.'/merge', ['duplicate_uuid' => $duplicate->uuid])
            ->assertOk()
            ->assertJsonPath('data.uuid', $primary->uuid);

        $this->assertSoftDeleted('family_members', ['id' => $duplicate->id]);
        $this->assertSame(1, MemberRelationship::query()
            ->where('source_member_id', $primary->id)
            ->where('target_member_id', $father->id)
            ->where('relationship_type', MemberRelationship::TYPE_FATHER)
            ->count());
        $this->assertDatabaseHas('member_photo_tags', ['member_photo_id' => $photo->id, 'family_member_id' => $primary->id]);
        $this->assertDatabaseMissing('member_photo_tags', ['family_member_id' => $duplicate->id]);
        $this->assertSame($linked->id, $primary->refresh()->user_id);
        $this->assertNull(FamilyMember::withTrashed()->findOrFail($duplicate->id)->user_id);
        $activity = ActivityLog::query()->where('activity_type', ActivityLog::MEMBER_MERGED)->firstOrFail();
        $this->assertSame($duplicate->uuid, $activity->payload['merged_uuid']);
    }

    public function test_merge_rejects_cross_family_and_conflicting_accounts(): void
    {
        [$owner, $family] = $this->familyUser(FamilyUserRole::ROLE_OWNER);
        $primary = $this->member($family, $owner, 'Budi');
        $other = Family::factory()->create(['created_by' => $owner->id]);
        $foreign = $this->member($other, $owner, 'Budi');
        Sanctum::actingAs($owner);

        $this->postJson('/api/v1/family-members/'.$primary->uuid.'/merge', ['duplicate_uuid' => $foreign->uuid])
            ->assertUnprocessable();

        $first = $this->member($family, $owner, 'Sama');
        $second = $this->member($family, $owner, 'Sama');
        $first->forceFill(['user_id' => User::factory()->create()->id])->save();
        $second->forceFill(['user_id' => User::factory()->create()->id])->save();

        $this->postJson('/api/v1/family-members/'.$first->uuid.'/merge', ['duplicate_uuid' => $second->uuid])
            ->assertUnprocessable();
    }

    /**
     * @return array{0: User, 1: Family}
     */
    private function familyUser(string $role): array
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        FamilyUserRole::factory()->create([
            'family_id' => $family->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);

        return [$user, $family];
    }

    private function member(Family $family, User $user, string $name): FamilyMember
    {
        return FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $user->id,
            'full_name' => $name,
        ]);
    }
}
