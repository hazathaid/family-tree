<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\FamilyLink;
use App\Models\FamilyMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<FamilyLink> */
class FamilyLinkFactory extends Factory
{
    protected $model = FamilyLink::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'family_id' => Family::factory(),
            'linked_family_id' => Family::factory(),
            'member_id' => fn (array $attributes): int => FamilyMember::factory()
                ->create(['family_id' => $attributes['family_id']])->id,
            'linked_member_id' => fn (array $attributes): int => FamilyMember::factory()
                ->create(['family_id' => $attributes['linked_family_id']])->id,
            'link_type' => FamilyLink::LINK_TYPE_MARRIAGE,
            'children_home_family_id' => null,
            'status' => FamilyLink::STATUS_PENDING,
            'requested_by' => User::factory(),
            'approved_by_family' => null,
            'approved_by_linked_family' => null,
            'expires_at' => now()->addDays(30),
            'revoked_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'status' => FamilyLink::STATUS_ACTIVE,
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn (): array => [
            'status' => FamilyLink::STATUS_REVOKED,
            'revoked_at' => now(),
        ]);
    }
}
