<?php

namespace Tests\Unit;

use App\Models\Family;
use App\Models\FamilyBranch;
use App\Models\FamilyMember;
use App\Models\User;
use App\Services\FamilyMemberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FamilyMemberServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_member_resolves_branch_and_sets_creator(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $branch = FamilyBranch::factory()->create(['family_id' => $family->id]);

        $member = app(FamilyMemberService::class)->create($user, $family, [
            'family_branch_uuid' => $branch->uuid,
            'full_name' => 'Hasan Basri',
            'is_alive' => true,
        ]);

        $this->assertSame($family->id, $member->family_id);
        $this->assertSame($branch->id, $member->family_branch_id);
        $this->assertSame($user->id, $member->created_by);
        $this->assertTrue($member->is_alive);
    }

    public function test_create_member_rejects_branch_from_another_family(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $otherFamily = Family::factory()->create(['created_by' => $user->id]);
        $branch = FamilyBranch::factory()->create(['family_id' => $otherFamily->id]);

        $this->expectException(ValidationException::class);

        app(FamilyMemberService::class)->create($user, $family, [
            'family_branch_uuid' => $branch->uuid,
            'full_name' => 'Hasan Basri',
            'is_alive' => true,
        ]);
    }

    public function test_upload_photo_replaces_existing_files(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $member = FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $user->id,
            'profile_photo' => 'old/photo.png',
            'profile_photo_thumbnail' => 'old/thumb_photo.png',
        ]);
        Storage::disk('public')->put('old/photo.png', 'old');
        Storage::disk('public')->put('old/thumb_photo.png', 'old');

        $updated = app(FamilyMemberService::class)->uploadPhoto(
            $member,
            UploadedFile::fake()->createWithContent('profile.png', $this->tinyPng())
        );

        Storage::disk('public')->assertMissing('old/photo.png');
        Storage::disk('public')->assertMissing('old/thumb_photo.png');
        Storage::disk('public')->assertExists($updated->profile_photo);
        Storage::disk('public')->assertExists($updated->profile_photo_thumbnail);
    }

    private function tinyPng(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=') ?: '';
    }
}
