<?php

namespace Tests\Feature;

use App\DTOs\EventData;
use App\Jobs\NotifyFamilyOfUpdate;
use App\Jobs\SendBirthdayGreetings;
use App\Jobs\SendPushNotification;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\FamilyActivityNotification;
use App\Services\ArticleCommentService;
use App\Services\ArticleLikeService;
use App\Services\ArticleService;
use App\Services\EventService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DomainNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_commenting_notifies_the_article_author(): void
    {
        Queue::fake();
        NotificationFacade::fake();
        [$author, $family] = $this->userWithFamily();
        $commenter = $this->addFamilyUser($family);
        $article = $this->article($family, $author);

        app(ArticleCommentService::class)->create($article, $commenter, 'Bagus sekali');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $author->id,
            'type' => 'article_comment',
        ]);
        $notification = Notification::query()->where('user_id', $author->id)->firstOrFail();
        $this->assertSame('article', $notification->data['target_type']);
        $this->assertSame($article->uuid, $notification->data['target_uuid']);
        Queue::assertPushed(SendPushNotification::class);
        NotificationFacade::assertSentTo($author, FamilyActivityNotification::class);
    }

    public function test_author_actions_do_not_notify_themselves(): void
    {
        Queue::fake();
        NotificationFacade::fake();
        [$author, $family] = $this->userWithFamily();
        $article = $this->article($family, $author);

        app(ArticleCommentService::class)->create($article, $author, 'Catatan sendiri');
        app(ArticleLikeService::class)->like($article, $author);

        $this->assertDatabaseCount('notifications', 0);
        NotificationFacade::assertNothingSent();
    }

    public function test_liking_notifies_author_only_once(): void
    {
        Queue::fake();
        NotificationFacade::fake();
        [$author, $family] = $this->userWithFamily();
        $liker = $this->addFamilyUser($family);
        $article = $this->article($family, $author);

        $likes = app(ArticleLikeService::class);
        $likes->like($article, $liker);
        $likes->like($article, $liker);

        $this->assertSame(1, Notification::query()->where('user_id', $author->id)->where('type', 'article_like')->count());
    }

    public function test_family_updates_opt_out_suppresses_delivery(): void
    {
        Queue::fake();
        NotificationFacade::fake();
        [$author] = $this->userWithFamily();
        $author->update(['notification_preferences' => ['family_updates' => false]]);

        $notification = app(NotificationService::class)->dispatchForUser(
            $author,
            NotificationService::CATEGORY_FAMILY_UPDATES,
            'article_published',
            'Judul',
            'Isi',
        );

        $this->assertNull($notification);
        $this->assertDatabaseCount('notifications', 0);
        Queue::assertNothingPushed();
        NotificationFacade::assertNothingSent();
    }

    public function test_push_opt_out_keeps_in_app_notification_without_push(): void
    {
        Queue::fake();
        NotificationFacade::fake();
        [$author] = $this->userWithFamily();
        $author->update(['notification_preferences' => ['push' => false, 'email' => false]]);

        $notification = app(NotificationService::class)->dispatchForUser(
            $author,
            NotificationService::CATEGORY_FAMILY_UPDATES,
            'article_published',
            'Judul',
            'Isi',
        );

        $this->assertNotNull($notification);
        $this->assertDatabaseHas('notifications', ['id' => $notification->id]);
        Queue::assertNotPushed(SendPushNotification::class);
        NotificationFacade::assertNothingSent();
    }

    public function test_publishing_an_article_queues_a_family_update(): void
    {
        Queue::fake();
        [$author, $family] = $this->userWithFamily();
        $article = $this->article($family, $author, Article::STATUS_DRAFT);

        app(ArticleService::class)->publish($article, $author);

        Queue::assertPushed(NotifyFamilyOfUpdate::class, fn (NotifyFamilyOfUpdate $job) => $job->familyId === $family->id
            && $job->type === 'article_published'
            && $job->exceptUserId === $author->id);
    }

    public function test_creating_an_event_queues_a_family_update(): void
    {
        Queue::fake();
        [$organizer, $family] = $this->userWithFamily();

        app(EventService::class)->create($organizer, new EventData(
            $family->uuid,
            'Reuni',
            now()->addWeek()->format('Y-m-d H:i:s'),
            'Tahunan',
            'Bandung',
        ));

        Queue::assertPushed(NotifyFamilyOfUpdate::class, fn (NotifyFamilyOfUpdate $job) => $job->familyId === $family->id
            && $job->type === 'event_created'
            && $job->exceptUserId === $organizer->id);
    }

    public function test_family_update_job_notifies_other_members_and_respects_opt_out(): void
    {
        Queue::fake();
        NotificationFacade::fake();
        [$actor, $family] = $this->userWithFamily();
        $member = $this->addFamilyUser($family);
        $optedOut = $this->addFamilyUser($family);
        $optedOut->update(['notification_preferences' => ['family_updates' => false]]);

        (new NotifyFamilyOfUpdate($family->id, 'article_published', 'Judul', 'Isi', ['article_uuid' => 'abc'], $actor->id))
            ->handle(app(NotificationService::class));

        $this->assertDatabaseHas('notifications', ['user_id' => $member->id, 'type' => 'article_published']);
        $this->assertDatabaseMissing('notifications', ['user_id' => $optedOut->id]);
        $this->assertDatabaseMissing('notifications', ['user_id' => $actor->id]);
        Queue::assertPushed(SendPushNotification::class);
    }

    public function test_birthday_job_greets_only_linked_users_with_matching_birthday(): void
    {
        Queue::fake();
        NotificationFacade::fake();
        [, $family] = $this->userWithFamily();
        $birthdayUser = User::factory()->create();
        $otherUser = User::factory()->create();

        FamilyMember::factory()->create([
            'family_id' => $family->id,
            'user_id' => $birthdayUser->id,
            'full_name' => 'Ulang Tahun',
            'birth_date' => now()->toDateString(),
            'is_alive' => true,
        ]);
        FamilyMember::factory()->create([
            'family_id' => $family->id,
            'user_id' => $otherUser->id,
            'full_name' => 'Bukan Hari Ini',
            'birth_date' => now()->addDay()->toDateString(),
            'is_alive' => true,
        ]);

        $sent = (new SendBirthdayGreetings)->handle(app(NotificationService::class));

        $this->assertSame(1, $sent);
        $this->assertDatabaseHas('notifications', ['user_id' => $birthdayUser->id, 'type' => 'birthday']);
        $this->assertDatabaseMissing('notifications', ['user_id' => $otherUser->id, 'type' => 'birthday']);
    }

    private function userWithFamily(): array
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        FamilyUserRole::factory()->create([
            'family_id' => $family->id,
            'user_id' => $user->id,
            'role' => FamilyUserRole::ROLE_OWNER,
        ]);

        return [$user, $family];
    }

    private function addFamilyUser(Family $family): User
    {
        $user = User::factory()->create();
        FamilyUserRole::factory()->create([
            'family_id' => $family->id,
            'user_id' => $user->id,
            'role' => FamilyUserRole::ROLE_MEMBER,
        ]);

        return $user;
    }

    private function article(Family $family, User $author, string $status = Article::STATUS_PUBLISHED): Article
    {
        $category = ArticleCategory::factory()->create();

        return Article::factory()->create([
            'family_id' => $family->id,
            'author_id' => $author->id,
            'category_id' => $category->id,
            'status' => $status,
            'published_at' => $status === Article::STATUS_PUBLISHED ? now() : null,
        ]);
    }
}
