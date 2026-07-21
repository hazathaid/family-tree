<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Timeline\TimelineIndexRequest;
use App\Http\Resources\ActivityLogResource;
use App\Repositories\Contracts\ActivityLogRepositoryInterface;
use Illuminate\Http\JsonResponse;

class TimelineController extends Controller
{
    public function __construct(private readonly ActivityLogRepositoryInterface $activities) {}

    public function index(TimelineIndexRequest $request): JsonResponse
    {
        $activities = $this->activities->paginateForUser(
            $request->user(),
            $request->safe()->only(['family_uuid', 'type']),
            $request->integer('limit', 15),
        );

        return response()->json(['success' => true, 'message' => 'Success', 'data' => ActivityLogResource::collection($activities)]);
    }
}
