<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Article\StoreArticleCategoryRequest;
use App\Http\Requests\Article\UpdateArticleCategoryRequest;
use App\Http\Resources\ArticleCategoryResource;
use App\Models\ArticleCategory;
use App\Repositories\Contracts\ArticleCategoryRepositoryInterface;
use App\Services\ArticleCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ArticleCategoryController extends Controller
{
    public function __construct(private readonly ArticleCategoryRepositoryInterface $categories, private readonly ArticleCategoryService $service) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', ArticleCategory::class);

        return response()->json(['success' => true, 'message' => 'Success', 'data' => ArticleCategoryResource::collection($this->categories->paginate($request->string('search')->toString() ?: null, min($request->integer('limit', 15), 100)))]);
    }

    public function store(StoreArticleCategoryRequest $request): JsonResponse
    {
        Gate::authorize('create', ArticleCategory::class);

        return response()->json(['success' => true, 'message' => 'Category created', 'data' => new ArticleCategoryResource($this->service->create($request->validated()))], 201);
    }

    public function show(ArticleCategory $articleCategory): JsonResponse
    {
        Gate::authorize('view', $articleCategory);

        return response()->json(['success' => true, 'message' => 'Success', 'data' => new ArticleCategoryResource($articleCategory)]);
    }

    public function update(UpdateArticleCategoryRequest $request, ArticleCategory $articleCategory): JsonResponse
    {
        Gate::authorize('update', $articleCategory);

        return response()->json(['success' => true, 'message' => 'Category updated', 'data' => new ArticleCategoryResource($this->service->update($articleCategory, $request->validated()))]);
    }

    public function destroy(ArticleCategory $articleCategory): JsonResponse
    {
        Gate::authorize('delete', $articleCategory);
        $this->service->delete($articleCategory);

        return response()->json(['success' => true, 'message' => 'Category deleted', 'data' => null]);
    }
}
