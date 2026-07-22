<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\RevokeAccountSessionRequest;
use App\Http\Requests\Profile\UpdateNotificationPreferencesRequest;
use App\Http\Resources\AccountSessionResource;
use App\Http\Resources\NotificationPreferencesResource;
use App\Services\AccountSessionService;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AccountController extends Controller
{
    public function __construct(
        private readonly ProfileService $profiles,
        private readonly AccountSessionService $sessions,
    ) {}

    public function preferences(Request $request): JsonResponse
    {
        Gate::authorize('manage', $request->user());

        return $this->success(new NotificationPreferencesResource($request->user()->notification_preferences));
    }

    public function updatePreferences(UpdateNotificationPreferencesRequest $request): JsonResponse
    {
        Gate::authorize('manage', $request->user());
        $user = $this->profiles->updateNotificationPreferences($request->user(), $request->validated());

        return $this->success(new NotificationPreferencesResource($user->notification_preferences), 'Preferences updated');
    }

    public function sessions(Request $request): JsonResponse
    {
        Gate::authorize('manage', $request->user());

        return $this->success(AccountSessionResource::collection($this->sessions->list($request->user())));
    }

    public function revoke(RevokeAccountSessionRequest $request, string $session): JsonResponse
    {
        Gate::authorize('manage', $request->user());
        $current = $this->sessions->revoke($request->user(), $session);

        return $this->success(['revoked_current' => $current], 'Session revoked');
    }

    private function success(mixed $data, string $message = 'Success'): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data]);
    }
}
