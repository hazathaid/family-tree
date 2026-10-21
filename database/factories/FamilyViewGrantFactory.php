<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyViewGrant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<FamilyViewGrant> */
class FamilyViewGrantFactory extends Factory
{
    protected $model = FamilyViewGrant::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'family_id' => Family::factory(),
            'anchor_member_id' => fn (array $attributes): int => FamilyMember::factory()
                ->create(['family_id' => $attributes['family_id']])->id,
            'granted_by' => User::factory(),
            'grantee_user_id' => User::factory(),
            'invited_email' => fn (array $attributes): string => User::query()
                ->find($attributes['grantee_user_id'])?->email ?? 'grantee@example.test',
            'token_hash' => hash('sha256', Str::random(64)),
            'can_view_living' => false,
            'expires_at' => now()->addDays(30),
            'revoked_at' => null,
            'last_accessed_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => ['expires_at' => now()->subDay()]);
    }

    public function revoked(): static
    {
        return $this->state(fn (): array => ['revoked_at' => now()]);
    }
}
