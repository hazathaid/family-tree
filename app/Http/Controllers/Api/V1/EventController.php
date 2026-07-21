<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\EventData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Event\RsvpEventRequest;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Http\Resources\EventAttendeeResource;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\Family;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Services\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EventController extends Controller
{
    public function __construct(private readonly EventRepositoryInterface $events, private readonly EventService $service) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Event::class);
        $filters = $request->only(['family_uuid', 'upcoming', 'search']);

        return response()->json(['success' => true, 'message' => 'Success', 'data' => EventResource::collection($this->events->paginateForUser($request->user(), $filters, min($request->integer('limit', 15), 100)))]);
    }

    public function store(StoreEventRequest $request): JsonResponse
    {
        Gate::authorize('create', Event::class);
        Gate::authorize('update', Family::query()->where('uuid', $request->validated('family_uuid'))->firstOrFail());

        return response()->json(['success' => true, 'message' => 'Event created', 'data' => new EventResource($this->service->create($request->user(), EventData::fromArray($request->validated())))], 201);
    }

    public function show(Request $request, Event $event): JsonResponse
    {
        Gate::authorize('view', $event);

        return response()->json(['success' => true, 'message' => 'Success', 'data' => new EventResource($this->events->loadDetails($event, $request->user()))]);
    }

    public function update(UpdateEventRequest $request, Event $event): JsonResponse
    {
        Gate::authorize('update', $event);

        return response()->json(['success' => true, 'message' => 'Event updated', 'data' => new EventResource($this->service->update($event, $request->validated(), $request->user()))]);
    }

    public function destroy(Event $event): JsonResponse
    {
        Gate::authorize('delete', $event);
        $this->service->delete($event);

        return response()->json(['success' => true, 'message' => 'Event deleted', 'data' => null]);
    }

    public function rsvp(RsvpEventRequest $request, Event $event): JsonResponse
    {
        Gate::authorize('rsvp', $event);

        return response()->json(['success' => true, 'message' => 'RSVP updated', 'data' => new EventAttendeeResource($this->service->rsvp($event, $request->user(), $request->validated('status')))]);
    }
}
