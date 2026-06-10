<?php

namespace App\Providers;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\EloquentUserRepository;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
    }

    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $user, string $token): string {
            return config('app.url').'/reset-password?token='.$token.'&email='.urlencode($user->email);
        });
    }
}
