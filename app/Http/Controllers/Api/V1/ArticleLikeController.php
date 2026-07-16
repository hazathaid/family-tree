<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\ArticleLikeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ArticleLikeController extends Controller
{
    public function __construct(private readonly ArticleLikeService $service) {}

    public function store(Request $request, Article $article): JsonResponse
    {
        Gate::authorize('interact', $article);

        return response()->json(['success' => true, 'message' => 'Article liked', 'data' => $this->service->like($article, $request->user())]);
    }

    public function destroy(Request $request, Article $article): JsonResponse
    {
        Gate::authorize('interact', $article);

        return response()->json(['success' => true, 'message' => 'Article unliked', 'data' => $this->service->unlike($article, $request->user())]);
    }
}
