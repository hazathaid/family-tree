<?php

namespace Tests\Unit;

use App\DTOs\ReportCriteria;
use App\Models\Family;
use App\Repositories\Contracts\ReportRepositoryInterface;
use App\Services\ReportService;
use App\Services\TreeGraphBuilderService;
use Illuminate\Support\Carbon;
use Mockery;
use PHPUnit\Framework\TestCase;

class ReportServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_adds_the_requested_period_to_activity_totals(): void
    {
        $family = new Family;
        $criteria = new ReportCriteria(Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'));
        $repository = Mockery::mock(ReportRepositoryInterface::class);
        $repository->shouldReceive('activityReport')->once()->with($family, $criteria)->andReturn(['active_users' => 3]);
        $graph = Mockery::mock(TreeGraphBuilderService::class);

        $result = (new ReportService($repository, $graph))->activity($family, $criteria);

        $this->assertSame('2026-07-01', $result['period']['from']);
        $this->assertSame('2026-07-31', $result['period']['to']);
        $this->assertSame(3, $result['active_users']);
    }
}
