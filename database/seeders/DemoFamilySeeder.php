<?php

namespace Database\Seeders;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\MemberRelationship;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoFamilySeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->updateOrCreate(
            ['email' => 'owner@family-tree.test'],
            [
                'name' => 'Owner Demo',
                'phone' => '081200000001',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'status' => 'active',
            ],
        );

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@family-tree.test'],
            [
                'name' => 'Admin Demo',
                'phone' => '081200000002',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'status' => 'active',
            ],
        );

        $memberUser = User::query()->updateOrCreate(
            ['email' => 'member@family-tree.test'],
            [
                'name' => 'Member Demo',
                'phone' => '081200000003',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'status' => 'active',
            ],
        );

        $family = Family::query()->updateOrCreate(
            ['slug' => 'keluarga-demo-santoso'],
            [
                'name' => 'Keluarga Demo Santoso',
                'description' => 'Dataset demo untuk smoke test API keluarga dan Relationship Engine.',
                'origin_city' => 'Bandung',
                'created_by' => $owner->id,
            ],
        );

        $this->role($family, $owner, FamilyUserRole::ROLE_OWNER);
        $this->role($family, $admin, FamilyUserRole::ROLE_ADMIN);
        $this->role($family, $memberUser, FamilyUserRole::ROLE_MEMBER);

        $members = [
            'great_grand_father' => $this->member($family, $owner, 'Buyut Ahmad Santoso', 'male', '1920-01-01', false, '2000-05-01'),
            'great_grand_mother' => $this->member($family, $owner, 'Buyut Aminah Santoso', 'female', '1922-03-12', false, '2004-08-21'),
            'grand_father' => $this->member($family, $owner, 'Kakek Budi Santoso', 'male', '1945-01-10', false, '2020-02-02'),
            'grand_mother' => $this->member($family, $owner, 'Nenek Siti Santoso', 'female', '1947-07-18', true),
            'father' => $this->member($family, $owner, 'Ayah Dedi Santoso', 'male', '1970-01-01', true),
            'mother' => $this->member($family, $owner, 'Ibu Rini Santoso', 'female', '1972-04-09', true),
            'source' => $this->member($family, $owner, 'Saya Arif Santoso', 'male', '1995-01-01', true),
            'brother' => $this->member($family, $owner, 'Kakak Bima Santoso', 'male', '1992-02-14', true),
            'sister' => $this->member($family, $owner, 'Adik Citra Santoso', 'female', '1998-09-30', true),
            'older_uncle' => $this->member($family, $owner, 'Pakde Eko Santoso', 'male', '1965-06-15', true),
            'older_uncle_wife' => $this->member($family, $owner, 'Bude Lestari Santoso', 'female', '1966-11-11', true),
            'younger_uncle' => $this->member($family, $owner, 'Om Fajar Santoso', 'male', '1975-12-02', true),
            'older_aunt' => $this->member($family, $owner, 'Bude Gita Santoso', 'female', '1968-05-05', true),
            'younger_aunt' => $this->member($family, $owner, 'Tante Hana Santoso', 'female', '1978-10-10', true),
            'cousin' => $this->member($family, $owner, 'Sepupu Indra Santoso', 'male', '1997-03-03', true),
            'nephew' => $this->member($family, $owner, 'Keponakan Jaya Santoso', 'male', '2020-01-20', true),
            'spouse' => $this->member($family, $owner, 'Istri Kania Santoso', 'female', '1996-02-20', true),
            'father_in_law' => $this->member($family, $owner, 'Mertua Bambang', 'male', '1968-08-08', true),
            'mother_in_law' => $this->member($family, $owner, 'Mertua Wati', 'female', '1969-09-09', true),
            'son' => $this->member($family, $owner, 'Anak Laki Santoso', 'male', '2018-04-01', true),
            'daughter' => $this->member($family, $owner, 'Anak Perempuan Santoso', 'female', '2020-06-01', true),
            'daughter_in_law' => $this->member($family, $owner, 'Menantu Nisa Santoso', 'female', '2019-07-07', true),
            'grand_child' => $this->member($family, $owner, 'Cucu Rara Santoso', 'female', '2040-01-01', true),
            'great_grand_child' => $this->member($family, $owner, 'Cicit Rio Santoso', 'male', '2060-01-01', true),
        ];

        $this->relationship($family, $members['great_grand_father'], $members['grand_father'], MemberRelationship::TYPE_FATHER);
        $this->relationship($family, $members['great_grand_mother'], $members['grand_father'], MemberRelationship::TYPE_MOTHER);
        $this->relationship($family, $members['grand_father'], $members['father'], MemberRelationship::TYPE_FATHER);
        $this->relationship($family, $members['grand_mother'], $members['father'], MemberRelationship::TYPE_MOTHER);
        $this->relationship($family, $members['grand_father'], $members['older_uncle'], MemberRelationship::TYPE_FATHER);
        $this->relationship($family, $members['grand_mother'], $members['older_uncle'], MemberRelationship::TYPE_MOTHER);
        $this->relationship($family, $members['grand_father'], $members['younger_uncle'], MemberRelationship::TYPE_FATHER);
        $this->relationship($family, $members['grand_mother'], $members['younger_uncle'], MemberRelationship::TYPE_MOTHER);
        $this->relationship($family, $members['grand_father'], $members['older_aunt'], MemberRelationship::TYPE_FATHER);
        $this->relationship($family, $members['grand_mother'], $members['older_aunt'], MemberRelationship::TYPE_MOTHER);
        $this->relationship($family, $members['grand_father'], $members['younger_aunt'], MemberRelationship::TYPE_FATHER);
        $this->relationship($family, $members['grand_mother'], $members['younger_aunt'], MemberRelationship::TYPE_MOTHER);
        $this->relationship($family, $members['older_uncle'], $members['cousin'], MemberRelationship::TYPE_FATHER);
        $this->relationship($family, $members['older_uncle'], $members['older_uncle_wife'], MemberRelationship::TYPE_HUSBAND);
        $this->relationship($family, $members['father'], $members['source'], MemberRelationship::TYPE_FATHER);
        $this->relationship($family, $members['mother'], $members['source'], MemberRelationship::TYPE_MOTHER);
        $this->relationship($family, $members['father'], $members['brother'], MemberRelationship::TYPE_FATHER);
        $this->relationship($family, $members['mother'], $members['brother'], MemberRelationship::TYPE_MOTHER);
        $this->relationship($family, $members['father'], $members['sister'], MemberRelationship::TYPE_FATHER);
        $this->relationship($family, $members['mother'], $members['sister'], MemberRelationship::TYPE_MOTHER);
        $this->relationship($family, $members['brother'], $members['nephew'], MemberRelationship::TYPE_FATHER);
        $this->relationship($family, $members['source'], $members['spouse'], MemberRelationship::TYPE_HUSBAND);
        $this->relationship($family, $members['father_in_law'], $members['spouse'], MemberRelationship::TYPE_FATHER);
        $this->relationship($family, $members['mother_in_law'], $members['spouse'], MemberRelationship::TYPE_MOTHER);
        $this->relationship($family, $members['source'], $members['son'], MemberRelationship::TYPE_FATHER);
        $this->relationship($family, $members['spouse'], $members['son'], MemberRelationship::TYPE_MOTHER);
        $this->relationship($family, $members['source'], $members['daughter'], MemberRelationship::TYPE_FATHER);
        $this->relationship($family, $members['spouse'], $members['daughter'], MemberRelationship::TYPE_MOTHER);
        $this->relationship($family, $members['son'], $members['daughter_in_law'], MemberRelationship::TYPE_HUSBAND);
        $this->relationship($family, $members['daughter'], $members['grand_child'], MemberRelationship::TYPE_MOTHER);
        $this->relationship($family, $members['grand_child'], $members['great_grand_child'], MemberRelationship::TYPE_MOTHER);
    }

    private function role(Family $family, User $user, string $role): void
    {
        FamilyUserRole::query()->updateOrCreate(
            [
                'family_id' => $family->id,
                'user_id' => $user->id,
            ],
            ['role' => $role],
        );
    }

    private function member(
        Family $family,
        User $creator,
        string $fullName,
        string $gender,
        string $birthDate,
        bool $isAlive,
        ?string $deathDate = null,
    ): FamilyMember {
        return FamilyMember::query()->updateOrCreate(
            [
                'family_id' => $family->id,
                'full_name' => $fullName,
            ],
            [
                'nickname' => explode(' ', $fullName)[0],
                'gender' => $gender,
                'birth_date' => $birthDate,
                'birth_place' => 'Bandung',
                'is_alive' => $isAlive,
                'death_date' => $deathDate,
                'death_place' => $deathDate === null ? null : 'Bandung',
                'biography' => 'Data demo untuk pengujian Relationship Engine.',
                'created_by' => $creator->id,
            ],
        );
    }

    private function relationship(
        Family $family,
        FamilyMember $source,
        FamilyMember $target,
        string $type,
    ): void {
        MemberRelationship::query()->updateOrCreate(
            [
                'family_id' => $family->id,
                'source_member_id' => $source->id,
                'target_member_id' => $target->id,
                'relationship_type' => $type,
            ],
            [
                'notes' => 'Relasi dasar dari demo seeder.',
            ],
        );
    }
}
