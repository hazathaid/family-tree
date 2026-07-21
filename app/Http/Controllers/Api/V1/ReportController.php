<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\ReportCriteria;
use App\Http\Controllers\Controller;
use App\Http\Requests\Report\ActivityReportRequest;
use App\Http\Resources\ActivityReportResource;
use App\Http\Resources\FamilyStatisticsResource;
use App\Models\Family;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function familyStatistics(Family $family): JsonResponse
    {
        Gate::authorize('view', $family);

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => new FamilyStatisticsResource($this->reports->familyStatistics($family)),
        ]);
    }

    public function activity(ActivityReportRequest $request, Family $family): JsonResponse
    {
        Gate::authorize('view', $family);
        $input = $request->validated();
        $from = isset($input['from']) ? Carbon::parse($input['from'])->startOfDay() : now()->subDays(29)->startOfDay();
        $to = isset($input['to']) ? Carbon::parse($input['to'])->endOfDay() : now()->endOfDay();

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => new ActivityReportResource($this->reports->activity($family, new ReportCriteria($from, $to))),
        ]);
    }
}
