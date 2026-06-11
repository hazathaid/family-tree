<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\FamilyData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Family\StoreFamilyRequest;
use App\Http\Requests\Family\UpdateFamilyRequest;
use App\Http\Resources\FamilyResource;
use App\Models\Family;
use App\Repositories\Contracts\FamilyRepositoryInterface;
use App\Services\FamilyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FamilyController extends Controller
{
    public function __construct(
        private readonly FamilyRepositoryInterface $families,
        private readonly FamilyService $familyService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Family::class);

        $families = $this->families->paginateForUser(
            $request->user(),
            (int) $request->integer('limit', 15),
        );

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => FamilyResource::collection($families),
        ]);
    }

    public function store(StoreFamilyRequest $request): JsonResponse
    {
        Gate::authorize('create', Family::class);

        $family = $this->familyService->create($request->user(), FamilyData::fromArray($request->validated()));

        return response()->json([
            'success' => true,
            'message' => 'Family created',
            'data' => new FamilyResource($family),
        ], 201);
    }

    public function show(Family $family): JsonResponse
    {
        Gate::authorize('view', $family);

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => new FamilyResource($family),
        ]);
    }

    public function update(UpdateFamilyRequest $request, Family $family): JsonResponse
    {
        Gate::authorize('update', $family);

        $family = $this->familyService->update($family, FamilyData::fromArray($request->validated()));

        return response()->json([
            'success' => true,
            'message' => 'Family updated',
            'data' => new FamilyResource($family),
        ]);
    }

    public function destroy(Family $family): JsonResponse
    {
        Gate::authorize('delete', $family);

        $this->familyService->delete($family);

        return response()->json([
            'success' => true,
            'message' => 'Family deleted',
            'data' => null,
        ]);
    }
}
