<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\ArticleData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Article\StoreArticleRequest;
use App\Http\Requests\Article\UpdateArticleRequest;
use App\Http\Requests\Article\UploadArticleImageRequest;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use App\Services\ArticleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ArticleController extends Controller
{
    public function __construct(private readonly ArticleRepositoryInterface $articles, private readonly ArticleService $service) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Article::class);
        $filters = $request->only(['family_uuid', 'category_uuid', 'status', 'featured', 'search']);

        return response()->json(['success' => true, 'message' => 'Success', 'data' => ArticleResource::collection($this->articles->paginateForUser($request->user(), $filters, min($request->integer('limit', 15), 100)))]);
    }

    public function store(StoreArticleRequest $request): JsonResponse
    {
        Gate::authorize('create', Article::class);

        return response()->json(['success' => true, 'message' => 'Article created', 'data' => new ArticleResource($this->service->create($request->user(), ArticleData::fromArray($request->validated())))], 201);
    }

    public function show(Request $request, Article $article): JsonResponse
    {
        Gate::authorize('view', $article);

        return response()->json(['success' => true, 'message' => 'Success', 'data' => new ArticleResource($this->articles->loadDetails($article, $request->user()))]);
    }

    public function update(UpdateArticleRequest $request, Article $article): JsonResponse
    {
        Gate::authorize('update', $article);

        return response()->json(['success' => true, 'message' => 'Article updated', 'data' => new ArticleResource($this->service->update($article, $request->validated(), $request->user()))]);
    }

    public function destroy(Article $article): JsonResponse
    {
        Gate::authorize('delete', $article);
        $this->service->delete($article);

        return response()->json(['success' => true, 'message' => 'Article deleted', 'data' => null]);
    }

    public function publish(Request $request, Article $article): JsonResponse
    {
        Gate::authorize('update', $article);

        return response()->json(['success' => true, 'message' => 'Article published', 'data' => new ArticleResource($this->service->publish($article, $request->user()))]);
    }

    public function image(UploadArticleImageRequest $request, Article $article): JsonResponse
    {
        Gate::authorize('update', $article);

        return response()->json(['success' => true, 'message' => 'Featured image updated', 'data' => new ArticleResource($this->service->uploadImage($article, $request->file('image'), $request->user()))]);
    }
}
