<?php

namespace Tests\Unit;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\MemberPhoto;
use App\Models\User;
use App\Services\MemberPhotoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MemberPhotoServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_tags_are_synchronized_and_limited_to_photo_family(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create();
        $photo = MemberPhoto::factory()->create(['family_id' => $family->id, 'uploaded_by' => $user->id]);
        $member = FamilyMember::factory()->create(['family_id' => $family->id]);
        $service = app(MemberPhotoService::class);

        $result = $service->tag($photo, [$member->uuid]);
        $this->assertSame([$member->uuid], $result->taggedMembers->pluck('uuid')->all());

        $this->expectException(ValidationException::class);
        $service->tag($photo, [FamilyMember::factory()->create()->uuid]);
    }
}
