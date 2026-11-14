<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Services\MemberSpouseService;
use App\Services\WebOnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TreeMemberSpouseController extends Controller
{
    public function __construct(
        private readonly WebOnboardingService $onboarding,
        private readonly MemberSpouseService $spouses,
    ) {}

    public function index(Request $request, FamilyMember $member): JsonResponse
    {
        $family = $this->onboarding->activeFamilyFor($request->user());
        abort_unless($family instanceof Family && $member->family_id === $family->id, 404);
        Gate::authorize('view', $member);

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => $this->spouses->summaries($member),
        ]);
    }
}
