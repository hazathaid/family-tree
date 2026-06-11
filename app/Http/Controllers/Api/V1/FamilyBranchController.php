<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\FamilyBranchData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Family\StoreFamilyBranchRequest;
use App\Http\Requests\Family\UpdateFamilyBranchRequest;
use App\Http\Resources\FamilyBranchResource;
use App\Models\Family;
use App\Models\FamilyBranch;
use App\Repositories\Contracts\FamilyBranchRepositoryInterface;
use App\Services\FamilyBranchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FamilyBranchController extends Controller
{
    public function __construct(
        private readonly FamilyBranchRepositoryInterface $branches,
        private readonly FamilyBranchService $branchService,
    ) {}

    public function index(Request $request, Family $family): JsonResponse
    {
        Gate::authorize('view', $family);

        $branches = $this->branches->paginateForFamily(
            $family,
            (int) $request->integer('limit', 15),
        );

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => FamilyBranchResource::collection($branches),
        ]);
    }

    public function store(StoreFamilyBranchRequest $request, Family $family): JsonResponse
    {
        Gate::authorize('manageBranches', $family);

        $branch = $this->branchService->create($family, FamilyBranchData::fromArray($request->validated()));

        return response()->json([
            'success' => true,
            'message' => 'Family branch created',
            'data' => new FamilyBranchResource($branch),
        ], 201);
    }

    public function show(Family $family, FamilyBranch $branch): JsonResponse
    {
        Gate::authorize('view', $family);
        $this->branchService->ensureBranchBelongsToFamily($family, $branch);

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => new FamilyBranchResource($branch),
        ]);
    }

    public function update(UpdateFamilyBranchRequest $request, Family $family, FamilyBranch $branch): JsonResponse
    {
        Gate::authorize('manageBranches', $family);

        $branch = $this->branchService->update($family, $branch, FamilyBranchData::fromArray($request->validated()));

        return response()->json([
            'success' => true,
            'message' => 'Family branch updated',
            'data' => new FamilyBranchResource($branch),
        ]);
    }

    public function destroy(Family $family, FamilyBranch $branch): JsonResponse
    {
        Gate::authorize('manageBranches', $family);

        $this->branchService->delete($family, $branch);

        return response()->json([
            'success' => true,
            'message' => 'Family branch deleted',
            'data' => null,
        ]);
    }
}
