<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreFamilyLinkRequest;
use App\Models\Family;
use App\Models\FamilyLink;
use App\Models\FamilyMember;
use App\Models\User;
use App\Services\FamilyLinkService;
use App\Services\LinkedFamilyTreeService;
use App\Services\WebOnboardingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class FamilyLinkController extends Controller
{
    public function __construct(
        private readonly WebOnboardingService $onboarding,
        private readonly FamilyLinkService $links,
        private readonly LinkedFamilyTreeService $partnerTrees,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $family = $this->activeFamily($user);
        Gate::authorize('view', $family);

        $member = $this->prefilledMember($request, $family);
        $term = trim($request->string('link_search')->toString());

        return view('family-links.index', [
            'family' => $family,
            'links' => $this->links->linksForFamily($family),
            'member' => $member,
            'memberOptions' => $this->links->candidateMembers($family),
            'targets' => $term === '' ? collect() : $this->links->searchLinkedCandidates($user, $family, $term),
            'searchTerm' => $term,
        ]);
    }

    public function store(StoreFamilyLinkRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $family = $this->activeFamily($user);

        $member = FamilyMember::query()->where('uuid', $request->validated('member_uuid'))->first();
        abort_unless($member instanceof FamilyMember && $member->family_id === $family->id, 404);

        $linkedMember = FamilyMember::query()->where('uuid', $request->validated('linked_member_uuid'))->first();
        abort_unless($linkedMember instanceof FamilyMember, 404);
        abort_unless($user->can('view', $linkedMember->family), 403);

        Gate::authorize('request', [FamilyLink::class, $member]);

        $this->links->request($user, $member, $linkedMember);

        return redirect()->route('family-links.index')->with('status', 'Permintaan tautan dikirim. Menunggu persetujuan keluarga '.$linkedMember->family->name.'.');
    }

    public function approve(Request $request, FamilyLink $link): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        Gate::authorize('approve', $link);

        $this->links->approve($user, $link);

        return redirect()->route('family-links.index')->with('status', 'Tautan keluarga disetujui.');
    }

    public function revoke(Request $request, FamilyLink $link): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        Gate::authorize('revoke', $link);

        $this->links->revoke($user, $link);

        return redirect()->route('family-links.index')->with('status', 'Tautan keluarga dicabut.');
    }

    public function partnerTree(Request $request, FamilyLink $link): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        Gate::authorize('view', $link);

        $family = $this->activeFamily($user);
        abort_unless($link->isActive() && $link->involvesFamily($family->id), 404);

        $anchor = $link->family_id === $family->id ? $link->linkedMember : $link->member;
        $bridge = $link->family_id === $family->id ? $link->member : $link->linkedMember;
        abort_unless($anchor instanceof FamilyMember && $bridge instanceof FamilyMember, 404);

        $tree = $this->partnerTrees->present($anchor, 'full', 4, 'vertical', $bridge);

        return response()
            ->view('family-links.partner-tree', [
                'family' => $family,
                'link' => $link,
                'anchor' => $anchor,
                'bridge' => $bridge,
                'partnerFamily' => $anchor->family,
                'tree' => $tree,
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    private function activeFamily(User $user): Family
    {
        $family = $this->onboarding->activeFamilyFor($user);
        abort_unless($family instanceof Family, 403);

        return $family;
    }

    private function prefilledMember(Request $request, Family $family): ?FamilyMember
    {
        $uuid = $request->string('member')->toString();

        if ($uuid === '') {
            return null;
        }

        $member = FamilyMember::query()
            ->where('uuid', $uuid)
            ->where('family_id', $family->id)
            ->first();

        return $member instanceof FamilyMember ? $member : null;
    }
}
