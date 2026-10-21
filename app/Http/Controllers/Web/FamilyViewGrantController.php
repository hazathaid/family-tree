<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ConfirmDeleteRequest;
use App\Http\Requests\Web\StoreFamilyViewGrantRequest;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyViewGrant;
use App\Models\User;
use App\Services\FamilyViewGrantService;
use App\Services\WebOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FamilyViewGrantController extends Controller
{
    public function __construct(
        private readonly WebOnboardingService $onboarding,
        private readonly FamilyViewGrantService $grants,
    ) {}

    public function store(StoreFamilyViewGrantRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $family = $this->activeFamily($user);

        $anchor = FamilyMember::query()
            ->where('uuid', $request->validated('anchor_member_uuid'))
            ->where('family_id', $family->id)
            ->first();
        abort_unless($anchor instanceof FamilyMember, 404);

        $result = $this->grants->create(
            $user,
            $family,
            $anchor,
            $request->validated('email'),
            (int) $request->integer('expires_in_days', FamilyViewGrantService::DEFAULT_TTL_DAYS),
        );

        $message = $result['grant']->grantee_user_id !== null
            ? 'Akses baca-saja diberikan kepada pengguna terdaftar.'
            : 'Undangan akses baca-saja dikirim ke email tujuan.';

        return back()->with('status', $message);
    }

    public function revoke(ConfirmDeleteRequest $request, FamilyViewGrant $grant): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $family = $this->activeFamily($user);
        abort_unless($family->id === $grant->family_id, 404);

        $this->grants->revoke($user, $grant);

        return back()->with('status', 'Akses baca-saja dicabut.');
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        try {
            $grant = $this->grants->accept($user, $token);
        } catch (ValidationException $exception) {
            return redirect()->route('home')->withErrors($exception->errors());
        }

        return redirect()
            ->route('family-view.show', $grant->family)
            ->with('status', 'Akses baca-saja diaktifkan.');
    }

    private function activeFamily(User $user): Family
    {
        $family = $this->onboarding->activeFamilyFor($user);
        abort_unless($family instanceof Family, 403);

        return $family;
    }
}
