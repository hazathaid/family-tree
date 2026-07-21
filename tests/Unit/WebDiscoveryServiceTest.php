<?php

namespace Tests\Unit;

use App\Models\Family;
use App\Models\User;
use App\Repositories\Contracts\FamilyMemberRepositoryInterface;
use App\Services\SearchService;
use App\Services\WebDiscoveryService;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;

class WebDiscoveryServiceTest extends TestCase
{
    public function test_it_builds_bounded_paginated_grouped_results(): void
    {
        $family = new Family(['uuid' => '42a3c5c7-8344-45b8-a0c5-b9cf68b741de']);
        $user = new User;
        $search = Mockery::mock(SearchService::class);
        $members = Mockery::mock(FamilyMemberRepositoryInterface::class);
        $roots = new LengthAwarePaginator([], 0, 100);
        $search->shouldReceive('search')->once()->andReturn(['members' => collect(range(1, 12)), 'articles' => collect(), 'events' => collect()]);
        $members->shouldReceive('paginateForFamily')->once()->with($family, [], 100)->andReturn($roots);

        $result = app()->makeWith(WebDiscoveryService::class, ['search' => $search, 'members' => $members])->search($family, $user, ['keyword' => 'Budi']);

        $this->assertSame(12, $result['members']->total());
        $this->assertCount(10, $result['members']);
        $this->assertSame($roots, $result['roots']);
    }
}
