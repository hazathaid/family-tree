<?php

namespace Tests\Unit;

use App\Models\Family;
use App\Models\User;
use App\Repositories\Contracts\FamilyRepositoryInterface;
use App\Services\WebOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class WebOnboardingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_family_is_activated_and_routes_to_dashboard(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create();
        $repository = Mockery::mock(FamilyRepositoryInterface::class);
        $repository->shouldReceive('allForUser')->once()->with($user)->andReturn(collect([$family]));

        $destination = (new WebOnboardingService($repository))->destinationFor($user);

        self::assertSame(route('dashboard'), $destination);
        $this->assertSame($family->uuid, session(WebOnboardingService::ACTIVE_FAMILY_KEY));
    }

    public function test_multiple_families_route_to_selector_without_choosing_for_user(): void
    {
        $user = User::factory()->create();
        $repository = Mockery::mock(FamilyRepositoryInterface::class);
        $repository->shouldReceive('allForUser')->once()->andReturn(Family::factory()->count(2)->create());

        $destination = (new WebOnboardingService($repository))->destinationFor($user);

        self::assertSame(route('onboarding.index'), $destination);
        $this->assertNull(session(WebOnboardingService::ACTIVE_FAMILY_KEY));
    }

    public function test_active_family_must_exist_and_be_accessible(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create();
        $repository = Mockery::mock(FamilyRepositoryInterface::class);
        $repository->shouldReceive('findByUuid')->once()->with($family->uuid)->andReturn($family);
        session([WebOnboardingService::ACTIVE_FAMILY_KEY => $family->uuid]);

        self::assertNull((new WebOnboardingService($repository))->activeFamilyFor($user));
    }
}
