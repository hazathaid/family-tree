<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Services\EmailVerificationService;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function __construct(
        private readonly EmailVerificationService $emailVerificationService,
    ) {}

    public function send(Request $request): JsonResponse
    {
        $sent = $this->emailVerificationService->send($request->user());

        return response()->json([
            'success' => true,
            'message' => $sent ? 'Verification link sent' : 'Email already verified',
            'data' => null,
        ]);
    }

    public function verify(EmailVerificationRequest $request): JsonResponse
    {
        $verified = $this->emailVerificationService->verify($request);

        return response()->json([
            'success' => true,
            'message' => $verified ? 'Email verified' : 'Email already verified',
            'data' => null,
        ]);
    }
}
