<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\EmailVerificationController;
use App\Http\Controllers\Web\OnboardingController;
use App\Http\Controllers\Web\PasswordController;
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
        Route::view('/dashboard', 'dashboard')->middleware('active.family')->name('dashboard');
    });
});
