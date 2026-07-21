<?php

namespace App\Http\Controllers\Web;

use App\DTOs\FamilyData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Family\SelectActiveFamilyRequest;
use App\Http\Requests\Family\StoreFamilyRequest;
use App\Models\Family;
use App\Services\FamilyService;
use App\Services\WebOnboardingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OnboardingController extends Controller
{
    public function __construct(
        private readonly WebOnboardingService $onboarding,
        private readonly FamilyService $families,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Family::class);

        return view('onboarding.index', ['families' => $this->onboarding->familiesFor($request->user())]);
    }

    public function store(StoreFamilyRequest $request): RedirectResponse
    {
        Gate::authorize('create', Family::class);
        $family = $this->families->create($request->user(), FamilyData::fromArray($request->validated()));
        $this->onboarding->activate($family);

        return redirect()->route('dashboard')->with('status', 'Keluarga berhasil dibuat.');
    }

    public function select(SelectActiveFamilyRequest $request, Family $family): RedirectResponse
    {
        $this->onboarding->activate($family);

        return redirect()->route('dashboard')->with('status', 'Keluarga aktif diperbarui.');
    }
}
