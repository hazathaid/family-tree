<?php

namespace Tests\Feature;

use App\DTOs\FamilyBranchData;
use App\DTOs\FamilyData;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\User;
use App\Services\FamilyBranchService;
use App\Services\FamilyRoleService;
use App\Services\FamilyService;
use App\Services\RelationshipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_relationship_lifecycle_is_audited(): void
    {
        [$user, $family] = $this->owner();
        $father = $this->member($family, $user, 'Ayah');
        $child = $this->member($family, $user, 'Anak');
        $this->actingAs($user);

        $service = app(RelationshipService::class);
        $relationship = $service->create([
            'family_id' => $family->id,
            'source_member_id' => $child->id,
            'target_member_id' => $father->id,
            'relationship_type' => 'father',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'member_relationship.created',
            'auditable_uuid' => $relationship->uuid,
        ]);

        $service->update($relationship, ['relationship_type' => 'father', 'notes' => 'dikonfirmasi']);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'member_relationship.updated',
            'auditable_uuid' => $relationship->uuid,
        ]);

        $service->delete($relationship);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'member_relationship.deleted',
            'auditable_uuid' => $relationship->uuid,
        ]);
    }

    public function test_family_settings_role_and_branch_mutations_are_audited(): void
    {
        [$user, $family] = $this->owner();
        $this->actingAs($user);

        app(FamilyService::class)->update($family, new FamilyData('Keluarga Baru', null, null, null, null));
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'family.updated',
            'auditable_uuid' => $family->uuid,
        ]);

        $branch = app(FamilyBranchService::class)->create($family, new FamilyBranchData('Cabang Utara', null));
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'family_branch.created',
            'auditable_uuid' => $branch->uuid,
        ]);

        $membership = FamilyUserRole::factory()->create([
            'family_id' => $family->id,
            'user_id' => User::factory()->create()->id,
            'role' => FamilyUserRole::ROLE_MEMBER,
        ]);
        app(FamilyRoleService::class)->assignRole($family, $membership, FamilyUserRole::ROLE_ADMIN);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'family_user_role.updated',
            'auditable_uuid' => $membership->uuid,
        ]);
    }

    public function test_system_mutations_without_an_actor_are_not_audited(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $member = $this->member($family, $user, 'Tanpa Aktor');

        $member->update(['full_name' => 'Tanpa Aktor Diubah']);

        $this->assertDatabaseCount('audit_logs', 0);
    }

    /**
     * @return array{0: User, 1: Family}
     */
    private function owner(): array
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        FamilyUserRole::factory()->create([
            'family_id' => $family->id,
            'user_id' => $user->id,
            'role' => FamilyUserRole::ROLE_OWNER,
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
