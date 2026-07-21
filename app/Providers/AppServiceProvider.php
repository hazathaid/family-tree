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
use App\Repositories\Contracts\ArticleCategoryRepositoryInterface;
use App\Repositories\Contracts\ArticleCommentRepositoryInterface;
use App\Repositories\Contracts\ArticleLikeRepositoryInterface;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\Contracts\FamilyBranchRepositoryInterface;
use App\Repositories\Contracts\FamilyDashboardRepositoryInterface;
use App\Repositories\Contracts\FamilyMemberRepositoryInterface;
use App\Repositories\Contracts\FamilyRepositoryInterface;
use App\Repositories\Contracts\FamilyUserRoleRepositoryInterface;
use App\Repositories\Contracts\MemberPhotoRepositoryInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\PhotoAlbumRepositoryInterface;
use App\Repositories\Contracts\RelationshipRepositoryInterface;
use App\Repositories\Contracts\TreeRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\EloquentActivityLogRepository;
use App\Repositories\Eloquent\EloquentArticleCategoryRepository;
use App\Repositories\Eloquent\EloquentArticleCommentRepository;
use App\Repositories\Eloquent\EloquentArticleLikeRepository;
use App\Repositories\Eloquent\EloquentArticleRepository;
use App\Repositories\Eloquent\EloquentEventRepository;
use App\Repositories\Eloquent\EloquentFamilyBranchRepository;
use App\Repositories\Eloquent\EloquentFamilyDashboardRepository;
use App\Repositories\Eloquent\EloquentFamilyMemberRepository;
use App\Repositories\Eloquent\EloquentFamilyRepository;
use App\Repositories\Eloquent\EloquentFamilyUserRoleRepository;
use App\Repositories\Eloquent\EloquentMemberPhotoRepository;
use App\Repositories\Eloquent\EloquentNotificationRepository;
use App\Repositories\Eloquent\EloquentPhotoAlbumRepository;
use App\Repositories\Eloquent\EloquentRelationshipRepository;
use App\Repositories\Eloquent\EloquentTreeRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ActivityLogRepositoryInterface::class, EloquentActivityLogRepository::class);
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(FamilyRepositoryInterface::class, EloquentFamilyRepository::class);
        $this->app->bind(FamilyUserRoleRepositoryInterface::class, EloquentFamilyUserRoleRepository::class);
        $this->app->bind(FamilyBranchRepositoryInterface::class, EloquentFamilyBranchRepository::class);
        $this->app->bind(FamilyDashboardRepositoryInterface::class, EloquentFamilyDashboardRepository::class);
        $this->app->bind(FamilyMemberRepositoryInterface::class, EloquentFamilyMemberRepository::class);
        $this->app->bind(RelationshipRepositoryInterface::class, EloquentRelationshipRepository::class);
        $this->app->bind(TreeRepositoryInterface::class, EloquentTreeRepository::class);
        $this->app->bind(ArticleCategoryRepositoryInterface::class, EloquentArticleCategoryRepository::class);
        $this->app->bind(ArticleRepositoryInterface::class, EloquentArticleRepository::class);
        $this->app->bind(ArticleCommentRepositoryInterface::class, EloquentArticleCommentRepository::class);
        $this->app->bind(ArticleLikeRepositoryInterface::class, EloquentArticleLikeRepository::class);
        $this->app->bind(PhotoAlbumRepositoryInterface::class, EloquentPhotoAlbumRepository::class);
        $this->app->bind(MemberPhotoRepositoryInterface::class, EloquentMemberPhotoRepository::class);
        $this->app->bind(EventRepositoryInterface::class, EloquentEventRepository::class);
        $this->app->bind(NotificationRepositoryInterface::class, EloquentNotificationRepository::class);
    }

    public function boot(): void
    {
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
            return config('app.url').'/reset-password?token='.$token.'&email='.urlencode($user->email);
        });
    }
}
