<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SimpleXMLElement;

class CheckCoverageCommand extends Command
{
    protected $signature = 'coverage:gate
        {file=coverage.xml : Path to the Clover coverage report}
        {--min=80 : Minimum global line coverage percentage}
        {--engine-min=95 : Minimum line coverage percentage for the relationship/tree engine}';

    protected $description = 'Fail when Clover line coverage drops below the project thresholds';

    /**
     * Engine scope follows the relationship and tree specification: the
     * relationship traversal/resolver, tree builder/layout/presentation/export
     * services, generation map and their caches.
     *
     * @var array<int, string>
     */
    private const ENGINE_MARKERS = [
        '/Relationship',
        '/Tree',
        'GenerationMap',
    ];

    public function handle(): int
    {
        $path = (string) $this->argument('file');

        if (! is_file($path)) {
            $this->error("Coverage report not found: {$path}");

            return self::FAILURE;
        }

        $xml = @simplexml_load_file($path);

        if (! $xml instanceof SimpleXMLElement) {
            $this->error("Coverage report is not valid XML: {$path}");

            return self::FAILURE;
        }

        [$covered, $total] = $this->totals($xml, null);
        [$engineCovered, $engineTotal] = $this->totals($xml, self::ENGINE_MARKERS);

        $global = $this->percent($covered, $total);
        $engine = $this->percent($engineCovered, $engineTotal);
        $globalMin = (float) $this->option('min');
        $engineMin = (float) $this->option('engine-min');

        $this->line(sprintf('Global line coverage: %.2f%% (minimum %.2f%%)', $global, $globalMin));
        $this->line(sprintf('Relationship/tree engine coverage: %.2f%% (minimum %.2f%%)', $engine, $engineMin));

        $failed = false;

        if ($total === 0) {
            $this->error('No statements found in the coverage report.');

            return self::FAILURE;
        }

        if ($global + 0.0001 < $globalMin) {
            $this->error(sprintf('Global coverage %.2f%% is below the %.2f%% threshold.', $global, $globalMin));
            $failed = true;
        }

        if ($engineTotal > 0 && $engine + 0.0001 < $engineMin) {
            $this->error(sprintf('Engine coverage %.2f%% is below the %.2f%% threshold.', $engine, $engineMin));
            $failed = true;
        }

        if ($failed) {
            return self::FAILURE;
        }

        $this->info('Coverage gate passed.');

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string>|null  $markers
     * @return array{0: int, 1: int}
     */
    private function totals(SimpleXMLElement $xml, ?array $markers): array
    {
        $covered = 0;
        $total = 0;

        foreach ($xml->xpath('//file') ?: [] as $file) {
            $name = (string) $file['name'];

            if ($markers !== null && ! $this->matches($name, $markers)) {
                continue;
            }

            foreach ($file->xpath('line[@type="stmt"]') ?: [] as $line) {
                $total++;

                if ((int) $line['count'] > 0) {
                    $covered++;
                }
            }
        }

        return [$covered, $total];
    }

    /**
     * @param  array<int, string>  $markers
     */
    private function matches(string $name, array $markers): bool
    {
        foreach ($markers as $marker) {
            if (str_contains($name, $marker)) {
                return true;
            }
        }

        return false;
    }

    private function percent(int $covered, int $total): float
    {
        return $total === 0 ? 100.0 : ($covered / $total) * 100;
    }
}
