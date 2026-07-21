<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SystemHealthResource;
use App\Services\SystemHealthService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SystemHealthController extends Controller
{
    public function __invoke(SystemHealthService $service): JsonResponse
    {
        $health = $service->status();

        return response()->json([
            'success' => $health->status === 'ok',
            'message' => $health->status === 'ok' ? 'Healthy' : 'Service Unavailable',
            'data' => (new SystemHealthResource($health))->resolve(),
        ], $health->status === 'ok' ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
