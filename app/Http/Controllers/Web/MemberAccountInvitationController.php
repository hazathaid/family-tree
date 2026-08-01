<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\MemberAccount\AcceptMemberAccountInvitationRequest;
use App\Http\Requests\MemberAccount\InviteMemberAccountRequest;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\MemberAccountInvitation;
use App\Services\MemberAccountInvitationService;
use App\Services\WebOnboardingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class MemberAccountInvitationController extends Controller
{
    public function __construct(private readonly MemberAccountInvitationService $invitations) {}

    public function store(InviteMemberAccountRequest $request, FamilyMember $member): RedirectResponse
    {
        Gate::authorize('inviteAccount', $member);
        $this->invitations->invite($request->user(), $member, $request->validated('email'));

        return back()->with('status', 'Undangan akun dikirim ke '.$request->validated('email').'.');
    }

    public function show(string $token): View
    {
        $invitation = $this->invitations->findByPlainToken($token);
        abort_unless($invitation instanceof MemberAccountInvitation, 404);

        return view('auth.accept-member-invitation', compact('invitation', 'token'));
    }

    public function accept(AcceptMemberAccountInvitationRequest $request, string $token): RedirectResponse
    {
        $user = $this->invitations->accept($token, $request->validated());
        Auth::login($user);
        $request->session()->regenerate();
        $membership = FamilyUserRole::query()->with('family')->where('user_id', $user->id)->firstOrFail();
        $request->session()->put(WebOnboardingService::ACTIVE_FAMILY_KEY, $membership->family->uuid);

        return redirect()->route('dashboard')->with('status', 'Akun berhasil dibuat dan profil keluarga telah terhubung.');
    }
}
