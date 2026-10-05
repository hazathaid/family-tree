<?php

namespace Tests\Feature;

use App\Services\OpenApiSpecService;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class OpenApiContractTest extends TestCase
{
    public function test_the_committed_spec_is_up_to_date_with_the_registered_routes(): void
    {
        $path = base_path('docs/openapi.json');
        $this->assertFileExists($path, 'Run `php artisan openapi:generate` to create docs/openapi.json.');

        $committed = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(
            app(OpenApiSpecService::class)->generate(),
            $committed,
            'The committed OpenAPI document is stale. Run `php artisan openapi:generate` and commit the result.',
        );
    }

    public function test_every_api_route_is_documented(): void
    {
        $spec = app(OpenApiSpecService::class)->generate();
        $documented = $spec['paths'];

        $missing = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();

            if (! str_starts_with($uri, 'api/v1/')) {
                continue;
            }

            $openApiPath = '/'.$uri;

            foreach (array_diff($route->methods(), ['HEAD', 'OPTIONS']) as $method) {
                if (! isset($documented[$openApiPath][strtolower($method)])) {
                    $missing[] = strtoupper($method).' '.$openApiPath;
                }
            }
        }

        $this->assertSame([], $missing, 'Undocumented API routes: '.implode(', ', $missing));
    }

    public function test_secured_operations_declare_the_sanctum_scheme(): void
    {
        $spec = app(OpenApiSpecService::class)->generate();

        $this->assertArrayHasKey('sanctum', $spec['components']['securitySchemes']);
        $this->assertSame(
            [['sanctum' => []]],
            $spec['paths']['/api/v1/families']['get']['security'] ?? null,
        );
    }
}
