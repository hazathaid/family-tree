<?php

namespace Tests\Unit;

use App\Models\Family;
use App\Repositories\Contracts\FamilyBranchRepositoryInterface;
use App\Repositories\Contracts\FamilyMemberRepositoryInterface;
use App\Repositories\Contracts\FamilyUserRoleRepositoryInterface;
use App\Repositories\Contracts\RelationshipRepositoryInterface;
use App\Services\WebFamilyManagementService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class WebFamilyManagementServiceTest extends TestCase
{
    public function test_directory_delegates_bounded_filters_to_repositories(): void
    {
        $family = new Family;
        $filters = ['search' => 'Budi', 'sort' => 'name'];
        $members = Mockery::mock(FamilyMemberRepositoryInterface::class);
        $branches = Mockery::mock(FamilyBranchRepositoryInterface::class);
        $roles = Mockery::mock(FamilyUserRoleRepositoryInterface::class);
        $relationships = Mockery::mock(RelationshipRepositoryInterface::class);
        $memberPage = new LengthAwarePaginator([], 0, 15);
        $branchPage = new LengthAwarePaginator([], 0, 100);
        $members->shouldReceive('paginateForFamily')->once()->with($family, $filters)->andReturn($memberPage);
        $branches->shouldReceive('paginateForFamily')->once()->with($family, 100)->andReturn($branchPage);

        $result = (new WebFamilyManagementService($branches, $members, $roles, $relationships))->directory($family, $filters);

        $this->assertSame($memberPage, $result['members']);
        $this->assertInstanceOf(Collection::class, $result['branches']);
        $this->assertSame($filters, $result['filters']);
    }
}
