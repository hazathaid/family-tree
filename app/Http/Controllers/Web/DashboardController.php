<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WebDashboardService;
use App\Services\WebOnboardingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function __construct(
        private readonly WebOnboardingService $onboarding,
        private readonly WebDashboardService $dashboard,
    ) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $family = $this->onboarding->activeFamilyFor($user);
        abort_unless($family !== null, 403);
        Gate::authorize('viewDashboard', $family);

        return view('dashboard', [
            'family' => $family,
            'dashboard' => $this->dashboard->show($family, $user),
        ]);
    }
}
