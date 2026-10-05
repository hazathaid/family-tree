<?php

namespace Tests\Feature;

use Illuminate\Support\Str;
use Tests\TestCase;

class CoverageGateCommandTest extends TestCase
{
    public function test_it_passes_when_global_and_engine_coverage_meet_thresholds(): void
    {
        $path = $this->clover([
            ['/app/Services/RelationshipTraversalService.php', 4, 4],
            ['/app/Services/ArticleService.php', 10, 8],
        ]);

        $this->artisan('coverage:gate', ['file' => $path])
            ->expectsOutputToContain('Coverage gate passed.')
            ->assertExitCode(0);
    }

    public function test_it_fails_when_global_coverage_is_below_the_threshold(): void
    {
        $path = $this->clover([
            ['/app/Services/RelationshipTraversalService.php', 10, 10],
            ['/app/Services/ArticleService.php', 10, 5],
        ]);

        $this->artisan('coverage:gate', ['file' => $path, '--min' => 80])
            ->expectsOutputToContain('below the')
            ->assertExitCode(1);
    }

    public function test_it_fails_when_engine_coverage_is_below_the_threshold(): void
    {
        $path = $this->clover([
            ['/app/Services/RelationshipTraversalService.php', 100, 90],
            ['/app/Services/ArticleService.php', 10, 10],
        ]);

        $this->artisan('coverage:gate', ['file' => $path, '--engine-min' => 95])
            ->expectsOutputToContain('Engine coverage')
            ->assertExitCode(1);
    }

    public function test_it_fails_when_the_report_is_missing(): void
    {
        $this->artisan('coverage:gate', ['file' => '/tmp/does-not-exist-'.Str::uuid().'.xml'])
            ->expectsOutputToContain('Coverage report not found')
            ->assertExitCode(1);
    }

    /**
     * @param  array<int, array{0: string, 1: int, 2: int}>  $files  [path, total statements, covered statements]
     */
    private function clover(array $files): string
    {
        $path = sys_get_temp_dir().'/coverage-'.Str::uuid().'.xml';
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<coverage>\n<project>\n";

        foreach ($files as [$name, $total, $covered]) {
            $xml .= '<file name="'.$name.'">'."\n";
            for ($i = 1; $i <= $total; $i++) {
                $count = $i <= $covered ? 1 : 0;
                $xml .= '<line num="'.$i.'" type="stmt" count="'.$count.'"/>'."\n";
            }
            $xml .= "</file>\n";
        }

        $xml .= "</project>\n</coverage>\n";
        file_put_contents($path, $xml);

        return $path;
    }
}
