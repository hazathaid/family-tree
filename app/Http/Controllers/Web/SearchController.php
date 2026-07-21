<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Search\SearchRequest;
use App\Models\Family;
use App\Services\WebDiscoveryService;
use App\Services\WebOnboardingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class SearchController extends Controller
{
    public function __construct(private readonly WebOnboardingService $onboarding, private readonly WebDiscoveryService $discovery) {}

    public function __invoke(SearchRequest $request): View
    {
        $family = $this->onboarding->activeFamilyFor($request->user());
        abort_unless($family instanceof Family, 403);
        Gate::authorize('view', $family);
        $filters = $request->safe()->only(['keyword', 'name', 'city', 'generation', 'status', 'root_member_uuid']);

        return view('search.index', ['family' => $family, 'filters' => $filters, ...$this->discovery->search($family, $request->user(), $filters)]);
    }
}
