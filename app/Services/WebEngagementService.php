<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Event;
use App\Models\Family;
use App\Models\MemberPhoto;
use App\Models\PhotoAlbum;
use App\Models\User;
use App\Repositories\Contracts\ActivityLogRepositoryInterface;
use App\Repositories\Contracts\ArticleCategoryRepositoryInterface;
use App\Repositories\Contracts\ArticleCommentRepositoryInterface;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\Contracts\MemberPhotoRepositoryInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\PhotoAlbumRepositoryInterface;

class WebEngagementService
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articles,
        private readonly ArticleCategoryRepositoryInterface $categories,
        private readonly ArticleCommentRepositoryInterface $comments,
        private readonly MemberPhotoRepositoryInterface $photos,
        private readonly PhotoAlbumRepositoryInterface $albums,
        private readonly EventRepositoryInterface $events,
        private readonly ActivityLogRepositoryInterface $activities,
        private readonly NotificationRepositoryInterface $notifications,
    ) {}

    public function articles(Family $family, User $user, array $filters): array
    {
        $filters['family_uuid'] = $family->uuid;

        return ['articles' => $this->articles->paginateForUser($user, $filters, 12)->withQueryString(), 'categories' => $this->categories->paginate(null, 100)];
    }

    public function article(Article $article, User $user): array
    {
        return ['article' => $this->articles->loadDetails($article, $user), 'comments' => $this->comments->paginate($article, 15)];
    }

    public function articleForm(): array
    {
        return ['categories' => $this->categories->paginate(null, 100)];
    }

    public function photos(Family $family, User $user, array $filters): array
    {
        $filters['family_uuid'] = $family->uuid;

        return ['photos' => $this->photos->paginateForUser($user, $filters, 18)->withQueryString(), 'albums' => $this->albums->paginateForUser($user, $family->uuid, 100)];
    }

    public function photo(MemberPhoto $photo): MemberPhoto
    {
        return $this->photos->loadDetails($photo);
    }

    public function album(PhotoAlbum $album, User $user): array
    {
        return ['album' => $album->load(['family', 'creator'])->loadCount('photos'), 'photos' => $this->photos->paginateForUser($user, ['family_uuid' => $album->family->uuid, 'album_uuid' => $album->uuid], 18)];
    }

    public function photoForm(Family $family, User $user): array
    {
        return ['albums' => $this->albums->paginateForUser($user, $family->uuid, 100), 'members' => $family->members()->orderBy('full_name')->limit(100)->get()];
    }

    public function events(Family $family, User $user, array $filters): array
    {
        $filters['family_uuid'] = $family->uuid;

        return ['events' => $this->events->paginateForUser($user, $filters, 12)->withQueryString()];
    }

    public function event(Event $event, User $user): Event
    {
        return $this->events->loadDetails($event, $user);
    }

    public function timeline(Family $family, User $user, array $filters): array
    {
        $filters['family_uuid'] = $family->uuid;

        return ['activities' => $this->activities->paginateForUser($user, $filters, 15)->withQueryString()];
    }

    public function notifications(User $user, ?string $status = null): array
    {
        $isRead = $status === null ? null : $status === 'read';
        $items = $this->notifications->paginateForUser($user, 15, $isRead)->withQueryString();

        return ['notifications' => $items, 'unreadCount' => $this->notifications->unreadCount($user)];
    }
}
