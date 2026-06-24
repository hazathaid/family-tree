<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Relationship\StoreRelationshipRequest;
use App\Http\Requests\Relationship\UpdateRelationshipRequest;
use App\Http\Resources\MemberRelationshipResource;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\MemberRelationship;
use App\Repositories\Contracts\FamilyMemberRepositoryInterface;
use App\Repositories\Contracts\FamilyRepositoryInterface;
use App\Repositories\Contracts\RelationshipRepositoryInterface;
use App\Services\RelationshipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RelationshipController extends Controller
{
    public function __construct(
        private readonly RelationshipRepositoryInterface $relationships,
        private readonly FamilyRepositoryInterface $families,
        private readonly FamilyMemberRepositoryInterface $members,
        private readonly RelationshipService $relationshipService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', FamilyMember::class);

        $relationships = $this->relationships->paginateForUser(
            $request->user(),
            $this->filters($request),
            (int) $request->integer('limit', 15),
        );

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => MemberRelationshipResource::collection($relationships),
        ]);
    }

    public function store(StoreRelationshipRequest $request): JsonResponse
    {
        $family = $this->familyFromRequest($request);
        Gate::authorize('create', [FamilyMember::class, $family]);

        $relationship = $this->relationshipService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Relationship created',
            'data' => new MemberRelationshipResource($relationship),
        ], 201);
    }

    public function show(MemberRelationship $relationship): JsonResponse
    {
        Gate::authorize('view', $relationship->sourceMember);

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => new MemberRelationshipResource($relationship->load(['family', 'sourceMember', 'targetMember'])),
        ]);
    }

    public function update(UpdateRelationshipRequest $request, MemberRelationship $relationship): JsonResponse
    {
        Gate::authorize('update', $relationship->sourceMember);

        $updated = $this->relationshipService->update($relationship, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Relationship updated',
            'data' => new MemberRelationshipResource($updated),
        ]);
    }

    public function destroy(MemberRelationship $relationship): JsonResponse
    {
        Gate::authorize('delete', $relationship->sourceMember);

        $this->relationshipService->delete($relationship);

        return response()->json([
            'success' => true,
            'message' => 'Relationship deleted',
            'data' => null,
        ]);
    }

    private function familyFromRequest(Request $request): Family
    {
        if ($request->filled('family_uuid')) {
            $family = $this->families->findByUuid((string) $request->input('family_uuid'));
        } else {
            $family = Family::query()->find($request->integer('family_id'));
        }

        abort_if(! $family instanceof Family, 404);

        return $family;
    }

    private function filters(Request $request): array
    {
        $familyId = null;
        $memberId = null;

        if ($request->filled('family_uuid')) {
            $familyId = $this->families->findByUuid((string) $request->input('family_uuid'))?->id;
        } elseif ($request->filled('family_id')) {
            $familyId = $request->integer('family_id');
        }

        if ($request->filled('member_uuid')) {
            $memberId = $this->members->findByUuid((string) $request->input('member_uuid'))?->id;
        } elseif ($request->filled('member_id')) {
            $memberId = $request->integer('member_id');
        }

        return array_filter([
            'family_id' => $familyId,
            'member_id' => $memberId,
        ]);
    }
}
