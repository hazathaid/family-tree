<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\PasswordResetService;
use Illuminate\Http\JsonResponse;

class ForgotPasswordController extends Controller
{
    public function __construct(
        private readonly PasswordResetService $passwordResetService,
    ) {
    }

    public function store(ForgotPasswordRequest $request): JsonResponse
    {
        $message = $this->passwordResetService->sendResetLink($request->validated('email'));

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => null,
        ]);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $message = $this->passwordResetService->reset($request->validated());

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => null,
        ]);
    }
}
