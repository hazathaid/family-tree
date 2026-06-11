<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\FamilyDashboardResource;
use App\Models\Family;
use App\Services\FamilyDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class FamilyDashboardController extends Controller
{
    public function __construct(
        private readonly FamilyDashboardService $dashboard,
    ) {}

    public function show(Family $family): JsonResponse
    {
        Gate::authorize('viewDashboard', $family);

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => new FamilyDashboardResource($this->dashboard->summary($family)),
        ]);
    }
}
