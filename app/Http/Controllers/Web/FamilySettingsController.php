<?php

namespace App\Http\Controllers\Web;

use App\DTOs\FamilyBranchData;
use App\DTOs\FamilyData;
use App\DTOs\FamilyRoleData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Family\AssignFamilyRoleRequest;
use App\Http\Requests\Family\InviteFamilyMemberRequest;
use App\Http\Requests\Family\StoreFamilyBranchRequest;
use App\Http\Requests\Family\UpdateFamilyBranchRequest;
use App\Http\Requests\Web\ConfirmDeleteRequest;
use App\Http\Requests\Web\SaveFamilySettingsRequest;
use App\Models\Family;
use App\Models\FamilyBranch;
use App\Models\FamilyUserRole;
use App\Services\FamilyBranchService;
use App\Services\FamilyRoleService;
use App\Services\FamilyService;
use App\Services\WebFamilyManagementService;
use App\Services\WebOnboardingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FamilySettingsController extends Controller
{
    public function __construct(
        private readonly WebOnboardingService $onboarding,
        private readonly WebFamilyManagementService $presentation,
        private readonly FamilyService $families,
        private readonly FamilyBranchService $branches,
        private readonly FamilyRoleService $roles,
    ) {}

    public function index(Request $request): View
    {
        $family = $this->activeFamily($request);
        Gate::authorize('view', $family);

        return view('settings.index', ['family' => $family, ...$this->presentation->settings($family)]);
    }

    public function update(SaveFamilySettingsRequest $request): RedirectResponse
    {
        $family = $this->activeFamily($request);
        Gate::authorize('update', $family);
        $data = $request->safe()->except(['logo', 'cover_image']);
        $data['logo'] = $family->logo;
        $data['cover_image'] = $family->cover_image;
        $this->families->update($family, FamilyData::fromArray($data));
        $this->families->updateIdentityAssets($family, $request->file('logo'), $request->file('cover_image'));

        return back()->with('status', 'Profil keluarga berhasil diperbarui.');
    }

    public function storeBranch(StoreFamilyBranchRequest $request): RedirectResponse
    {
        $family = $this->activeFamily($request);
        Gate::authorize('manageBranches', $family);
        $this->branches->create($family, FamilyBranchData::fromArray($request->validated()));

        return back()->with('status', 'Cabang keluarga berhasil ditambahkan.');
    }

    public function updateBranch(UpdateFamilyBranchRequest $request, FamilyBranch $branch): RedirectResponse
    {
        $family = $this->activeFamily($request);
        Gate::authorize('update', $branch);
        $this->branches->update($family, $branch, FamilyBranchData::fromArray($request->validated()));

        return back()->with('status', 'Cabang keluarga berhasil diperbarui.');
    }

    public function destroyBranch(ConfirmDeleteRequest $request, FamilyBranch $branch): RedirectResponse
    {
        $family = $this->activeFamily($request);
        Gate::authorize('delete', $branch);
        $this->branches->delete($family, $branch);

        return back()->with('status', 'Cabang keluarga berhasil dihapus.');
    }

    public function invite(InviteFamilyMemberRequest $request): RedirectResponse
    {
        $family = $this->activeFamily($request);
        Gate::authorize('manageRoles', $family);
        $this->roles->invite($family, FamilyRoleData::fromArray($request->validated()));

        return back()->with('status', 'Anggota keluarga berhasil diundang.');
    }

    public function assignRole(AssignFamilyRoleRequest $request, FamilyUserRole $membership): RedirectResponse
    {
        $family = $this->activeFamily($request);
        Gate::authorize('manageRoles', $family);
        $this->roles->assignRole($family, $membership, $request->validated('role'));

        return back()->with('status', 'Peran anggota berhasil diperbarui.');
    }

    public function removeRole(ConfirmDeleteRequest $request, FamilyUserRole $membership): RedirectResponse
    {
        $family = $this->activeFamily($request);
        Gate::authorize('manageRoles', $family);
        $this->roles->removeMember($family, $membership);

        return back()->with('status', 'Akses anggota berhasil dicabut.');
    }

    private function activeFamily(Request $request): Family
    {
        $family = $this->onboarding->activeFamilyFor($request->user());
        abort_unless($family instanceof Family, 403);

        return $family;
    }
}
