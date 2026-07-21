<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Event;
use App\Models\Family;
use App\Models\FamilyUserRole;
use App\Models\Notification;
use App\Models\User;
use App\Services\WebOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebEngagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_pages_hide_other_family_drafts_and_sanitize_content(): void
    {
        [$user, $family] = $this->familyUser();
        $category = ArticleCategory::factory()->create();
        $otherDraft = Article::factory()->create(['title' => 'Rahasia keluarga lain']);

        $this->web($user, $family)->post('/articles', [
            'family_uuid' => $family->uuid, 'category_uuid' => $category->uuid,
            'title' => 'Sejarah Kami', 'content' => '<p onclick="evil()">Cerita<script>evil()</script></p>', 'status' => 'published',
        ])->assertRedirect();

        $article = Article::query()->where('family_id', $family->id)->firstOrFail();
        $this->web($user, $family)->get('/articles')->assertOk()->assertSee('Sejarah Kami')->assertDontSee($otherDraft->title);
        $this->web($user, $family)->get('/articles/'.$article->uuid)->assertOk()->assertSee('Cerita')->assertDontSee('onclick', false)->assertDontSee('<script>', false);
    }

    public function test_photo_gallery_upload_is_paginated_and_scoped(): void
    {
        Storage::fake('public');
        [$user, $family] = $this->familyUser();
        $this->web($user, $family)->post('/photos', [
            'family_uuid' => $family->uuid, 'caption' => 'Lebaran bersama',
            'image' => UploadedFile::fake()->image('lebaran.jpg', 800, 600),
        ])->assertRedirect();

        $this->web($user, $family)->get('/photos')->assertOk()->assertSee('Lebaran bersama', false);
        $this->web($user, $family)->post('/photos', ['family_uuid' => $family->uuid, 'image' => UploadedFile::fake()->create('besar.jpg', 10241, 'image/jpeg')])->assertSessionHasErrors('image');
    }

    public function test_event_rsvp_supports_all_documented_states(): void
    {
        [$user, $family] = $this->familyUser();
        $event = Event::factory()->create(['family_id' => $family->id, 'event_date' => now()->addWeek()]);

        foreach (['yes', 'no', 'maybe'] as $status) {
            $this->web($user, $family)->post('/events/'.$event->uuid.'/rsvp', ['status' => $status])->assertRedirect();
            $this->assertDatabaseHas('event_attendees', ['event_id' => $event->id, 'user_id' => $user->id, 'status' => $status]);
        }
        $this->web($user, $family)->get('/events/'.$event->uuid)->assertOk()->assertSee($event->event_date->timezone(config('app.timezone'))->format('d M Y, H:i'));
    }

    public function test_timeline_and_notifications_are_scoped_and_can_be_marked_read(): void
    {
        [$user, $family] = $this->familyUser();
        ActivityLog::factory()->create(['family_id' => $family->id, 'payload' => ['title' => 'Acara keluarga']]);
        $notification = Notification::factory()->create(['user_id' => $user->id, 'title' => 'Pengingat reuni', 'is_read' => false]);
        Notification::factory()->create(['title' => 'Notifikasi orang lain']);

        $this->web($user, $family)->get('/timeline')->assertOk()->assertSee('Acara keluarga');
        $this->web($user, $family)->get('/notifications')->assertOk()->assertSee('Pengingat reuni')->assertDontSee('Notifikasi orang lain');
        $this->web($user, $family)->post('/notifications/'.$notification->uuid.'/read')->assertRedirect();
        $this->assertDatabaseHas('notifications', ['id' => $notification->id, 'is_read' => true]);
    }

    private function familyUser(): array
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        FamilyUserRole::factory()->create(['family_id' => $family->id, 'user_id' => $user->id, 'role' => FamilyUserRole::ROLE_ADMIN]);

        return [$user, $family];
    }

    private function web(User $user, Family $family): static
    {
        return $this->withSession([WebOnboardingService::ACTIVE_FAMILY_KEY => $family->uuid])->actingAs($user);
    }
}
