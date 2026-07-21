<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\AuditLog;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdministrationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_suspend_and_activate_user(): void
    {
        $admin = $this->superAdmin();
        $user = User::factory()->create();
        $user->createToken('mobile');
        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/admin/users/{$user->uuid}", ['status' => 'suspended'])
            ->assertOk()
            ->assertJsonPath('data.status', 'suspended');

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.suspended', 'auditable_uuid' => $user->uuid]);

        $this->patchJson("/api/v1/admin/users/{$user->uuid}", ['status' => 'active'])
            ->assertOk()
            ->assertJsonPath('data.status', 'active');
    }

    public function test_super_admin_can_review_families_and_remove_content(): void
    {
        $admin = $this->superAdmin();
        $family = Family::factory()->create();
        $article = Article::factory()->create(['family_id' => $family->id]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/admin/families')
            ->assertOk()
            ->assertJsonPath('data.0.uuid', $family->uuid);

        $this->deleteJson("/api/v1/admin/families/{$family->uuid}/content", [
            'content_type' => 'article',
            'content_uuid' => $article->uuid,
        ])->assertOk()->assertJsonPath('message', 'Content removed');

        $this->assertSoftDeleted('articles', ['id' => $article->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'content.removed', 'auditable_uuid' => $article->uuid]);
    }

    public function test_super_admin_can_view_and_export_audit_logs(): void
    {
        $admin = $this->superAdmin();
        AuditLog::query()->create([
            'user_id' => $admin->id,
            'action' => 'user.active',
            'auditable_type' => User::class,
            'auditable_id' => $admin->id,
            'auditable_uuid' => $admin->uuid,
        ]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/admin/audit-logs?action=user.active')
            ->assertOk()
            ->assertJsonPath('data.0.action', 'user.active');

        $this->get('/api/v1/admin/audit-logs/export?action=user.active')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertDownload('audit-logs.csv');
    }

    public function test_non_admin_cannot_access_administration_endpoints(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/admin/users')->assertForbidden();
        $this->getJson('/api/v1/admin/families')->assertForbidden();
        $this->getJson('/api/v1/admin/audit-logs')->assertForbidden();
    }

    public function test_admin_cannot_suspend_self(): void
    {
        $admin = $this->superAdmin();
        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/admin/users/{$admin->uuid}", ['status' => 'suspended'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    private function superAdmin(): User
    {
        $role = Role::findOrCreate('super-admin', 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
