<?php

namespace Tests\Unit;

use App\DTOs\SearchCriteria;
use App\Models\User;
use App\Repositories\Contracts\SearchRepositoryInterface;
use App\Services\SearchService;
use App\Services\TreeGraphBuilderService;
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
        $graph = Mockery::mock(TreeGraphBuilderService::class);

        $result = (new SearchService($repository, $graph))->search($user, $criteria);

        $this->assertEquals(new Collection(['member']), $result['members']);
        $this->assertEquals(new Collection(['article']), $result['articles']);
        $this->assertEquals(new Collection(['event']), $result['events']);
    }
}
