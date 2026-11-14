<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Relationship\MarriageEndRequest;
use App\Http\Resources\MemberRelationshipResource;
use App\Models\FamilyMember;
use App\Models\MemberRelationship;
use App\Services\MemberSpouseService;
use App\Services\RelationshipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MemberSpouseController extends Controller
{
    public function __construct(
        private readonly MemberSpouseService $spouses,
        private readonly RelationshipService $relationships,
    ) {}

    public function index(FamilyMember $familyMember): JsonResponse
    {
        Gate::authorize('view', $familyMember);

        $canManage = Gate::allows('update', $familyMember);
        $data = array_map(
            fn (array $spouse): array => [
                ...$spouse,
                'can_end' => $canManage && ! $spouse['is_ended'],
                'can_correct' => $canManage && $spouse['is_ended'],
                'can_reopen' => $canManage && $spouse['is_ended'],
            ],
            $this->spouses->summaries($familyMember),
        );

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => $data,
        ]);
    }

    public function end(
        MarriageEndRequest $request,
        FamilyMember $familyMember,
        MemberRelationship $relationship,
    ): JsonResponse {
        $this->authorizeMarriage($familyMember, $relationship);

        $updated = $this->relationships->endMarriage(
            $relationship,
            (string) $request->validated('end_date'),
            (string) $request->validated('end_reason'),
        );

        return $this->respond($updated, 'Marriage ended');
    }

    public function correct(
        MarriageEndRequest $request,
        FamilyMember $familyMember,
        MemberRelationship $relationship,
    ): JsonResponse {
        $this->authorizeMarriage($familyMember, $relationship);

        $updated = $this->relationships->correctMarriageEnd(
            $relationship,
            (string) $request->validated('end_date'),
            (string) $request->validated('end_reason'),
            $request->user(),
        );

        return $this->respond($updated, 'Marriage end corrected');
    }

    public function reopen(
        Request $request,
        FamilyMember $familyMember,
        MemberRelationship $relationship,
    ): JsonResponse {
        $this->authorizeMarriage($familyMember, $relationship);

        $updated = $this->relationships->reopenMarriage($relationship, $request->user());

        return $this->respond($updated, 'Marriage reopened');
    }

    private function authorizeMarriage(FamilyMember $member, MemberRelationship $relationship): void
    {
        Gate::authorize('update', $member);

        abort_unless(
            $relationship->family_id === $member->family_id
            && in_array($member->id, [$relationship->source_member_id, $relationship->target_member_id], true),
            404
        );
    }

    private function respond(MemberRelationship $relationship, string $message): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => new MemberRelationshipResource(
                $relationship->loadMissing(['family', 'sourceMember', 'targetMember'])
            ),
        ]);
    }
}
