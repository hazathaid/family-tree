<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Photo\StoreMemberPhotoRequest;
use App\Http\Requests\Photo\TagMemberPhotoRequest;
use App\Http\Resources\MemberPhotoResource;
use App\Models\MemberPhoto;
use App\Repositories\Contracts\MemberPhotoRepositoryInterface;
use App\Services\MemberPhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MemberPhotoController extends Controller
{
    public function __construct(private readonly MemberPhotoRepositoryInterface $photos, private readonly MemberPhotoService $service) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', MemberPhoto::class);

        return response()->json(['success' => true, 'message' => 'Success', 'data' => MemberPhotoResource::collection($this->photos->paginateForUser($request->user(), $request->only(['family_uuid', 'album_uuid', 'member_uuid']), min($request->integer('limit', 15), 100)))]);
    }

    public function store(StoreMemberPhotoRequest $request): JsonResponse
    {
        Gate::authorize('create', MemberPhoto::class);

        return response()->json(['success' => true, 'message' => 'Photo uploaded', 'data' => new MemberPhotoResource($this->service->upload($request->user(), $request->validated(), $request->file('image')))], 201);
    }

    public function show(MemberPhoto $memberPhoto): JsonResponse
    {
        Gate::authorize('view', $memberPhoto);

        return response()->json(['success' => true, 'message' => 'Success', 'data' => new MemberPhotoResource($this->photos->loadDetails($memberPhoto))]);
    }

    public function tag(TagMemberPhotoRequest $request, MemberPhoto $memberPhoto): JsonResponse
    {
        Gate::authorize('update', $memberPhoto);

        return response()->json(['success' => true, 'message' => 'Photo tags updated', 'data' => new MemberPhotoResource($this->service->tag($memberPhoto, $request->validated('member_uuids')))]);
    }

    public function destroy(MemberPhoto $memberPhoto): JsonResponse
    {
        Gate::authorize('delete', $memberPhoto);
        $this->service->delete($memberPhoto);

        return response()->json(['success' => true, 'message' => 'Photo deleted', 'data' => null]);
    }
}
