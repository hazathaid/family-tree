<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FamilyTreeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_family_member_can_generate_and_export_tree(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $member = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $user->id]);
        Sanctum::actingAs($user);
        $query = '?member_uuid='.$member->uuid.'&mode=full&depth=5&layout=radial';
        $this->getJson('/api/v1/tree/generate'.$query)->assertOk()->assertJsonPath('data.layout', 'radial')->assertJsonPath('data.statistics.members', 1);
        $this->get('/api/v1/tree/export/png'.$query)->assertOk()->assertHeader('content-type', 'image/png');
        $this->get('/api/v1/tree/export/pdf'.$query)->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_tree_request_validates_parameters(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/v1/tree/generate?member_uuid=nope&mode=wrong&depth=21')->assertUnprocessable()
            ->assertJsonValidationErrors(['member_uuid', 'mode', 'depth']);
    }
}
