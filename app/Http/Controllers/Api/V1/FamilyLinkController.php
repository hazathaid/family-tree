<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\FamilyLink\ListFamilyLinkCandidatesRequest;
use App\Http\Requests\FamilyLink\PartnerTreeRequest;
use App\Http\Requests\FamilyLink\StoreFamilyLinkRequest;
use App\Http\Resources\FamilyLinkCandidateResource;
use App\Http\Resources\FamilyLinkResource;
use App\Http\Resources\TreeResource;
use App\Models\Family;
use App\Models\FamilyLink;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\User;
use App\Repositories\Contracts\FamilyUserRoleRepositoryInterface;
use App\Services\FamilyLinkService;
use App\Services\LinkedFamilyTreeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class FamilyLinkController extends Controller
{
    public function __construct(
        private readonly FamilyLinkService $links,
        private readonly LinkedFamilyTreeService $partnerTrees,
        private readonly FamilyUserRoleRepositoryInterface $familyRoles,
    ) {}

    public function index(Request $request, Family $family): JsonResponse
    {
        Gate::authorize('view', $family);

        return $this->success(
            'Success',
            FamilyLinkResource::collection($this->links->linksForFamily($family)),
        );
    }

    public function candidates(ListFamilyLinkCandidatesRequest $request, Family $family): JsonResponse
    {
        Gate::authorize('update', $family);
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $candidates = $this->links->searchLinkedCandidates(
            $user,
            $family,
            $request->validated('search'),
            (int) $request->integer('limit', 25),
        );

        return $this->success('Success', FamilyLinkCandidateResource::collection($candidates));
    }

    public function store(StoreFamilyLinkRequest $request, Family $family): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $member = FamilyMember::query()
            ->where('uuid', $request->validated('member_uuid'))
            ->where('family_id', $family->id)
            ->first();
        abort_unless($member instanceof FamilyMember, 404);

        $linkedMember = FamilyMember::query()
            ->where('uuid', $request->validated('linked_member_uuid'))
            ->first();
        abort_unless($linkedMember instanceof FamilyMember, 404);
        abort_unless($user->can('view', $linkedMember->family), 403);

        Gate::authorize('request', [FamilyLink::class, $member]);

        $link = $this->links->request($user, $member, $linkedMember);

        return $this->success('Family link requested', new FamilyLinkResource($link), 201);
    }

    public function show(FamilyLink $link): JsonResponse
    {
        Gate::authorize('view', $link);

        return $this->success('Success', new FamilyLinkResource($link));
    }

    public function approve(Request $request, FamilyLink $link): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        Gate::authorize('approve', $link);

        return $this->success('Family link approved', new FamilyLinkResource($this->links->approve($user, $link)));
    }

    public function revoke(Request $request, FamilyLink $link): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        Gate::authorize('revoke', $link);

        return $this->success('Family link revoked', new FamilyLinkResource($this->links->revoke($user, $link)));
    }

    public function partnerTree(PartnerTreeRequest $request, FamilyLink $link): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        Gate::authorize('view', $link);

        abort_unless($link->isActive(), 404);

        $family = $this->viewingFamily($user, $link, $request->validated('family_uuid'));
        abort_unless($link->involvesFamily($family->id), 404);

        $anchor = $family->id === $link->family_id ? $link->linkedMember : $link->member;
        $bridge = $family->id === $link->family_id ? $link->member : $link->linkedMember;
        abort_unless($anchor instanceof FamilyMember && $bridge instanceof FamilyMember, 404);

        $tree = $this->partnerTrees->present(
            $anchor,
            (string) $request->string('mode', 'full'),
            (int) $request->integer('depth', 4),
            (string) $request->string('layout', 'vertical'),
            $bridge,
        );

        return $this->success('Success', (new TreeResource($tree))->resolve($request))
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    private function viewingFamily(User $user, FamilyLink $link, ?string $familyUuid): Family
    {
        if ($familyUuid !== null && $familyUuid !== '') {
            $family = Family::query()->where('uuid', $familyUuid)->first();
            abort_unless($family instanceof Family, 404);
            abort_unless($link->involvesFamily($family->id), 404);
            abort_unless($this->familyRoles->findActive($family, $user) instanceof FamilyUserRole, 403);

            return $family;
        }

        /** @var Collection<int, Family|null> $families */
        $families = collect([$link->family, $link->linkedFamily]);

        foreach ($families as $family) {
            if ($family instanceof Family && $this->familyRoles->findActive($family, $user) instanceof FamilyUserRole) {
                return $family;
            }
        }

        abort(403);
    }

    private function success(string $message, mixed $data, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
