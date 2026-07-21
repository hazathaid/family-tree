<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Photo\StoreMemberPhotoRequest;
use App\Http\Requests\Photo\StorePhotoAlbumRequest;
use App\Http\Requests\Photo\TagMemberPhotoRequest;
use App\Http\Requests\Web\WebActionRequest;
use App\Models\Family;
use App\Models\MemberPhoto;
use App\Models\PhotoAlbum;
use App\Services\MemberPhotoService;
use App\Services\PhotoAlbumService;
use App\Services\WebEngagementService;
use App\Services\WebOnboardingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PhotoController extends Controller
{
    public function __construct(private readonly WebOnboardingService $onboarding, private readonly WebEngagementService $presentation, private readonly MemberPhotoService $photos, private readonly PhotoAlbumService $albums) {}

    public function index(Request $request): View
    {
        $family = $this->family($request);
        Gate::authorize('viewAny', MemberPhoto::class);

        return view('photos.index', ['family' => $family, ...$this->presentation->photos($family, $request->user(), $request->only(['album_uuid', 'member_uuid']))]);
    }

    public function create(Request $request): View
    {
        $family = $this->family($request);
        Gate::authorize('create', MemberPhoto::class);

        return view('photos.create', ['family' => $family, ...$this->presentation->photoForm($family, $request->user())]);
    }

    public function store(StoreMemberPhotoRequest $request): RedirectResponse
    {
        $family = $this->family($request);
        abort_unless($request->validated('family_uuid') === $family->uuid, 403);
        Gate::authorize('create', MemberPhoto::class);
        $photo = $this->photos->upload($request->user(), $request->validated(), $request->file('image'));

        return redirect()->route('photos.show', $photo)->with('status', 'Foto berhasil diunggah.');
    }

    public function show(Request $request, MemberPhoto $photo): View
    {
        $this->sameFamily($request, $photo->family_id);
        Gate::authorize('view', $photo);

        return view('photos.show', ['photo' => $this->presentation->photo($photo), ...$this->presentation->photoForm($photo->family, $request->user())]);
    }

    public function tag(TagMemberPhotoRequest $request, MemberPhoto $photo): RedirectResponse
    {
        $this->sameFamily($request, $photo->family_id);
        Gate::authorize('update', $photo);
        $this->photos->tag($photo, $request->validated('member_uuids'));

        return back()->with('status', 'Tag anggota diperbarui.');
    }

    public function destroy(WebActionRequest $request, MemberPhoto $photo): RedirectResponse
    {
        $this->sameFamily($request, $photo->family_id);
        Gate::authorize('delete', $photo);
        $this->photos->delete($photo);

        return redirect()->route('photos.index')->with('status', 'Foto dihapus.');
    }

    public function storeAlbum(StorePhotoAlbumRequest $request): RedirectResponse
    {
        $family = $this->family($request);
        abort_unless($request->validated('family_uuid') === $family->uuid, 403);
        Gate::authorize('create', PhotoAlbum::class);
        $album = $this->albums->create($request->user(), $request->validated());

        return redirect()->route('albums.show', $album)->with('status', 'Album dibuat.');
    }

    public function showAlbum(Request $request, PhotoAlbum $album): View
    {
        $this->sameFamily($request, $album->family_id);
        Gate::authorize('view', $album);

        return view('photos.album', $this->presentation->album($album, $request->user()));
    }

    private function family(Request $request): Family
    {
        return $this->onboarding->activeFamilyFor($request->user()) ?? abort(403);
    }

    private function sameFamily(Request $request, int $id): Family
    {
        $family = $this->family($request);
        abort_unless($family->id === $id, 404);

        return $family;
    }
}
