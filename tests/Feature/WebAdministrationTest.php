<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\AuditLog;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WebAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_administration_page_requires_a_super_admin(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create();

        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->actingAs($user)->get('/admin/users')->assertForbidden();
        $this->actingAs($user)->get('/admin/families')->assertForbidden();
        $this->actingAs($user)->get("/admin/families/{$family->uuid}")->assertForbidden();
        $this->actingAs($user)->get('/admin/audit-logs')->assertForbidden();
    }

    public function test_super_admin_can_use_dashboard_and_manage_users(): void
    {
        $admin = $this->superAdmin();
        $user = User::factory()->create();

        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Administrasi sistem');
        $this->actingAs($admin)->get('/admin/users')->assertOk()->assertSee($user->email);
        $this->actingAs($admin)->patch("/admin/users/{$user->uuid}", ['status' => 'suspended'])
            ->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'suspended']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.suspended', 'auditable_uuid' => $user->uuid]);
    }

    public function test_moderation_requires_confirmation_and_creates_an_audit_record(): void
    {
        $admin = $this->superAdmin();
        $family = Family::factory()->create();
        $article = Article::factory()->create(['family_id' => $family->id]);
        $url = "/admin/families/{$family->uuid}/content";

        $this->actingAs($admin)->delete($url, ['content_type' => 'article', 'content_uuid' => $article->uuid])
            ->assertSessionHasErrors('confirm');
        $this->assertNotSoftDeleted('articles', ['id' => $article->id]);

        $this->actingAs($admin)->delete($url, ['content_type' => 'article', 'content_uuid' => $article->uuid, 'confirm' => '1'])
            ->assertRedirect()->assertSessionHas('success');
        $this->assertSoftDeleted('articles', ['id' => $article->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'content.removed', 'auditable_uuid' => $article->uuid]);
    }

    public function test_super_admin_can_filter_and_export_audit_logs(): void
    {
        $admin = $this->superAdmin();
        AuditLog::query()->create(['user_id' => $admin->id, 'action' => 'user.active', 'auditable_type' => User::class, 'auditable_id' => $admin->id, 'auditable_uuid' => $admin->uuid]);

        $this->actingAs($admin)->get('/admin/audit-logs?action=user.active')->assertOk()->assertSee('user.active');
        $this->actingAs($admin)->get('/admin/audit-logs/export?action=user.active')->assertDownload('audit-logs.csv');
    }

    private function superAdmin(): User
    {
        $role = Role::findOrCreate('super-admin', 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
