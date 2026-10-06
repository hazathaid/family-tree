<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ConfirmTwoFactorRequest;
use App\Http\Requests\Profile\DisableTwoFactorRequest;
use App\Http\Requests\Profile\EnableTwoFactorRequest;
use App\Http\Requests\Profile\RegenerateRecoveryCodesRequest;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TwoFactorController extends Controller
{
    public function __construct(private readonly TwoFactorService $twoFactor) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'enabled' => $user->two_factor_confirmed_at !== null,
                'pending' => $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null,
                'recovery_codes_count' => count($this->twoFactor->recoveryCodes($user)),
            ],
        ]);
    }

    public function store(EnableTwoFactorRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Two-factor setup started',
            'data' => $this->twoFactor->enable($request->user()),
        ]);
    }

    public function confirm(ConfirmTwoFactorRequest $request): JsonResponse
    {
        $codes = $this->twoFactor->confirm($request->user(), $request->validated('code'));

        if ($codes === null) {
            throw ValidationException::withMessages([
                'code' => ['The provided two-factor code was invalid.'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication enabled',
            'data' => ['recovery_codes' => $codes],
        ]);
    }

    public function destroy(DisableTwoFactorRequest $request): JsonResponse
    {
        $this->twoFactor->disable($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication disabled',
            'data' => null,
        ]);
    }

    public function recoveryCodes(RegenerateRecoveryCodesRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Recovery codes regenerated',
            'data' => ['recovery_codes' => $this->twoFactor->regenerateRecoveryCodes($request->user())],
        ]);
    }
}
