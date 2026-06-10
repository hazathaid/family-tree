<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'data' => new UserResource($user),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $payload = $this->authService->login(
            $request->only('email', 'password'),
            $request->string('device_name', 'api')->toString(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'token' => $payload['token'],
                'user' => new UserResource($payload['user']),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Logout successful',
            'data' => null,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => new UserResource($request->user()),
        ]);
    }
}
