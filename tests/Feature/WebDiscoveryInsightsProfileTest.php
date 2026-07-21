<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\User;
use App\Services\WebOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebDiscoveryInsightsProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_search_is_grouped_paginated_and_scoped_to_active_family(): void
    {
        [$user, $family] = $this->familyUser();
        $otherFamily = Family::factory()->create();
        FamilyMember::factory()->create(['family_id' => $family->id, 'full_name' => 'Budi Bandung', 'birth_place' => 'Bandung']);
        FamilyMember::factory()->create(['family_id' => $otherFamily->id, 'full_name' => 'Budi Rahasia']);
        Article::factory()->published()->create(['family_id' => $family->id, 'author_id' => $user->id, 'title' => 'Kisah Budi']);

        $this->web($user, $family)->get('/search?keyword=Budi&city=Bandung')
            ->assertOk()->assertSee('Budi Bandung')->assertSee('Kisah Budi')->assertDontSee('Budi Rahasia')
            ->assertSee('Anggota')->assertSee('Artikel')->assertSee('Acara');
    }

    public function test_search_rejects_unsafe_or_invalid_filter_input(): void
    {
        [$user, $family] = $this->familyUser();

        $this->web($user, $family)->get('/search?keyword='.str_repeat('x', 101))->assertSessionHasErrors('keyword');
        $this->web($user, $family)->get('/search?generation=999')->assertSessionHasErrors('generation');
    }

    public function test_reports_show_accessible_tables_gamification_and_family_scoped_insights(): void
    {
        [$user, $family] = $this->familyUser();
        FamilyMember::factory()->create(['family_id' => $family->id, 'birth_place' => 'Bandung']);

        $this->web($user, $family)->get('/reports')->assertOk()
            ->assertSee('Laporan')
            ->assertSee('Anggota per kota')->assertSee('Bandung')
            ->assertSee('Papan peringkat keluarga')->assertSee('Data Anggota per kota');
    }

    public function test_profile_updates_preferences_avatar_and_requires_password_for_email_change(): void
    {
        Storage::fake('public');
        [$user, $family] = $this->familyUser();

        $this->web($user, $family)->put('/profile', ['name' => 'Nama Baru', 'email' => 'baru@example.test', 'phone' => '0812'])
            ->assertSessionHasErrors('current_password');
        $this->web($user, $family)->put('/profile', ['name' => 'Nama Baru', 'email' => 'baru@example.test', 'phone' => '0812', 'current_password' => 'password'])
            ->assertRedirect();
        $user->forceFill(['email_verified_at' => now()])->save();
        $this->web($user->refresh(), $family)->put('/profile/preferences', ['email_events' => '1', 'email_birthdays' => '0'])
            ->assertRedirect();
        $this->web($user->refresh(), $family)->post('/profile/avatar', ['avatar' => UploadedFile::fake()->image('avatar.jpg')])
            ->assertRedirect();

        $user->refresh();
        $this->assertSame('Nama Baru', $user->name);
        $this->assertTrue($user->notification_preferences['email_events']);
        $this->assertFalse($user->notification_preferences['email_birthdays']);
        Storage::disk('public')->assertExists($user->avatar);
    }

    public function test_password_change_requires_current_password_and_session_is_regenerated(): void
    {
        [$user, $family] = $this->familyUser();
        $response = $this->web($user, $family)->put('/profile/password', [
            'current_password' => 'password', 'password' => 'new-password', 'password_confirmation' => 'new-password',
        ]);

        $response->assertRedirect();
        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    private function familyUser(): array
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        FamilyUserRole::factory()->create(['family_id' => $family->id, 'user_id' => $user->id]);

        return [$user, $family];
    }

    private function web(User $user, Family $family): static
    {
        return $this->withSession([WebOnboardingService::ACTIVE_FAMILY_KEY => $family->uuid])->actingAs($user);
    }
}
