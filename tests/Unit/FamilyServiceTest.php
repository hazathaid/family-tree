<?php

namespace Tests\Unit;

use App\DTOs\FamilyData;
use App\Models\FamilyUserRole;
use App\Models\User;
use App\Repositories\Eloquent\EloquentFamilyRepository;
use App\Repositories\Eloquent\EloquentFamilyUserRoleRepository;
use App\Services\FamilyRoleCatalogService;
use App\Services\FamilyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_family_assigns_creator_as_owner(): void
    {
        $user = User::factory()->create();
        $service = new FamilyService(
            new EloquentFamilyRepository,
            new EloquentFamilyUserRoleRepository,
            new FamilyRoleCatalogService,
        );

        $family = $service->create($user, new FamilyData(
            name: 'Keluarga Besar Ahmad',
            description: null,
            originCity: 'Bandung',
            logo: null,
            coverImage: null,
        ));

        $this->assertSame('keluarga-besar-ahmad', $family->slug);
        $this->assertDatabaseHas('family_user_roles', [
            'family_id' => $family->id,
            'user_id' => $user->id,
            'role' => FamilyUserRole::ROLE_OWNER,
        ]);
    }
}
