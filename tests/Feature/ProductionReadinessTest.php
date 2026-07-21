<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_reports_dependencies_without_authentication(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Healthy',
                'data' => [
                    'status' => 'ok',
                    'checks' => ['database' => 'ok', 'redis' => 'ok'],
                ],
            ]);
    }

    public function test_responses_include_security_headers(): void
    {
        $this->getJson('/api/v1/health')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->assertHeader('Content-Security-Policy');
    }

    public function test_login_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'unknown@example.com',
                'password' => 'invalid-password',
            ])->assertUnprocessable();
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => 'unknown@example.com',
            'password' => 'invalid-password',
        ])->assertTooManyRequests();
    }

    public function test_operational_dashboards_are_restricted_to_super_admins(): void
    {
        $member = User::factory()->create();
        Sanctum::actingAs($member);

        self::assertFalse(Gate::forUser($member)->allows('viewHorizon'));
        self::assertFalse(Gate::forUser($member)->allows('viewTelescope'));

        $role = Role::findOrCreate('super-admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole($role);

        self::assertTrue(Gate::forUser($admin)->allows('viewHorizon'));
        self::assertTrue(Gate::forUser($admin)->allows('viewTelescope'));
    }

    public function test_production_maintenance_commands_are_registered(): void
    {
        $commands = Artisan::all();

        self::assertArrayHasKey('backup:run', $commands);
        self::assertArrayHasKey('backup:monitor', $commands);
        self::assertArrayHasKey('horizon', $commands);
        self::assertArrayHasKey('telescope:prune', $commands);
    }
}
