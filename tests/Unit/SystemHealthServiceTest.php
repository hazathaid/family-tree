<?php

namespace Tests\Unit;

use App\Repositories\Contracts\SystemHealthRepositoryInterface;
use App\Services\SystemHealthService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SystemHealthServiceTest extends TestCase
{
    /** @return array<string, array{bool, bool, string}> */
    public static function statuses(): array
    {
        return [
            'all dependencies available' => [true, true, 'ok'],
            'database unavailable' => [false, true, 'degraded'],
            'redis unavailable' => [true, false, 'degraded'],
        ];
    }

    #[DataProvider('statuses')]
    public function test_it_reports_dependency_health(bool $database, bool $redis, string $expected): void
    {
        $repository = $this->createMock(SystemHealthRepositoryInterface::class);
        $repository->method('databaseIsAvailable')->willReturn($database);
        $repository->method('redisIsAvailable')->willReturn($redis);

        $health = (new SystemHealthService($repository))->status();

        self::assertSame($expected, $health->status);
        self::assertSame($database ? 'ok' : 'unavailable', $health->checks['database']);
        self::assertSame($redis ? 'ok' : 'unavailable', $health->checks['redis']);
    }
}
