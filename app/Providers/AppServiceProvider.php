<?php

namespace App\Providers;

use App\Models\Family;
use App\Models\FamilyBranch;
use App\Models\FamilyMember;
use App\Policies\FamilyBranchPolicy;
use App\Policies\FamilyMemberPolicy;
use App\Policies\FamilyPolicy;
use App\Repositories\Contracts\FamilyBranchRepositoryInterface;
use App\Repositories\Contracts\FamilyDashboardRepositoryInterface;
use App\Repositories\Contracts\FamilyMemberRepositoryInterface;
use App\Repositories\Contracts\FamilyRepositoryInterface;
use App\Repositories\Contracts\FamilyUserRoleRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\EloquentFamilyBranchRepository;
use App\Repositories\Eloquent\EloquentFamilyDashboardRepository;
use App\Repositories\Eloquent\EloquentFamilyMemberRepository;
use App\Repositories\Eloquent\EloquentFamilyRepository;
use App\Repositories\Eloquent\EloquentFamilyUserRoleRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(FamilyRepositoryInterface::class, EloquentFamilyRepository::class);
        $this->app->bind(FamilyUserRoleRepositoryInterface::class, EloquentFamilyUserRoleRepository::class);
        $this->app->bind(FamilyBranchRepositoryInterface::class, EloquentFamilyBranchRepository::class);
        $this->app->bind(FamilyDashboardRepositoryInterface::class, EloquentFamilyDashboardRepository::class);
        $this->app->bind(FamilyMemberRepositoryInterface::class, EloquentFamilyMemberRepository::class);
    }

    public function boot(): void
    {
        Gate::policy(Family::class, FamilyPolicy::class);
        Gate::policy(FamilyBranch::class, FamilyBranchPolicy::class);
        Gate::policy(FamilyMember::class, FamilyMemberPolicy::class);

        ResetPassword::createUrlUsing(function (object $user, string $token): string {
            return config('app.url').'/reset-password?token='.$token.'&email='.urlencode($user->email);
        });
    }
}
