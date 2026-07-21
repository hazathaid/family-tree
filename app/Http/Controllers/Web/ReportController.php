<?php

namespace App\Http\Controllers\Web;

use App\DTOs\ReportCriteria;
use App\Http\Controllers\Controller;
use App\Http\Requests\Report\ActivityReportRequest;
use App\Models\Family;
use App\Services\GamificationService;
use App\Services\ReportService;
use App\Services\WebOnboardingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    public function __construct(private readonly WebOnboardingService $onboarding, private readonly ReportService $reports, private readonly GamificationService $gamification) {}

    public function __invoke(ActivityReportRequest $request): View
    {
        $family = $this->onboarding->activeFamilyFor($request->user());
        abort_unless($family instanceof Family, 403);
        Gate::authorize('view', $family);
        $input = $request->validated();
        $criteria = new ReportCriteria(
            isset($input['from']) ? Carbon::parse($input['from'])->startOfDay() : now()->subDays(29)->startOfDay(),
            isset($input['to']) ? Carbon::parse($input['to'])->endOfDay() : now()->endOfDay(),
        );

        return view('reports.index', [
            'family' => $family,
            'statistics' => $this->reports->familyStatistics($family),
            'activityReport' => $this->reports->activity($family, $criteria),
            'insights' => $this->reports->webInsights($family, $criteria),
            'gamification' => $this->gamification->profile($family, $request->user()),
            'leaderboard' => $this->gamification->userLeaderboard($family, 20),
            'from' => $criteria->from->toDateString(),
            'to' => $criteria->to->toDateString(),
        ]);
    }
}
