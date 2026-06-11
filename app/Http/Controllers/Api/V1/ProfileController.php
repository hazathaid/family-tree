<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ChangePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Requests\Profile\UploadAvatarRequest;
use App\Http\Resources\UserResource;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileService $profileService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => new UserResource($request->user()),
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->profileService->update($request->user(), $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Profile updated',
            'data' => new UserResource($user),
        ]);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->profileService->changePassword($request->user(), $request->validated('password'));

        return response()->json([
            'success' => true,
            'message' => 'Password changed',
            'data' => null,
        ]);
    }

    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        $user = $this->profileService->uploadAvatar($request->user(), $request->file('avatar'));

        return response()->json([
            'success' => true,
            'message' => 'Avatar uploaded',
            'data' => new UserResource($user),
        ]);
    }
}
