<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\IndexNotificationRequest;
use App\Http\Resources\NotificationResource;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationRepositoryInterface $notifications, private readonly NotificationService $service) {}

    public function index(IndexNotificationRequest $request): JsonResponse
    {
        $status = $request->validated('status');
        $isRead = $status === null ? null : $status === 'read';
        $items = $this->notifications->paginateForUser($request->user(), $request->integer('limit', 15), $isRead);

        return response()->json(['success' => true, 'message' => 'Success', 'data' => NotificationResource::collection($items)]);
    }

    public function read(Request $request, string $notification): JsonResponse
    {
        $item = $this->notifications->findForUser($request->user(), $notification);

        return response()->json(['success' => true, 'message' => 'Notification marked as read', 'data' => new NotificationResource($this->service->markRead($item))]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $count = $this->service->markAllRead($request->user());

        return response()->json(['success' => true, 'message' => 'Notifications marked as read', 'data' => ['updated' => $count]]);
    }
}
