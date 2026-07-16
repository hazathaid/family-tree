<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Article\StoreArticleCommentRequest;
use App\Http\Requests\Article\UpdateArticleCommentRequest;
use App\Http\Resources\ArticleCommentResource;
use App\Models\Article;
use App\Models\ArticleComment;
use App\Repositories\Contracts\ArticleCommentRepositoryInterface;
use App\Services\ArticleCommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ArticleCommentController extends Controller
{
    public function __construct(private readonly ArticleCommentRepositoryInterface $comments, private readonly ArticleCommentService $service) {}

    public function index(Request $request, Article $article): JsonResponse
    {
        Gate::authorize('view', $article);

        return response()->json(['success' => true, 'message' => 'Success', 'data' => ArticleCommentResource::collection($this->comments->paginate($article, min($request->integer('limit', 15), 100)))]);
    }

    public function store(StoreArticleCommentRequest $request, Article $article): JsonResponse
    {
        Gate::authorize('interact', $article);

        return response()->json(['success' => true, 'message' => 'Comment created', 'data' => new ArticleCommentResource($this->service->create($article, $request->user(), $request->validated('comment')))], 201);
    }

    public function update(UpdateArticleCommentRequest $request, Article $article, ArticleComment $comment): JsonResponse
    {
        abort_unless($comment->article_id === $article->id, 404);
        Gate::authorize('update', $comment);

        return response()->json(['success' => true, 'message' => 'Comment updated', 'data' => new ArticleCommentResource($this->service->update($comment, $request->validated('comment')))]);
    }

    public function destroy(Article $article, ArticleComment $comment): JsonResponse
    {
        abort_unless($comment->article_id === $article->id, 404);
        Gate::authorize('delete', $comment);
        $this->service->delete($comment);

        return response()->json(['success' => true, 'message' => 'Comment deleted', 'data' => null]);
    }
}
