<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\MemberRelationship;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MemberRelationship>
 */
class MemberRelationshipFactory extends Factory
{
    public function definition(): array
    {
        $family = Family::factory();

        return [
            'uuid' => (string) Str::uuid(),
            'family_id' => $family,
            'source_member_id' => FamilyMember::factory()->state(['family_id' => $family]),
            'target_member_id' => FamilyMember::factory()->state(['family_id' => $family]),
            'relationship_type' => fake()->randomElement(MemberRelationship::TYPES),
            'start_date' => null,
            'end_date' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
