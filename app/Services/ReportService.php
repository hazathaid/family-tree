<?php

namespace App\Services;

use App\DTOs\ReportCriteria;
use App\Models\Family;
use App\Repositories\Contracts\ReportRepositoryInterface;
use SplQueue;

class ReportService
{
    public function __construct(
        private readonly ReportRepositoryInterface $reports,
        private readonly TreeGraphBuilderService $graphBuilder,
    ) {}

    public function familyStatistics(Family $family): array
    {
        $statistics = $this->reports->memberStatistics($family);
        $generations = $this->generations($family->id, $statistics['root_member_id']);

        unset($statistics['root_member_id']);

        return $statistics + [
            'total_generations' => count($generations),
            'members_by_generation' => $generations,
        ];
    }

    public function activity(Family $family, ReportCriteria $criteria): array
    {
        return [
            'period' => [
                'from' => $criteria->from->toDateString(),
                'to' => $criteria->to->toDateString(),
            ],
        ] + $this->reports->activityReport($family, $criteria);
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
