<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\FamilyViewRequest;
use App\Models\Family;
use App\Models\User;
use App\Services\FamilyViewGrantService;
use App\Services\ScopedFamilyViewService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ScopedFamilyViewController extends Controller
{
    public function __construct(
        private readonly FamilyViewGrantService $grants,
        private readonly ScopedFamilyViewService $views,
    ) {}

    public function overview(Request $request, Family $family): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $grant = $this->grants->authorize($user, $family);
        $this->grants->markAccessed($user, $grant);

        return $this->noindex(response()->view('family-view.overview', [
            'grant' => $grant,
            'view' => $this->views->overview($grant),
        ]));
    }

    public function tree(FamilyViewRequest $request, Family $family): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $grant = $this->grants->authorize($user, $family);
        $this->grants->markAccessed($user, $grant);

        return $this->noindex(response()->view('family-view.tree', [
            'grant' => $grant,
            'view' => $this->views->tree(
                $grant,
                (int) $request->integer('depth', 4),
                (string) $request->string('layout', 'vertical'),
            ),
            'filters' => [
                'depth' => (int) $request->integer('depth', 4),
                'layout' => (string) $request->string('layout', 'vertical'),
            ],
        ]));
    }

    private function noindex(Response $response): Response
    {
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
