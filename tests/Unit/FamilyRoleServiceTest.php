<?php

namespace Tests\Unit;

use App\DTOs\FamilyRoleData;
use App\Models\Family;
use App\Models\FamilyUserRole;
use App\Models\User;
use App\Repositories\Eloquent\EloquentFamilyUserRoleRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use App\Services\FamilyRoleCatalogService;
use App\Services\FamilyRoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FamilyRoleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_invite_adds_existing_user_to_family(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create(['email' => 'member@example.com']);
        $family = Family::factory()->create(['created_by' => $owner->id]);
        $service = $this->service();

        $membership = $service->invite($family, new FamilyRoleData(
            email: 'member@example.com',
            role: FamilyUserRole::ROLE_MEMBER,
        ));

        $this->assertTrue($membership->user->is($user));
        $this->assertSame(FamilyUserRole::ROLE_MEMBER, $membership->role);
    }

    public function test_last_owner_cannot_be_demoted(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        $membership = FamilyUserRole::factory()->owner()->create([
            'family_id' => $family->id,
            'user_id' => $owner->id,
        ]);

        $this->expectException(ValidationException::class);

        $this->service()->assignRole($family, $membership, FamilyUserRole::ROLE_MEMBER);
    }

    private function service(): FamilyRoleService
    {
        return new FamilyRoleService(
            new EloquentFamilyUserRoleRepository,
            new EloquentUserRepository,
            new FamilyRoleCatalogService,
        );
    }
}
