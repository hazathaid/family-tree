<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreTreeRelativeRequest;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Services\TreeRelativeMemberService;
use App\Services\WebOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TreeRelativeMemberController extends Controller
{
    public function __construct(
        private readonly WebOnboardingService $onboarding,
        private readonly TreeRelativeMemberService $relatives,
    ) {}

    public function store(StoreTreeRelativeRequest $request, FamilyMember $member): RedirectResponse
    {
        $family = $this->onboarding->activeFamilyFor($request->user());
        abort_unless($family instanceof Family && $member->family_id === $family->id, 404);
        Gate::authorize('addRelative', $member);

        $relative = $this->relatives->create($request->user(), $member, $request->validated());

        return redirect()->route('tree.index', [
            ...$request->only(['root', 'mode', 'depth', 'layout', 'member_search', 'living_only', 'show_photos', 'show_nicknames', 'show_relationships']),
            'root' => $member->uuid,
        ])->with('status', $relative->full_name.' berhasil ditambahkan ke pohon keluarga.');
    }
}
