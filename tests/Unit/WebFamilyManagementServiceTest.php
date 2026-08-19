<?php

namespace Tests\Unit;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\User;
use App\Repositories\Contracts\FamilyBranchRepositoryInterface;
use App\Repositories\Contracts\FamilyMemberRepositoryInterface;
use App\Repositories\Contracts\FamilyUserRoleRepositoryInterface;
use App\Repositories\Contracts\RelationshipRepositoryInterface;
use App\Services\RelationshipResolverService;
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
        $resolver = Mockery::mock(RelationshipResolverService::class);
        $memberPage = new LengthAwarePaginator([], 0, 15);
        $branchPage = new LengthAwarePaginator([], 0, 100);
        $members->shouldReceive('paginateForFamily')->once()->with($family, $filters)->andReturn($memberPage);
        $branches->shouldReceive('paginateForFamily')->once()->with($family, 100)->andReturn($branchPage);

        $result = (new WebFamilyManagementService($branches, $members, $roles, $relationships, $resolver))->directory($family, $filters);

        $this->assertSame($memberPage, $result['members']);
        $this->assertInstanceOf(Collection::class, $result['branches']);
        $this->assertSame($filters, $result['filters']);
    }

    public function test_member_detail_resolves_relationship_from_logged_in_member_perspective(): void
    {
        $user = new User;
        $family = new Family;
        $member = new FamilyMember;
        $member->setRelation('family', $family);
        $viewer = new FamilyMember;

        $branches = Mockery::mock(FamilyBranchRepositoryInterface::class);
        $members = Mockery::mock(FamilyMemberRepositoryInterface::class);
        $roles = Mockery::mock(FamilyUserRoleRepositoryInterface::class);
        $relationships = Mockery::mock(RelationshipRepositoryInterface::class);
        $resolver = Mockery::mock(RelationshipResolverService::class);

        $members->shouldReceive('findForUserInFamily')->once()->with($user, $family)->andReturn($viewer);
        $relationships->shouldReceive('forMember')->once()->with($member)->andReturn(collect());
        $resolver->shouldReceive('resolve')->once()->with($viewer, $member)->andReturn(['relationship' => 'Ayah', 'path' => []]);

        $result = (new WebFamilyManagementService($branches, $members, $roles, $relationships, $resolver))->memberDetail($member, $user);

        $this->assertSame('Ayah', $result['relationship_to_viewer']);
        $this->assertTrue($result['relationships']->isEmpty());
    }

    public function test_member_detail_returns_null_relationship_when_user_has_no_linked_member(): void
    {
        $user = new User;
        $member = new FamilyMember;
        $member->setRelation('family', new Family);

        $branches = Mockery::mock(FamilyBranchRepositoryInterface::class);
        $members = Mockery::mock(FamilyMemberRepositoryInterface::class);
        $roles = Mockery::mock(FamilyUserRoleRepositoryInterface::class);
        $relationships = Mockery::mock(RelationshipRepositoryInterface::class);
        $resolver = Mockery::mock(RelationshipResolverService::class);

        $members->shouldReceive('findForUserInFamily')->once()->andReturnNull();
        $relationships->shouldReceive('forMember')->once()->with($member)->andReturn(collect());
        $resolver->shouldNotReceive('resolve');

        $result = (new WebFamilyManagementService($branches, $members, $roles, $relationships, $resolver))->memberDetail($member, $user);

        $this->assertNull($result['relationship_to_viewer']);
    }
}
