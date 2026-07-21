<?php

namespace App\Http\Controllers\Web;

use App\DTOs\ArticleData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Article\StoreArticleCommentRequest;
use App\Http\Requests\Article\StoreArticleRequest;
use App\Http\Requests\Article\UpdateArticleRequest;
use App\Http\Requests\Article\UploadArticleImageRequest;
use App\Http\Requests\Web\WebActionRequest;
use App\Models\Article;
use App\Models\Family;
use App\Services\ArticleCommentService;
use App\Services\ArticleLikeService;
use App\Services\ArticleService;
use App\Services\WebEngagementService;
use App\Services\WebOnboardingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ArticleController extends Controller
{
    public function __construct(private readonly WebOnboardingService $onboarding, private readonly WebEngagementService $presentation, private readonly ArticleService $articles, private readonly ArticleCommentService $comments, private readonly ArticleLikeService $likes) {}

    public function index(Request $request): View
    {
        $family = $this->family($request);
        Gate::authorize('viewAny', Article::class);

        return view('articles.index', ['family' => $family, ...$this->presentation->articles($family, $request->user(), $request->only(['category_uuid', 'status', 'featured', 'search']))]);
    }

    public function create(Request $request): View
    {
        $family = $this->family($request);
        Gate::authorize('create', Article::class);

        return view('articles.form', ['family' => $family, 'article' => null, ...$this->presentation->articleForm()]);
    }

    public function store(StoreArticleRequest $request): RedirectResponse
    {
        $family = $this->family($request);
        Gate::authorize('create', Article::class);
        abort_unless($request->validated('family_uuid') === $family->uuid, 403);
        $article = $this->articles->create($request->user(), ArticleData::fromArray($request->validated()));

        return redirect()->route('articles.show', $article)->with('status', 'Artikel berhasil disimpan.');
    }

    public function show(Request $request, Article $article): View
    {
        $this->sameFamily($request, $article->family_id);
        Gate::authorize('view', $article);

        return view('articles.show', $this->presentation->article($article, $request->user()));
    }

    public function edit(Request $request, Article $article): View
    {
        $family = $this->sameFamily($request, $article->family_id);
        Gate::authorize('update', $article);

        return view('articles.form', ['family' => $family, 'article' => $article, ...$this->presentation->articleForm()]);
    }

    public function update(UpdateArticleRequest $request, Article $article): RedirectResponse
    {
        $this->sameFamily($request, $article->family_id);
        Gate::authorize('update', $article);
        $this->articles->update($article, $request->validated(), $request->user());

        return redirect()->route('articles.show', $article)->with('status', 'Artikel berhasil diperbarui.');
    }

    public function destroy(WebActionRequest $request, Article $article): RedirectResponse
    {
        $this->sameFamily($request, $article->family_id);
        Gate::authorize('delete', $article);
        $this->articles->delete($article);

        return redirect()->route('articles.index')->with('status', 'Artikel berhasil dihapus.');
    }

    public function publish(WebActionRequest $request, Article $article): RedirectResponse
    {
        $this->sameFamily($request, $article->family_id);
        Gate::authorize('update', $article);
        $this->articles->publish($article, $request->user());

        return back()->with('status', 'Artikel berhasil diterbitkan.');
    }

    public function comment(StoreArticleCommentRequest $request, Article $article): RedirectResponse
    {
        $this->sameFamily($request, $article->family_id);
        Gate::authorize('interact', $article);
        $this->comments->create($article, $request->user(), $request->validated('comment'));

        return back()->with('status', 'Komentar ditambahkan.');
    }

    public function image(UploadArticleImageRequest $request, Article $article): RedirectResponse
    {
        $this->sameFamily($request, $article->family_id);
        Gate::authorize('update', $article);
        $this->articles->uploadImage($article, $request->file('image'), $request->user());

        return back()->with('status', 'Sampul artikel diperbarui.');
    }

    public function like(WebActionRequest $request, Article $article): RedirectResponse
    {
        $this->sameFamily($request, $article->family_id);
        Gate::authorize('interact', $article);
        $this->likes->like($article, $request->user());

        return back();
    }

    private function family(Request $request): Family
    {
        return $this->onboarding->activeFamilyFor($request->user()) ?? abort(403);
    }

    private function sameFamily(Request $request, int $familyId): Family
    {
        $family = $this->family($request);
        abort_unless($family->id === $familyId, 404);

        return $family;
    }
}
