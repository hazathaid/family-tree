<?php

namespace App\Console\Commands;

use App\Services\OpenApiSpecService;
use Illuminate\Console\Command;

class GenerateOpenApiCommand extends Command
{
    protected $signature = 'openapi:generate {--path=docs/openapi.json : Output file relative to the project root}';

    protected $description = 'Generate the OpenAPI document from the registered API routes';

    public function handle(OpenApiSpecService $spec): int
    {
        $target = base_path((string) $this->option('path'));
        $directory = dirname($target);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $json = json_encode($spec->generate(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            $this->error('Unable to encode the OpenAPI document.');

            return self::FAILURE;
        }

        file_put_contents($target, $json."\n");

        $this->info("OpenAPI document written to {$target}");

        return self::SUCCESS;
    }
}
