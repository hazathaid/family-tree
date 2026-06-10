<?php

namespace Tests\Unit;

use App\Models\User;
use App\Repositories\Eloquent\EloquentUserRepository;
use App\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_changing_password_hashes_new_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-secret')]);
        $service = new ProfileService(new EloquentUserRepository());

        $service->changePassword($user, 'new-secret');

        $this->assertTrue(Hash::check('new-secret', $user->refresh()->password));
    }

    public function test_email_verification_is_cleared_when_email_changes(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com', 'email_verified_at' => now()]);
        $service = new ProfileService(new EloquentUserRepository());

        $updated = $service->update($user, [
            'name' => $user->name,
            'email' => 'new@example.com',
            'phone' => null,
        ]);

        $this->assertNull($updated->email_verified_at);
    }
}
