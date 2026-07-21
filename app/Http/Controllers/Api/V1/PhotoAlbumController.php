<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Photo\StorePhotoAlbumRequest;
use App\Http\Requests\Photo\UpdatePhotoAlbumRequest;
use App\Http\Resources\PhotoAlbumResource;
use App\Models\PhotoAlbum;
use App\Repositories\Contracts\PhotoAlbumRepositoryInterface;
use App\Services\PhotoAlbumService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PhotoAlbumController extends Controller
{
    public function __construct(private readonly PhotoAlbumRepositoryInterface $albums, private readonly PhotoAlbumService $service) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', PhotoAlbum::class);

        return response()->json(['success' => true, 'message' => 'Success', 'data' => PhotoAlbumResource::collection($this->albums->paginateForUser($request->user(), $request->string('family_uuid')->toString() ?: null, min($request->integer('limit', 15), 100)))]);
    }

    public function store(StorePhotoAlbumRequest $request): JsonResponse
    {
        Gate::authorize('create', PhotoAlbum::class);

        return response()->json(['success' => true, 'message' => 'Album created', 'data' => new PhotoAlbumResource($this->service->create($request->user(), $request->validated()))], 201);
    }

    public function show(PhotoAlbum $photoAlbum): JsonResponse
    {
        Gate::authorize('view', $photoAlbum);

        return response()->json(['success' => true, 'message' => 'Success', 'data' => new PhotoAlbumResource($photoAlbum->load(['family', 'creator'])->loadCount('photos'))]);
    }

    public function update(UpdatePhotoAlbumRequest $request, PhotoAlbum $photoAlbum): JsonResponse
    {
        Gate::authorize('update', $photoAlbum);

        return response()->json(['success' => true, 'message' => 'Album updated', 'data' => new PhotoAlbumResource($this->service->update($photoAlbum, $request->validated()))]);
    }

    public function destroy(PhotoAlbum $photoAlbum): JsonResponse
    {
        Gate::authorize('delete', $photoAlbum);
        $this->service->delete($photoAlbum);

        return response()->json(['success' => true, 'message' => 'Album deleted', 'data' => null]);
    }
}
