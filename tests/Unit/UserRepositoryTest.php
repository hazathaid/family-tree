<?php

namespace Tests\Unit;

use App\Models\User;
use App\Repositories\Eloquent\EloquentUserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_repository_finds_users_by_email_and_uuid(): void
    {
        $user = User::factory()->create(['email' => 'repo@example.com']);
        $repository = new EloquentUserRepository();

        $this->assertTrue($repository->findByEmail('repo@example.com')?->is($user));
        $this->assertTrue($repository->findByUuid($user->uuid)?->is($user));
    }

    public function test_repository_updates_user(): void
    {
        $user = User::factory()->create();
        $repository = new EloquentUserRepository();

        $updated = $repository->update($user, ['name' => 'Updated Name']);

        $this->assertSame('Updated Name', $updated->name);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Name']);
    }
}
