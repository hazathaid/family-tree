<?php

namespace App\Jobs;

use App\Models\TreeExport;
use App\Services\TreeExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateTreeExport implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public readonly int $exportId) {}

    public function handle(TreeExportService $service): void
    {
        $export = TreeExport::query()->find($this->exportId);

        if (! $export instanceof TreeExport) {
            return;
        }

        $service->run($export);
    }
}
