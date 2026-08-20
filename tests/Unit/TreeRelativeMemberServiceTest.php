<?php

namespace Tests\Unit;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\User;
use App\Services\TreeRelativeMemberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TreeRelativeMemberServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_adding_a_child_requires_the_member_gender(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $member = FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $user->id,
            'gender' => null,
        ]);

        $this->expectException(ValidationException::class);

        app(TreeRelativeMemberService::class)->create($user, $member, [
            'relation' => 'child',
            'full_name' => 'Anak Baru',
        ]);
    }

    public function test_parent_relation_uses_the_relative_gender(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $member = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id]);

        $relative = app(TreeRelativeMemberService::class)->create($user, $member, [
            'relation' => 'parent',
            'full_name' => 'Ayah Baru',
            'gender' => 'male',
        ]);

        $this->assertDatabaseHas('member_relationships', [
            'family_id' => $family->id,
            'source_member_id' => $relative->id,
            'target_member_id' => $member->id,
            'relationship_type' => 'father',
        ]);
    }
}
