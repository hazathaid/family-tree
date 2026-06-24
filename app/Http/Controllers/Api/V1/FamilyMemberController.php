<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\FamilyMember\StoreFamilyMemberRequest;
use App\Http\Requests\FamilyMember\UpdateFamilyMemberRequest;
use App\Http\Requests\FamilyMember\UploadFamilyMemberPhotoRequest;
use App\Http\Resources\FamilyMemberResource;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Repositories\Contracts\FamilyMemberRepositoryInterface;
use App\Repositories\Contracts\FamilyRepositoryInterface;
use App\Services\FamilyMemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FamilyMemberController extends Controller
{
    public function __construct(
        private readonly FamilyMemberRepositoryInterface $members,
        private readonly FamilyRepositoryInterface $families,
        private readonly FamilyMemberService $memberService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', FamilyMember::class);

        $members = $this->members->paginateForUser(
            $request->user(),
            (int) $request->integer('limit', 15),
        );

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => FamilyMemberResource::collection($members),
        ]);
    }

    public function store(StoreFamilyMemberRequest $request): JsonResponse
    {
        $family = $this->families->findByUuid($request->validated('family_uuid'));
        abort_if(! $family instanceof Family, 404);

        Gate::authorize('create', [FamilyMember::class, $family]);

        $member = $this->memberService->create($request->user(), $family, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Family member created',
            'data' => new FamilyMemberResource($member),
        ], 201);
    }

    public function show(FamilyMember $familyMember): JsonResponse
    {
        Gate::authorize('view', $familyMember);

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => new FamilyMemberResource($familyMember->load(['family', 'branch'])),
        ]);
    }

    public function update(UpdateFamilyMemberRequest $request, FamilyMember $familyMember): JsonResponse
    {
        Gate::authorize('update', $familyMember);

        $member = $this->memberService->update($familyMember, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Family member updated',
            'data' => new FamilyMemberResource($member),
        ]);
    }

    public function destroy(FamilyMember $familyMember): JsonResponse
    {
        Gate::authorize('delete', $familyMember);

        $this->memberService->delete($familyMember);

        return response()->json([
            'success' => true,
            'message' => 'Family member deleted',
            'data' => null,
        ]);
    }

    public function uploadPhoto(UploadFamilyMemberPhotoRequest $request, FamilyMember $familyMember): JsonResponse
    {
        Gate::authorize('update', $familyMember);

        $member = $this->memberService->uploadPhoto($familyMember, $request->file('photo'));

        return response()->json([
            'success' => true,
            'message' => 'Family member photo uploaded',
            'data' => new FamilyMemberResource($member),
        ]);
    }
}
