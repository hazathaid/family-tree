<?php

namespace Tests\Feature;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PruneRefreshTokensCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prunes_expired_and_long_revoked_tokens(): void
    {
        $user = User::factory()->create();

        $expired = $this->token($user, ['expires_at' => now()->subDay()]);
        $oldRevoked = $this->token($user, ['expires_at' => now()->addDay(), 'revoked_at' => now()->subDays(10)]);
        $recentRevoked = $this->token($user, ['expires_at' => now()->addDay(), 'revoked_at' => now()->subDay()]);
        $active = $this->token($user, ['expires_at' => now()->addDay()]);

        $this->artisan('refresh-tokens:prune')->assertExitCode(0);

        $this->assertDatabaseMissing('refresh_tokens', ['id' => $expired->id]);
        $this->assertDatabaseMissing('refresh_tokens', ['id' => $oldRevoked->id]);
        $this->assertDatabaseHas('refresh_tokens', ['id' => $recentRevoked->id]);
        $this->assertDatabaseHas('refresh_tokens', ['id' => $active->id]);
    }

    public function test_zero_day_retention_prunes_all_revoked_tokens(): void
    {
        $user = User::factory()->create();
        $revoked = $this->token($user, ['expires_at' => now()->addDay(), 'revoked_at' => now()]);

        $this->artisan('refresh-tokens:prune', ['--days' => 0])->assertExitCode(0);

        $this->assertDatabaseMissing('refresh_tokens', ['id' => $revoked->id]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function token(User $user, array $attributes): RefreshToken
    {
        return RefreshToken::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'token_hash' => hash('sha256', Str::random(64)),
            'expires_at' => now()->addDay(),
            ...$attributes,
        ]);
    }
}
