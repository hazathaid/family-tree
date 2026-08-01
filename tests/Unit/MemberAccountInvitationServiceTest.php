<?php

namespace Tests\Unit;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\User;
use App\Services\MemberAccountInvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MemberAccountInvitationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_reinviting_revokes_previous_token_and_accept_is_single_use(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $owner->id]);
        FamilyUserRole::factory()->owner()->create(['family_id' => $family->id, 'user_id' => $owner->id]);
        $member = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $owner->id]);
        $service = app(MemberAccountInvitationService::class);

        $first = $service->invite($owner, $member, 'first@example.test');
        $second = $service->invite($owner, $member, 'second@example.test');

        $this->assertNull($service->findByPlainToken($first['plain_token']));
        $user = $service->accept($second['plain_token'], ['name' => 'Member', 'password' => 'password123']);
        $this->assertSame($member->id, $user->familyMemberProfiles()->firstOrFail()->id);

        $this->expectException(ValidationException::class);
        $service->accept($second['plain_token'], ['name' => 'Member', 'password' => 'password123']);
    }
}
