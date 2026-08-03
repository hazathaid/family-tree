<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\FamilyMember\StoreTreeRelativeRequest;
use App\Http\Resources\FamilyMemberResource;
use App\Models\FamilyMember;
use App\Services\TreeRelativeMemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class TreeRelativeMemberController extends Controller
{
    public function __construct(private readonly TreeRelativeMemberService $relatives) {}

    public function store(StoreTreeRelativeRequest $request, FamilyMember $familyMember): JsonResponse
    {
        Gate::authorize('addRelative', $familyMember);

        $relative = $this->relatives->create($request->user(), $familyMember, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Family tree relative created',
            'data' => new FamilyMemberResource($relative),
        ], 201);
    }
}
