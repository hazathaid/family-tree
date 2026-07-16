<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Models\Family;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use App\Services\FeaturedArticleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FeaturedArticleController extends Controller
{
    public function __construct(private readonly ArticleRepositoryInterface $articles, private readonly FeaturedArticleService $service) {}

    public function index(Request $request, Family $family): JsonResponse
    {
        Gate::authorize('view', $family);

        return response()->json(['success' => true, 'message' => 'Success', 'data' => ArticleResource::collection($this->articles->featured($family, min($request->integer('limit', 15), 100)))]);
    }

    public function store(Article $article): JsonResponse
    {
        Gate::authorize('feature', $article);

        return response()->json(['success' => true, 'message' => 'Article featured', 'data' => new ArticleResource($this->articles->loadDetails($this->service->feature($article)))]);
    }

    public function destroy(Article $article): JsonResponse
    {
        Gate::authorize('feature', $article);

        return response()->json(['success' => true, 'message' => 'Article unfeatured', 'data' => new ArticleResource($this->articles->loadDetails($this->service->unfeature($article)))]);
    }
}
