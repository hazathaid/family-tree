<?php

namespace App\Services;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;

class OpenApiSpecService
{
    private const API_PREFIX = 'api/v1/';

    /**
     * Build a deterministic OpenAPI 3.1 document from the registered API routes.
     *
     * @return array<string, mixed>
     */
    public function generate(): array
    {
        $paths = [];

        foreach (RouteFacade::getRoutes()->getRoutes() as $route) {
            $uri = $route->uri();

            if (! str_starts_with($uri, self::API_PREFIX)) {
                continue;
            }

            $path = '/'.ltrim($uri, '/');
            $secured = $this->isSecured($route);

            foreach ($this->methods($route) as $method) {
                $paths[$path][strtolower($method)] = $this->operation($route, $path, $method, $secured);
            }
        }

        ksort($paths);

        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Family Tree Platform Indonesia API',
                'version' => '1.0.0',
                'description' => 'REST contract consumed by the web and Flutter clients. Authoritative field-level documentation lives in docs/api-spec.md.',
            ],
            'servers' => [
                ['url' => '/', 'description' => 'Current host'],
            ],
            'tags' => $this->tags($paths),
            'paths' => $paths,
            'components' => [
                'securitySchemes' => [
                    'sanctum' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'Sanctum personal access token',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $paths
     * @return array<int, array{name: string}>
     */
    private function tags(array $paths): array
    {
        $tags = [];

        foreach (array_keys($paths) as $path) {
            $tags[$this->tag($path)] = true;
        }

        return array_map(fn (string $name): array => ['name' => $name], array_keys($tags));
    }

    /**
     * @return array<int, string>
     */
    private function methods(Route $route): array
    {
        return array_values(array_diff($route->methods(), ['HEAD', 'OPTIONS']));
    }

    /**
     * @return array<string, mixed>
     */
    private function operation(Route $route, string $path, string $method, bool $secured): array
    {
        $operation = [
            'operationId' => $this->operationId($route, $path, $method),
            'tags' => [$this->tag($path)],
            'summary' => $route->getName() ?: strtoupper($method).' '.$path,
            'parameters' => $this->parameters($path, $method),
            'responses' => $this->responses($method, $secured),
        ];

        if ($secured) {
            $operation['security'] = [['sanctum' => []]];
        }

        return $operation;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parameters(string $path, string $method): array
    {
        $parameters = [];

        if (preg_match_all('/\{([^}]+)\}/', $path, $matches) > 0) {
            foreach ($matches[1] as $name) {
                $parameters[] = [
                    'name' => $name,
                    'in' => 'path',
                    'required' => true,
                    'schema' => ['type' => 'string'],
                ];
            }
        }

        if (strtoupper($method) === 'GET' && ! str_contains($path, '/export')) {
            $parameters[] = ['name' => 'page', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'integer', 'minimum' => 1]];
            $parameters[] = ['name' => 'limit', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100]];
        }

        return $parameters;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function responses(string $method, bool $secured): array
    {
        $responses = [
            '200' => ['description' => 'Successful response'],
            '422' => ['description' => 'Validation error'],
            '429' => ['description' => 'Rate limited'],
        ];

        if ($secured) {
            $responses['401'] = ['description' => 'Unauthenticated'];
            $responses['403'] = ['description' => 'Forbidden'];
            $responses['404'] = ['description' => 'Not found'];
        }

        if (strtoupper($method) === 'POST') {
            $responses['201'] = ['description' => 'Created'];
        }

        ksort($responses);

        return $responses;
    }

    private function isSecured(Route $route): bool
    {
        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, 'auth:')) {
                return true;
            }
        }

        return false;
    }

    private function tag(string $path): string
    {
        $segments = explode('/', trim($path, '/'));
        $resource = $segments[2] ?? 'general';

        return Str::replace('_', '-', $resource);
    }

    private function operationId(Route $route, string $path, string $method): string
    {
        if ($name = $route->getName()) {
            return $name;
        }

        return strtolower($method).'.'.trim(preg_replace('/[^a-zA-Z0-9]+/', '.', $path) ?? $path, '.');
    }
}
