<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyBranch;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\MemberRelationship;
use App\Models\User;
use App\Services\WebOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebFamilyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_manage_family_profile_branches_and_roles(): void
    {
        [$owner, $family] = $this->userWithFamily(FamilyUserRole::ROLE_OWNER);
        $invitee = User::factory()->create(['email' => 'anggota@example.test']);
        Storage::fake('public');

        $this->active($owner, $family)->put(route('settings.update'), [
            'name' => 'Keluarga Baru',
            'origin_city' => 'Bandung',
            'logo' => UploadedFile::fake()->image('logo.png'),
            'cover_image' => UploadedFile::fake()->image('cover.jpg'),
        ])->assertRedirect();

        $family->refresh();
        $this->assertSame('Keluarga Baru', $family->name);
        Storage::disk('public')->assertExists($family->logo);
        Storage::disk('public')->assertExists($family->cover_image);

        $this->active($owner, $family)->post(route('settings.branches.store'), [
            'name' => 'Cabang Jakarta',
        ])->assertRedirect();
        $this->assertDatabaseHas('family_branches', ['family_id' => $family->id, 'name' => 'Cabang Jakarta']);

        $this->active($owner, $family)->post(route('settings.members.invite'), [
            'email' => $invitee->email,
            'role' => FamilyUserRole::ROLE_MEMBER,
        ])->assertRedirect();
        $this->assertDatabaseHas('family_user_roles', ['family_id' => $family->id, 'user_id' => $invitee->id]);
    }

    public function test_member_cannot_change_family_settings(): void
    {
        [$user, $family] = $this->userWithFamily(FamilyUserRole::ROLE_MEMBER);

        $this->active($user, $family)->put(route('settings.update'), ['name' => 'Terlarang'])->assertForbidden();
        $this->active($user, $family)->post(route('settings.branches.store'), ['name' => 'Terlarang'])->assertForbidden();
    }

    public function test_directory_combines_filters_and_only_shows_active_family(): void
    {
        [$user, $family] = $this->userWithFamily(FamilyUserRole::ROLE_OWNER);
        $branch = FamilyBranch::factory()->create(['family_id' => $family->id, 'name' => 'Jakarta']);
        FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id, 'family_branch_id' => $branch->id, 'full_name' => 'Budi Santoso', 'gender' => 'male', 'is_alive' => true]);
        FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id, 'full_name' => 'Siti Aminah', 'gender' => 'female', 'is_alive' => true]);
        FamilyMember::factory()->create(['full_name' => 'Budi Keluarga Lain']);

        $this->active($user, $family)->get(route('members.index', [
            'search' => 'Budi', 'gender' => 'male', 'is_alive' => '1', 'branch' => $branch->uuid, 'sort' => 'name',
        ]))->assertOk()->assertSee('Budi Santoso')->assertDontSee('Siti Aminah')->assertDontSee('Budi Keluarga Lain');
    }

    public function test_admin_can_create_update_view_and_delete_member_with_photo(): void
    {
        [$admin, $family] = $this->userWithFamily(FamilyUserRole::ROLE_ADMIN);
        Storage::fake('public');

        $response = $this->active($admin, $family)->post(route('members.store'), [
            'full_name' => 'Hasan',
            'gender' => 'male',
            'religion' => 'islam',
            'is_alive' => '0',
            'death_date' => '2020-01-01',
            'photo' => UploadedFile::fake()->image('hasan.jpg'),
        ]);
        $member = FamilyMember::query()->where('full_name', 'Hasan')->firstOrFail();
        $response->assertRedirect(route('members.show', $member));
        $this->active($admin, $family)->get(route('members.show', $member))->assertOk()->assertSeeText('Alm. Hasan');
        Storage::disk('public')->assertExists($member->refresh()->profile_photo_thumbnail);

        $femaleMember = FamilyMember::factory()->deceased()->create([
            'family_id' => $family->id,
            'created_by' => $admin->id,
            'full_name' => 'Siti Aminah',
            'gender' => 'female',
            'religion' => 'islam',
        ]);
        $this->active($admin, $family)
            ->get(route('members.show', $femaleMember))
            ->assertOk()
            ->assertSeeText('Almh. Siti Aminah');

        $this->active($admin, $family)->put(route('members.update', $member), [
            'full_name' => 'Hasan Abdullah', 'is_alive' => '0', 'death_date' => '2020-01-01',
        ])->assertRedirect(route('members.show', $member));
        $this->assertDatabaseHas('family_members', ['id' => $member->id, 'full_name' => 'Hasan Abdullah']);

        $this->active($admin, $family)->delete(route('members.destroy', $member), ['confirm' => '1'])->assertRedirect(route('members.index'));
        $this->assertSoftDeleted($member);
    }

    public function test_member_from_another_active_family_is_not_visible(): void
    {
        [$user, $family] = $this->userWithFamily(FamilyUserRole::ROLE_OWNER);
        $foreignMember = FamilyMember::factory()->create();

        $this->active($user, $family)->get(route('members.show', $foreignMember))->assertNotFound();
    }

    public function test_linked_member_sees_derived_relationship_labels_from_own_perspective(): void
    {
        [$user, $family] = $this->userWithFamily(FamilyUserRole::ROLE_MEMBER);

        $grandFather = $this->member($family, 'Kakek Budi', 'male', '1945-01-01', $user);
        $grandMother = $this->member($family, 'Nenek Siti', 'female', '1947-01-01', $user);
        $father = $this->member($family, 'Ayah Dedi', 'male', '1970-01-01', $user);
        $mother = $this->member($family, 'Ibu Rini', 'female', '1972-01-01', $user);
        $uncle = $this->member($family, 'Om Fajar', 'male', '1975-01-01', $user);
        $cousin = $this->member($family, 'Sepupu Indra', 'male', '1997-01-01', $user);
        $self = $this->member($family, 'Saya Arif', 'male', '1995-01-01', $user);
        $self->update(['user_id' => $user->id]);

        $this->relationship($grandFather, $father, 'father');
        $this->relationship($grandMother, $father, 'mother');
        $this->relationship($grandFather, $uncle, 'father');
        $this->relationship($grandMother, $uncle, 'mother');
        $this->relationship($uncle, $cousin, 'father');
        $this->relationship($father, $self, 'father');
        $this->relationship($mother, $self, 'mother');

        $this->active($user, $family)->get(route('members.show', $father))
            ->assertOk()
            ->assertSeeText('Ayah untuk Anda');
        $this->active($user, $family)->get(route('members.show', $grandFather))
            ->assertOk()
            ->assertSeeText('Kakek untuk Anda');
        $this->active($user, $family)->get(route('members.show', $uncle))
            ->assertOk()
            ->assertSeeText('Om untuk Anda');
        $this->active($user, $family)->get(route('members.show', $cousin))
            ->assertOk()
            ->assertSeeText('Sepupu untuk Anda');
        $this->active($user, $family)->get(route('members.show', $self))
            ->assertOk()
            ->assertSeeText('Saya untuk Anda');
    }

    private function member(Family $family, string $name, string $gender, string $birthDate, User $user): FamilyMember
    {
        return FamilyMember::factory()->create([
            'family_id' => $family->id,
            'full_name' => $name,
            'gender' => $gender,
            'birth_date' => $birthDate,
            'created_by' => $user->id,
        ]);
    }

    private function relationship(FamilyMember $source, FamilyMember $target, string $type): MemberRelationship
    {
        return MemberRelationship::factory()->create([
            'family_id' => $source->family_id,
            'source_member_id' => $source->id,
            'target_member_id' => $target->id,
            'relationship_type' => $type,
        ]);
    }

    private function userWithFamily(string $role): array
    {
        $user = User::factory()->create();
        $family = Family::factory()->create();
        FamilyUserRole::factory()->create(['family_id' => $family->id, 'user_id' => $user->id, 'role' => $role]);

        return [$user, $family];
    }

    private function active(User $user, Family $family): static
    {
        return $this->withSession([WebOnboardingService::ACTIVE_FAMILY_KEY => $family->uuid])->actingAs($user);
    }
}
