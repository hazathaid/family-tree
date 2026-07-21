<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\PushDeviceData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\StorePushDeviceRequest;
use App\Http\Resources\PushDeviceTokenResource;
use App\Repositories\Contracts\PushDeviceTokenRepositoryInterface;
use App\Services\PushDeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushDeviceController extends Controller
{
    public function __construct(private readonly PushDeviceTokenRepositoryInterface $devices, private readonly PushDeviceService $service) {}

    public function store(StorePushDeviceRequest $request): JsonResponse
    {
        $device = $this->service->register($request->user(), PushDeviceData::fromArray($request->validated()));

        return response()->json(['success' => true, 'message' => 'Push device registered', 'data' => new PushDeviceTokenResource($device)], 201);
    }

    public function destroy(Request $request, string $device): JsonResponse
    {
        $token = $this->devices->findForUser($request->user(), $device);
        $this->service->remove($token);

        return response()->json(['success' => true, 'message' => 'Push device removed', 'data' => null]);
    }
}
