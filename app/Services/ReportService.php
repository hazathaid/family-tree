<?php

namespace App\Services;

use App\DTOs\ReportCriteria;
use App\Models\Family;
use App\Repositories\Contracts\ReportRepositoryInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use SplQueue;

class ReportService
{
    public function __construct(
        private readonly ReportRepositoryInterface $reports,
        private readonly TreeGraphBuilderService $graphBuilder,
        private readonly ?CacheRepository $cache = null,
    ) {}

    public function familyStatistics(Family $family): array
    {
        return $this->remember("web:reports:{$family->id}:statistics", function () use ($family): array {
            $statistics = $this->reports->memberStatistics($family);
            $generations = $this->generations($family->id, $statistics['root_member_id']);

            unset($statistics['root_member_id']);

            return $statistics + [
                'total_generations' => count($generations),
                'members_by_generation' => $generations,
            ];
        });
    }

    public function activity(Family $family, ReportCriteria $criteria): array
    {
        $key = "web:reports:{$family->id}:activity:{$criteria->from->toDateString()}:{$criteria->to->toDateString()}";

        return $this->remember($key, fn (): array => [
            'period' => [
                'from' => $criteria->from->toDateString(),
                'to' => $criteria->to->toDateString(),
            ],
        ] + $this->reports->activityReport($family, $criteria));
    }

    public function webInsights(Family $family, ReportCriteria $criteria): array
    {
        $key = "web:reports:{$family->id}:insights:{$criteria->from->toDateString()}:{$criteria->to->toDateString()}";

        return $this->remember($key, fn (): array => $this->reports->webInsights($family, $criteria));
    }

    private function remember(string $key, callable $callback): array
    {
        return $this->cache?->remember($key, now()->addMinutes(15), $callback) ?? $callback();
    }

    private function generations(int $familyId, ?int $rootId): array
    {
        if ($rootId === null) {
            return [];
        }

        $graph = $this->graphBuilder->build($familyId);
        $levels = [];

        $componentRoots = array_values(array_unique([$rootId, ...array_keys($graph['nodes'])]));
        foreach ($componentRoots as $componentRoot) {
            if (isset($levels[$componentRoot])) {
                continue;
            }

            $levels[$componentRoot] = 0;
            $queue = new SplQueue;
            $queue->enqueue($componentRoot);

            while (! $queue->isEmpty()) {
                $current = $queue->dequeue();
                foreach ($graph['adjacency'][$current] ?? [] as $edge) {
                    if (isset($levels[$edge['to']])) {
                        continue;
                    }
                    $levels[$edge['to']] = $levels[$current] + $edge['delta'];
                    $queue->enqueue($edge['to']);
                }
            }
        }

        $counts = array_count_values($levels);
        ksort($counts, SORT_NUMERIC);

        return $counts;
    }
}
