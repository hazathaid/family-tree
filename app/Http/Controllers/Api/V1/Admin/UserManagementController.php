<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\AdminIndexRequest;
use App\Http\Requests\Administration\UpdateUserStatusRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AdministrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class UserManagementController extends Controller
{
    public function __construct(private readonly AdministrationService $administration) {}

    public function index(AdminIndexRequest $request): JsonResponse
    {
        Gate::authorize('administer');

        return $this->success(UserResource::collection($this->administration->users($request->integer('per_page', 15))));
    }

    public function show(User $user): JsonResponse
    {
        Gate::authorize('administer');

        return $this->success(new UserResource($user));
    }

    public function update(UpdateUserStatusRequest $request, User $user): JsonResponse
    {
        Gate::authorize('administer');
        $updated = $this->administration->updateUserStatus($request->user(), $user, $request->validated('status'));

        return $this->success(new UserResource($updated), 'User status updated');
    }

    private function success(mixed $data, string $message = 'Success'): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data]);
    }
}
