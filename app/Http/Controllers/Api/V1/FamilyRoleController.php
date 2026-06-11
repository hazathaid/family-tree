<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\FamilyRoleData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Family\AssignFamilyRoleRequest;
use App\Http\Requests\Family\InviteFamilyMemberRequest;
use App\Http\Resources\FamilyUserRoleResource;
use App\Models\Family;
use App\Models\FamilyUserRole;
use App\Services\FamilyRoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class FamilyRoleController extends Controller
{
    public function __construct(
        private readonly FamilyRoleService $familyRoles,
    ) {}

    public function index(Family $family): JsonResponse
    {
        Gate::authorize('manageRoles', $family);

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => FamilyUserRoleResource::collection($this->familyRoles->list($family)),
        ]);
    }

    public function invite(InviteFamilyMemberRequest $request, Family $family): JsonResponse
    {
        Gate::authorize('manageRoles', $family);

        $membership = $this->familyRoles->invite($family, FamilyRoleData::fromArray($request->validated()));

        return response()->json([
            'success' => true,
            'message' => 'Family member invited',
            'data' => new FamilyUserRoleResource($membership),
        ], 201);
    }

    public function assign(AssignFamilyRoleRequest $request, Family $family, FamilyUserRole $membership): JsonResponse
    {
        Gate::authorize('manageRoles', $family);

        $membership = $this->familyRoles->assignRole($family, $membership, $request->validated('role'));

        return response()->json([
            'success' => true,
            'message' => 'Family role assigned',
            'data' => new FamilyUserRoleResource($membership),
        ]);
    }

    public function remove(Family $family, FamilyUserRole $membership): JsonResponse
    {
        Gate::authorize('manageRoles', $family);

        $this->familyRoles->removeMember($family, $membership);

        return response()->json([
            'success' => true,
            'message' => 'Family member removed',
            'data' => null,
        ]);
    }
}
