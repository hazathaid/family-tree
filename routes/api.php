<?php

use App\Http\Controllers\Api\V1\Admin\AuditLogController;
use App\Http\Controllers\Api\V1\Admin\FamilyModerationController;
use App\Http\Controllers\Api\V1\Admin\UserManagementController;
use App\Http\Controllers\Api\V1\ArticleCategoryController;
use App\Http\Controllers\Api\V1\ArticleCommentController;
use App\Http\Controllers\Api\V1\ArticleController;
use App\Http\Controllers\Api\V1\ArticleLikeController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\FamilyBranchController;
use App\Http\Controllers\Api\V1\FamilyController;
use App\Http\Controllers\Api\V1\FamilyDashboardController;
use App\Http\Controllers\Api\V1\FamilyMemberController;
use App\Http\Controllers\Api\V1\FamilyRoleController;
use App\Http\Controllers\Api\V1\FamilyTreeController;
use App\Http\Controllers\Api\V1\FeaturedArticleController;
use App\Http\Controllers\Api\V1\GamificationController;
use App\Http\Controllers\Api\V1\MemberPhotoController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PhotoAlbumController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\PushDeviceController;
use App\Http\Controllers\Api\V1\RelationshipController;
use App\Http\Controllers\Api\V1\RelationshipEngineController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\TimelineController;
use App\Http\Controllers\Api\V1\TreeExportController;
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

        Route::get('relationship-engine', [RelationshipEngineController::class, 'show']);
        Route::apiResource('relationships', RelationshipController::class);
        Route::get('tree/generate', [FamilyTreeController::class, 'generate']);
        Route::get('tree/export/png', [TreeExportController::class, 'png'])->middleware('throttle:10,1');
        Route::get('tree/export/pdf', [TreeExportController::class, 'pdf'])->middleware('throttle:10,1');

        Route::apiResource('article-categories', ArticleCategoryController::class);
        Route::post('articles/{article}/publish', [ArticleController::class, 'publish']);
        Route::post('articles/{article}/featured-image', [ArticleController::class, 'image'])->middleware('throttle:10,1');
        Route::apiResource('articles', ArticleController::class);
        Route::get('articles/{article}/comments', [ArticleCommentController::class, 'index']);
        Route::post('articles/{article}/comments', [ArticleCommentController::class, 'store'])->middleware('throttle:30,1');
        Route::put('articles/{article}/comments/{comment}', [ArticleCommentController::class, 'update']);
        Route::delete('articles/{article}/comments/{comment}', [ArticleCommentController::class, 'destroy']);
        Route::post('articles/{article}/like', [ArticleLikeController::class, 'store'])->middleware('throttle:60,1');
        Route::delete('articles/{article}/like', [ArticleLikeController::class, 'destroy'])->middleware('throttle:60,1');
        Route::post('articles/{article}/feature', [FeaturedArticleController::class, 'store']);
        Route::delete('articles/{article}/feature', [FeaturedArticleController::class, 'destroy']);
        Route::get('families/{family}/articles/featured', [FeaturedArticleController::class, 'index']);

        Route::apiResource('photo-albums', PhotoAlbumController::class);
        Route::put('member-photos/{member_photo}/tags', [MemberPhotoController::class, 'tag']);
        Route::apiResource('member-photos', MemberPhotoController::class)->only(['index', 'store', 'show', 'destroy']);
        Route::get('timeline', [TimelineController::class, 'index']);
        Route::post('events/{event}/rsvp', [EventController::class, 'rsvp'])->middleware('throttle:30,1');
        Route::apiResource('events', EventController::class);
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::post('notifications/read-all', [NotificationController::class, 'readAll']);
        Route::post('notifications/{notification}/read', [NotificationController::class, 'read']);
        Route::post('push-devices', [PushDeviceController::class, 'store'])->middleware('throttle:20,1');
        Route::delete('push-devices/{device}', [PushDeviceController::class, 'destroy']);
        Route::get('search', [SearchController::class, 'index'])->middleware('throttle:60,1');
        Route::get('families/{family}/reports/family-statistics', [ReportController::class, 'familyStatistics']);
        Route::get('families/{family}/reports/activity', [ReportController::class, 'activity']);
        Route::get('families/{family}/gamification', [GamificationController::class, 'profile']);
        Route::get('families/{family}/leaderboard', [GamificationController::class, 'users']);
        Route::get('leaderboard/families', [GamificationController::class, 'families']);

        Route::prefix('admin')->group(function (): void {
            Route::get('users', [UserManagementController::class, 'index']);
            Route::get('users/{user}', [UserManagementController::class, 'show']);
            Route::patch('users/{user}', [UserManagementController::class, 'update']);
            Route::get('families', [FamilyModerationController::class, 'index']);
            Route::get('families/{family}', [FamilyModerationController::class, 'show']);
            Route::delete('families/{family}/content', [FamilyModerationController::class, 'destroyContent']);
            Route::get('audit-logs', [AuditLogController::class, 'index']);
            Route::get('audit-logs/export', [AuditLogController::class, 'export']);
        });
    });
});
