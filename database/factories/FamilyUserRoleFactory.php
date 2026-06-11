<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\FamilyUserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FamilyUserRole>
 */
class FamilyUserRoleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'family_id' => Family::factory(),
            'user_id' => User::factory(),
            'role' => FamilyUserRole::ROLE_MEMBER,
        ];
    }

    public function owner(): static
    {
        return $this->state(fn (): array => [
            'role' => FamilyUserRole::ROLE_OWNER,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (): array => [
            'role' => FamilyUserRole::ROLE_ADMIN,
        ]);
    }
}
