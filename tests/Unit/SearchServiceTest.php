<?php

namespace Tests\Unit;

use App\DTOs\SearchCriteria;
use App\Models\FamilyMember;
use App\Models\User;
use App\Repositories\Contracts\SearchRepositoryInterface;
use App\Services\GenerationMapService;
use App\Services\SearchService;
use Illuminate\Support\Collection;
use Mockery;
use PHPUnit\Framework\TestCase;

class SearchServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_combines_repository_results(): void
    {
        $user = new User;
        $criteria = new SearchCriteria('reuni', null, null, null, null, null, null, null, 15);
        $repository = Mockery::mock(SearchRepositoryInterface::class);
        $repository->shouldReceive('members')->once()->with($user, $criteria)->andReturn(collect(['member']));
        $repository->shouldReceive('articles')->once()->with($user, $criteria)->andReturn(collect(['article']));
        $repository->shouldReceive('events')->once()->with($user, $criteria)->andReturn(collect(['event']));
        $generations = Mockery::mock(GenerationMapService::class);
        $generations->shouldNotReceive('memberIdsAtGeneration');

        $result = (new SearchService($repository, $generations))->search($user, $criteria);

        $this->assertEquals(new Collection(['member']), $result['members']);
        $this->assertEquals(new Collection(['article']), $result['articles']);
        $this->assertEquals(new Collection(['event']), $result['events']);
    }

    public function test_it_scopes_members_by_computed_generation(): void
    {
        $user = new User;
        $criteria = new SearchCriteria(null, null, null, null, null, 1, null, 'root-uuid', 15);
        $root = new FamilyMember;
        $root->id = 10;
        $root->family_id = 3;

        $repository = Mockery::mock(SearchRepositoryInterface::class);
        $repository->shouldReceive('rootMember')->once()->with($user, 'root-uuid')->andReturn($root);
        $repository->shouldReceive('membersForIds')->once()->with($user, $criteria, [20, 21])->andReturn(collect([]));
        $repository->shouldReceive('articles')->once()->andReturn(collect());
        $repository->shouldReceive('events')->once()->andReturn(collect());

        $generations = Mockery::mock(GenerationMapService::class);
        $generations->shouldReceive('memberIdsAtGeneration')->once()->with(3, 10, 1)->andReturn([20, 21]);

        $result = (new SearchService($repository, $generations))->search($user, $criteria);

        $this->assertTrue($result['members']->isEmpty());
    }
}
