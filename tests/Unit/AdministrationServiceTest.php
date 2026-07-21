<?php

namespace Tests\Unit;

use App\Models\Family;
use App\Models\User;
use App\Services\AdministrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdministrationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_current_administration_counts(): void
    {
        $owner = User::factory()->create();
        User::factory()->suspended()->create();
        Family::factory()->create(['created_by' => $owner->id]);

        $statistics = app(AdministrationService::class)->dashboard();

        $this->assertSame(2, $statistics['users']);
        $this->assertSame(1, $statistics['suspended_users']);
        $this->assertSame(1, $statistics['families']);
    }

    public function test_user_status_is_updated_and_audited(): void
    {
        $actor = User::factory()->create();
        $user = User::factory()->create();

        $updated = app(AdministrationService::class)->updateUserStatus($actor, $user, 'suspended');

        $this->assertSame('suspended', $updated->status);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $actor->id,
            'action' => 'user.suspended',
            'auditable_uuid' => $user->uuid,
        ]);
    }

    public function test_actor_cannot_suspend_self(): void
    {
        $actor = User::factory()->create();

        $this->expectException(ValidationException::class);

        app(AdministrationService::class)->updateUserStatus($actor, $actor, 'suspended');
    }
}
