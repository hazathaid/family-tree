<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\FamilyBranchController;
use App\Http\Controllers\Api\V1\FamilyController;
use App\Http\Controllers\Api\V1\FamilyDashboardController;
use App\Http\Controllers\Api\V1\FamilyMemberController;
use App\Http\Controllers\Api\V1\FamilyRoleController;
use App\Http\Controllers\Api\V1\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('register', [AuthController::class, 'register'])->middleware('guest:sanctum');
        Route::post('login', [AuthController::class, 'login'])->middleware('guest:sanctum');
        Route::post('forgot-password', [ForgotPasswordController::class, 'store'])->middleware('guest:sanctum');
        Route::post('reset-password', [ForgotPasswordController::class, 'reset'])->middleware('guest:sanctum');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::post('email/verification-notification', [EmailVerificationController::class, 'send']);
            Route::get('email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
                ->middleware('signed')
                ->name('verification.verify');
        });
    });

    Route::middleware('auth:sanctum')->prefix('profile')->group(function (): void {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
        Route::patch('password', [ProfileController::class, 'changePassword']);
        Route::post('avatar', [ProfileController::class, 'uploadAvatar']);
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::apiResource('families', FamilyController::class);

        Route::get('families/{family}/roles', [FamilyRoleController::class, 'index']);
        Route::post('families/{family}/roles/invite', [FamilyRoleController::class, 'invite']);
        Route::patch('families/{family}/roles/{membership}', [FamilyRoleController::class, 'assign']);
        Route::delete('families/{family}/roles/{membership}', [FamilyRoleController::class, 'remove']);

        Route::apiResource('families.branches', FamilyBranchController::class);
        Route::get('families/{family}/dashboard', [FamilyDashboardController::class, 'show']);

        Route::apiResource('family-members', FamilyMemberController::class);
        Route::post('family-members/{family_member}/photo', [FamilyMemberController::class, 'uploadPhoto']);
    });
});
