<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ConfirmDeleteRequest;
use App\Http\Requests\Web\MemberDirectoryRequest;
use App\Http\Requests\Web\SaveMemberRequest;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Services\FamilyMemberService;
use App\Services\WebFamilyManagementService;
use App\Services\WebOnboardingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MemberController extends Controller
{
    public function __construct(
        private readonly WebOnboardingService $onboarding,
        private readonly WebFamilyManagementService $presentation,
        private readonly FamilyMemberService $members,
    ) {}

    public function index(MemberDirectoryRequest $request): View
    {
        $family = $this->activeFamily($request);
        Gate::authorize('view', $family);

        return view('members.index', ['family' => $family, ...$this->presentation->directory($family, $request->validated())]);
    }

    public function create(Request $request): View
    {
        $family = $this->activeFamily($request);
        Gate::authorize('create', [FamilyMember::class, $family]);

        return view('members.form', ['family' => $family, 'member' => null, 'branches' => $this->presentation->branchesForForm($family)]);
    }

    public function store(SaveMemberRequest $request): RedirectResponse
    {
        $family = $this->activeFamily($request);
        Gate::authorize('create', [FamilyMember::class, $family]);
        $member = $this->members->create($request->user(), $family, $request->validated());

        if ($request->hasFile('photo')) {
            $this->members->uploadPhoto($member, $request->file('photo'));
        }

        return redirect()->route('members.show', $member)->with('status', 'Anggota berhasil ditambahkan.');
    }

    public function show(Request $request, FamilyMember $member): View
    {
        $this->ensureActiveFamilyMember($request, $member);
        Gate::authorize('view', $member);

        return view('members.show', ['member' => $member->load(['branch', 'taggedPhotos']), ...$this->presentation->memberDetail($member)]);
    }

    public function edit(Request $request, FamilyMember $member): View
    {
        $family = $this->ensureActiveFamilyMember($request, $member);
        Gate::authorize('update', $member);

        return view('members.form', ['family' => $family, 'member' => $member, 'branches' => $this->presentation->branchesForForm($family)]);
    }

    public function update(SaveMemberRequest $request, FamilyMember $member): RedirectResponse
    {
        $this->ensureActiveFamilyMember($request, $member);
        Gate::authorize('update', $member);
        $member = $this->members->update($member, $request->validated());

        if ($request->hasFile('photo')) {
            $member = $this->members->uploadPhoto($member, $request->file('photo'));
        }

        return redirect()->route('members.show', $member)->with('status', 'Anggota berhasil diperbarui.');
    }

    public function destroy(ConfirmDeleteRequest $request, FamilyMember $member): RedirectResponse
    {
        $this->ensureActiveFamilyMember($request, $member);
        Gate::authorize('delete', $member);
        $this->members->delete($member);

        return redirect()->route('members.index')->with('status', 'Anggota berhasil dihapus.');
    }

    private function activeFamily(Request $request): Family
    {
        $family = $this->onboarding->activeFamilyFor($request->user());
        abort_unless($family instanceof Family, 403);

        return $family;
    }

    private function ensureActiveFamilyMember(Request $request, FamilyMember $member): Family
    {
        $family = $this->activeFamily($request);
        abort_unless($member->family_id === $family->id, 404);

        return $family;
    }
}
