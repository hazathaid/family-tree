<?php

namespace App\Console\Commands;

use App\Services\TreeExportService;
use Illuminate\Console\Command;

class PruneTreeExportsCommand extends Command
{
    protected $signature = 'tree-exports:prune';

    protected $description = 'Delete expired tree export files and records';

    public function handle(TreeExportService $exports): int
    {
        $this->info('Pruned '.$exports->prune().' expired tree exports.');

        return self::SUCCESS;
    }
}
