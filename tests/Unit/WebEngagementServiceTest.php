<?php

namespace Tests\Unit;

use App\Models\User;
use App\Repositories\Contracts\ActivityLogRepositoryInterface;
use App\Repositories\Contracts\ArticleCategoryRepositoryInterface;
use App\Repositories\Contracts\ArticleCommentRepositoryInterface;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\Contracts\MemberPhotoRepositoryInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\PhotoAlbumRepositoryInterface;
use App\Services\WebEngagementService;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;

class WebEngagementServiceTest extends TestCase
{
    public function test_notification_presentation_maps_unread_filter_and_count(): void
    {
        $notifications = Mockery::mock(NotificationRepositoryInterface::class);
        $user = new User;
        $items = new LengthAwarePaginator([], 0, 15);
        $notifications->shouldReceive('paginateForUser')->once()->with($user, 15, false)->andReturn($items);
        $notifications->shouldReceive('unreadCount')->once()->with($user)->andReturn(7);

        $service = new WebEngagementService(
            Mockery::mock(ArticleRepositoryInterface::class), Mockery::mock(ArticleCategoryRepositoryInterface::class),
            Mockery::mock(ArticleCommentRepositoryInterface::class), Mockery::mock(MemberPhotoRepositoryInterface::class),
            Mockery::mock(PhotoAlbumRepositoryInterface::class), Mockery::mock(EventRepositoryInterface::class),
            Mockery::mock(ActivityLogRepositoryInterface::class), $notifications,
        );

        $result = $service->notifications($user, 'unread');

        $this->assertSame($items, $result['notifications']);
        $this->assertSame(7, $result['unreadCount']);
    }
}
