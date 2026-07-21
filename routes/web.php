<?php

use App\Http\Controllers\Web\ArticleController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\EmailVerificationController;
use App\Http\Controllers\Web\EventController;
use App\Http\Controllers\Web\FamilySettingsController;
use App\Http\Controllers\Web\FamilyTreeController;
use App\Http\Controllers\Web\MemberController;
use App\Http\Controllers\Web\OnboardingController;
use App\Http\Controllers\Web\PasswordController;
use App\Http\Controllers\Web\PhotoController;
use App\Http\Controllers\Web\TimelineController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/forgot-password', [PasswordController::class, 'forgotForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordController::class, 'email'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordController::class, 'reset'])->name('password.update');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])->middleware('throttle:6,1')->name('verification.send');

    Route::middleware('verified')->group(function (): void {
        Route::get('/onboarding', [OnboardingController::class, 'index'])->name('onboarding.index');
        Route::post('/onboarding/families', [OnboardingController::class, 'store'])->name('onboarding.families.store');
        Route::post('/families/{family}/activate', [OnboardingController::class, 'select'])->name('families.activate');
        Route::middleware('active.family')->group(function (): void {
            Route::get('/dashboard', DashboardController::class)->name('dashboard');
            Route::get('/tree', FamilyTreeController::class)->name('tree.index');

            Route::get('/settings', [FamilySettingsController::class, 'index'])->name('settings.index');
            Route::put('/settings', [FamilySettingsController::class, 'update'])->name('settings.update');
            Route::post('/settings/branches', [FamilySettingsController::class, 'storeBranch'])->name('settings.branches.store');
            Route::put('/settings/branches/{branch}', [FamilySettingsController::class, 'updateBranch'])->name('settings.branches.update');
            Route::delete('/settings/branches/{branch}', [FamilySettingsController::class, 'destroyBranch'])->name('settings.branches.destroy');
            Route::post('/settings/members', [FamilySettingsController::class, 'invite'])->name('settings.members.invite');
            Route::patch('/settings/members/{membership}', [FamilySettingsController::class, 'assignRole'])->name('settings.members.role');
            Route::delete('/settings/members/{membership}', [FamilySettingsController::class, 'removeRole'])->name('settings.members.destroy');

            Route::resource('members', MemberController::class)->except('show');
            Route::get('/members/{member}', [MemberController::class, 'show'])->name('members.show');

            Route::post('/articles/{article}/publish', [ArticleController::class, 'publish'])->name('articles.publish');
            Route::post('/articles/{article}/image', [ArticleController::class, 'image'])->name('articles.image');
            Route::post('/articles/{article}/comments', [ArticleController::class, 'comment'])->name('articles.comments.store');
            Route::post('/articles/{article}/like', [ArticleController::class, 'like'])->name('articles.like');
            Route::resource('articles', ArticleController::class);

            Route::get('/photos', [PhotoController::class, 'index'])->name('photos.index');
            Route::get('/photos/create', [PhotoController::class, 'create'])->name('photos.create');
            Route::post('/photos', [PhotoController::class, 'store'])->name('photos.store');
            Route::get('/photos/{photo}', [PhotoController::class, 'show'])->name('photos.show');
            Route::put('/photos/{photo}/tags', [PhotoController::class, 'tag'])->name('photos.tags.update');
            Route::delete('/photos/{photo}', [PhotoController::class, 'destroy'])->name('photos.destroy');
            Route::post('/albums', [PhotoController::class, 'storeAlbum'])->name('albums.store');
            Route::get('/albums/{album}', [PhotoController::class, 'showAlbum'])->name('albums.show');

            Route::post('/events/{event}/rsvp', [EventController::class, 'rsvp'])->name('events.rsvp');
            Route::resource('events', EventController::class);
            Route::get('/timeline', [TimelineController::class, 'index'])->name('timeline.index');
            Route::get('/notifications', [TimelineController::class, 'notifications'])->name('notifications.index');
            Route::post('/notifications/read-all', [TimelineController::class, 'readAll'])->name('notifications.read-all');
            Route::post('/notifications/{notification}/read', [TimelineController::class, 'read'])->name('notifications.read');
        });
    });
});
