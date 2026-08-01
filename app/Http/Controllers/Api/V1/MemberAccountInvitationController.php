<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MemberAccount\AcceptMemberAccountInvitationRequest;
use App\Http\Requests\MemberAccount\InviteMemberAccountRequest;
use App\Http\Resources\MemberAccountInvitationResource;
use App\Http\Resources\UserResource;
use App\Models\FamilyMember;
use App\Models\MemberAccountInvitation;
use App\Services\MemberAccountInvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class MemberAccountInvitationController extends Controller
{
    public function __construct(private readonly MemberAccountInvitationService $invitations) {}

    public function store(InviteMemberAccountRequest $request, FamilyMember $familyMember): JsonResponse
    {
        Gate::authorize('inviteAccount', $familyMember);
        $result = $this->invitations->invite($request->user(), $familyMember, $request->validated('email'));

        return response()->json([
            'success' => true,
            'message' => 'Member account invitation sent',
            'data' => new MemberAccountInvitationResource($result['invitation']),
        ], 201);
    }

    public function show(string $token): JsonResponse
    {
        $invitation = $this->invitations->findByPlainToken($token);
        abort_unless($invitation instanceof MemberAccountInvitation, 404);

        return response()->json(['success' => true, 'message' => 'Success', 'data' => new MemberAccountInvitationResource($invitation)]);
    }

    public function accept(AcceptMemberAccountInvitationRequest $request, string $token): JsonResponse
    {
        $user = $this->invitations->accept($token, $request->validated());
        $plainTextToken = $user->createToken($request->string('device_name', 'invitation')->toString())->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Member account created',
            'data' => ['user' => new UserResource($user), 'token' => $plainTextToken],
        ], 201);
    }
}
