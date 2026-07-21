<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ArticleComment;
use App\Models\Event;
use App\Models\Family;
use App\Models\FamilyBranch;
use App\Models\FamilyMember;
use App\Models\MemberPhoto;
use App\Models\PhotoAlbum;
use App\Policies\ArticleCategoryPolicy;
use App\Policies\ArticleCommentPolicy;
use App\Policies\ArticlePolicy;
use App\Policies\EventPolicy;
use App\Policies\FamilyBranchPolicy;
use App\Policies\FamilyMemberPolicy;
use App\Policies\FamilyPolicy;
use App\Policies\MemberPhotoPolicy;
use App\Policies\PhotoAlbumPolicy;
use App\Repositories\Contracts\ActivityLogRepositoryInterface;
use App\Repositories\Contracts\AdministrationRepositoryInterface;
use App\Repositories\Contracts\ArticleCategoryRepositoryInterface;
use App\Repositories\Contracts\ArticleCommentRepositoryInterface;
use App\Repositories\Contracts\ArticleLikeRepositoryInterface;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\Contracts\FamilyBranchRepositoryInterface;
use App\Repositories\Contracts\FamilyDashboardRepositoryInterface;
use App\Repositories\Contracts\FamilyMemberRepositoryInterface;
use App\Repositories\Contracts\FamilyRepositoryInterface;
use App\Repositories\Contracts\FamilyUserRoleRepositoryInterface;
use App\Repositories\Contracts\GamificationRepositoryInterface;
use App\Repositories\Contracts\MemberPhotoRepositoryInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\PhotoAlbumRepositoryInterface;
use App\Repositories\Contracts\PushDeviceTokenRepositoryInterface;
use App\Repositories\Contracts\RelationshipRepositoryInterface;
use App\Repositories\Contracts\ReportRepositoryInterface;
use App\Repositories\Contracts\SearchRepositoryInterface;
use App\Repositories\Contracts\SystemHealthRepositoryInterface;
use App\Repositories\Contracts\TreeRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\EloquentActivityLogRepository;
use App\Repositories\Eloquent\EloquentAdministrationRepository;
use App\Repositories\Eloquent\EloquentArticleCategoryRepository;
use App\Repositories\Eloquent\EloquentArticleCommentRepository;
use App\Repositories\Eloquent\EloquentArticleLikeRepository;
use App\Repositories\Eloquent\EloquentArticleRepository;
use App\Repositories\Eloquent\EloquentAuditLogRepository;
use App\Repositories\Eloquent\EloquentEventRepository;
use App\Repositories\Eloquent\EloquentFamilyBranchRepository;
use App\Repositories\Eloquent\EloquentFamilyDashboardRepository;
use App\Repositories\Eloquent\EloquentFamilyMemberRepository;
use App\Repositories\Eloquent\EloquentFamilyRepository;
use App\Repositories\Eloquent\EloquentFamilyUserRoleRepository;
use App\Repositories\Eloquent\EloquentGamificationRepository;
use App\Repositories\Eloquent\EloquentMemberPhotoRepository;
use App\Repositories\Eloquent\EloquentNotificationRepository;
use App\Repositories\Eloquent\EloquentPhotoAlbumRepository;
use App\Repositories\Eloquent\EloquentPushDeviceTokenRepository;
use App\Repositories\Eloquent\EloquentRelationshipRepository;
use App\Repositories\Eloquent\EloquentReportRepository;
use App\Repositories\Eloquent\EloquentSearchRepository;
use App\Repositories\Eloquent\EloquentSystemHealthRepository;
use App\Repositories\Eloquent\EloquentTreeRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ActivityLogRepositoryInterface::class, EloquentActivityLogRepository::class);
        $this->app->bind(AdministrationRepositoryInterface::class, EloquentAdministrationRepository::class);
        $this->app->bind(AuditLogRepositoryInterface::class, EloquentAuditLogRepository::class);
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(FamilyRepositoryInterface::class, EloquentFamilyRepository::class);
        $this->app->bind(FamilyUserRoleRepositoryInterface::class, EloquentFamilyUserRoleRepository::class);
        $this->app->bind(GamificationRepositoryInterface::class, EloquentGamificationRepository::class);
        $this->app->bind(FamilyBranchRepositoryInterface::class, EloquentFamilyBranchRepository::class);
        $this->app->bind(FamilyDashboardRepositoryInterface::class, EloquentFamilyDashboardRepository::class);
        $this->app->bind(FamilyMemberRepositoryInterface::class, EloquentFamilyMemberRepository::class);
        $this->app->bind(RelationshipRepositoryInterface::class, EloquentRelationshipRepository::class);
        $this->app->bind(SearchRepositoryInterface::class, EloquentSearchRepository::class);
        $this->app->bind(TreeRepositoryInterface::class, EloquentTreeRepository::class);
        $this->app->bind(ArticleCategoryRepositoryInterface::class, EloquentArticleCategoryRepository::class);
        $this->app->bind(ArticleRepositoryInterface::class, EloquentArticleRepository::class);
        $this->app->bind(ArticleCommentRepositoryInterface::class, EloquentArticleCommentRepository::class);
        $this->app->bind(ArticleLikeRepositoryInterface::class, EloquentArticleLikeRepository::class);
        $this->app->bind(PhotoAlbumRepositoryInterface::class, EloquentPhotoAlbumRepository::class);
        $this->app->bind(MemberPhotoRepositoryInterface::class, EloquentMemberPhotoRepository::class);
        $this->app->bind(EventRepositoryInterface::class, EloquentEventRepository::class);
        $this->app->bind(NotificationRepositoryInterface::class, EloquentNotificationRepository::class);
        $this->app->bind(PushDeviceTokenRepositoryInterface::class, EloquentPushDeviceTokenRepository::class);
        $this->app->bind(ReportRepositoryInterface::class, EloquentReportRepository::class);
        $this->app->bind(SystemHealthRepositoryInterface::class, EloquentSystemHealthRepository::class);
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute(60)
            ->by($request->user()?->getAuthIdentifier() ?: $request->ip()));
        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(5)
            ->by(strtolower((string) $request->input('email')).'|'.$request->ip()));

        Gate::define('administer', fn ($user): bool => $user->hasRole('super-admin'));
        Gate::policy(Family::class, FamilyPolicy::class);
        Gate::policy(FamilyBranch::class, FamilyBranchPolicy::class);
        Gate::policy(FamilyMember::class, FamilyMemberPolicy::class);
        Gate::policy(ArticleCategory::class, ArticleCategoryPolicy::class);
        Gate::policy(Article::class, ArticlePolicy::class);
        Gate::policy(ArticleComment::class, ArticleCommentPolicy::class);
        Gate::policy(PhotoAlbum::class, PhotoAlbumPolicy::class);
        Gate::policy(MemberPhoto::class, MemberPhotoPolicy::class);
        Gate::policy(Event::class, EventPolicy::class);

        ResetPassword::createUrlUsing(function (object $user, string $token): string {
            return route('password.reset', ['token' => $token, 'email' => $user->email]);
        });
    }
}
