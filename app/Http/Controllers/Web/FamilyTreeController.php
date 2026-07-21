<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\TreeViewerRequest;
use App\Models\Family;
use App\Services\WebOnboardingService;
use App\Services\WebTreeViewerService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class FamilyTreeController extends Controller
{
    public function __construct(
        private readonly WebOnboardingService $onboarding,
        private readonly WebTreeViewerService $viewer,
    ) {}

    public function __invoke(TreeViewerRequest $request): View
    {
        $family = $this->onboarding->activeFamilyFor($request->user());
        abort_unless($family instanceof Family, 403);
        Gate::authorize('view', $family);

        return view('tree.index', [
            'family' => $family,
            ...$this->viewer->present($family, $request->validated(), $request->userAgent() && preg_match('/Mobile|Android|iPhone/i', $request->userAgent())),
        ]);
    }
}
