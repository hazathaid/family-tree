<?php

namespace App\Http\Controllers\Web;

use App\DTOs\EventData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Event\RsvpEventRequest;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Http\Requests\Web\WebActionRequest;
use App\Models\Event;
use App\Models\Family;
use App\Services\EventService;
use App\Services\WebEngagementService;
use App\Services\WebOnboardingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EventController extends Controller
{
    public function __construct(private readonly WebOnboardingService $onboarding, private readonly WebEngagementService $presentation, private readonly EventService $events) {}

    public function index(Request $request): View
    {
        $family = $this->family($request);
        Gate::authorize('viewAny', Event::class);

        return view('events.index', ['family' => $family, ...$this->presentation->events($family, $request->user(), $request->only(['upcoming', 'search']))]);
    }

    public function create(Request $request): View
    {
        $family = $this->family($request);
        Gate::authorize('create', Event::class);

        return view('events.form', ['family' => $family, 'event' => null]);
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $family = $this->family($request);
        abort_unless($request->validated('family_uuid') === $family->uuid, 403);
        Gate::authorize('update', $family);
        $event = $this->events->create($request->user(), EventData::fromArray($request->validated()));

        return redirect()->route('events.show', $event)->with('status', 'Acara berhasil dibuat.');
    }

    public function show(Request $request, Event $event): View
    {
        $this->sameFamily($request, $event);
        Gate::authorize('view', $event);

        return view('events.show', ['event' => $this->presentation->event($event, $request->user())]);
    }

    public function edit(Request $request, Event $event): View
    {
        $family = $this->sameFamily($request, $event);
        Gate::authorize('update', $event);

        return view('events.form', compact('family', 'event'));
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $this->sameFamily($request, $event);
        Gate::authorize('update', $event);
        $this->events->update($event, $request->validated(), $request->user());

        return redirect()->route('events.show', $event)->with('status', 'Acara diperbarui.');
    }

    public function destroy(WebActionRequest $request, Event $event): RedirectResponse
    {
        $this->sameFamily($request, $event);
        Gate::authorize('delete', $event);
        $this->events->delete($event);

        return redirect()->route('events.index')->with('status', 'Acara dihapus.');
    }

    public function rsvp(RsvpEventRequest $request, Event $event): RedirectResponse
    {
        $this->sameFamily($request, $event);
        Gate::authorize('rsvp', $event);
        $this->events->rsvp($event, $request->user(), $request->validated('status'));

        return back()->with('status', 'RSVP diperbarui.');
    }

    private function family(Request $request): Family
    {
        return $this->onboarding->activeFamilyFor($request->user()) ?? abort(403);
    }

    private function sameFamily(Request $request, Event $event): Family
    {
        $family = $this->family($request);
        abort_unless($family->id === $event->family_id, 404);

        return $family;
    }
}
