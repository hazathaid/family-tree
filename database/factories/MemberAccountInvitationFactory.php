<?php

namespace Database\Factories;

use App\Models\FamilyMember;
use App\Models\MemberAccountInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<MemberAccountInvitation> */
class MemberAccountInvitationFactory extends Factory
{
    protected $model = MemberAccountInvitation::class;

    public function definition(): array
    {
        return [
            'family_member_id' => FamilyMember::factory(),
            'invited_by' => User::factory(),
            'email' => fake()->unique()->safeEmail(),
            'token_hash' => hash('sha256', Str::random(64)),
            'expires_at' => now()->addDays(7),
        ];
    }
}
