<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gamification\LeaderboardRequest;
use App\Http\Resources\GamificationProfileResource;
use App\Http\Resources\LeaderboardResource;
use App\Models\Family;
use App\Services\GamificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class GamificationController extends Controller
{
    public function __construct(private readonly GamificationService $gamification) {}

    public function profile(Family $family): JsonResponse
    {
        Gate::authorize('view', $family);

        return $this->success(new GamificationProfileResource($this->gamification->profile($family, request()->user())));
    }

    public function users(LeaderboardRequest $request, Family $family): JsonResponse
    {
        Gate::authorize('view', $family);

        return $this->success(LeaderboardResource::collection($this->gamification->userLeaderboard($family, (int) $request->validated('limit', 20))));
    }

    public function families(LeaderboardRequest $request): JsonResponse
    {
        return $this->success(LeaderboardResource::collection($this->gamification->familyLeaderboard((int) $request->validated('limit', 20))));
    }

    private function success(mixed $data): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Success', 'data' => $data]);
    }
}
