<?php

namespace Database\Seeders;

use App\Models\Family;
use App\Models\FamilyBranch;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\MemberRelationship;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class BaniFahrurojiSeeder extends Seeder
{
    private const FAMILY_UUID = '7b79cf25-ee58-5dd8-a957-59a701623a33';

    /** @var list<string> */
    private const BRANCH_ROOTS = [
        'KH. Didin Muhyiddin',
        'KH. Uwesulkorni',
        'Pipih Sopiah',
        'KH. Amir SIdik',
        'Dadang Amir Muslim',
    ];

    /**
     * Names are transcribed from "family tree Fahruroji.drawio".
     */
    private const MEMBERS = <<<'NAMES'
Nyi HJ. E. Khodijah Binti Aheh
KH. Fahrurozi Bin Ama KH Sididiq
KH. Didin Muhyiddin
Hj. Euis Djulaeha
Yayan Supriatna
Imas Nur Asiah
Mochammad Rifa
Siti Mardiyah Ulfah
Asla Aliza Afkariyah
Aisya Syuba Mahdiyah
H. Rizal Yusup Ramdhan
Hj. Dian Sudiarni
Risman Rismansyah
Roza Dira Haqi
Aliya Dira Kholqi
Muhammad Ryuma Syahdira
H. Ahmad Farid
Hj. Lina Rosdiana
Mohamad Musa
Ummu Hawa Assa'diyah
Amira Shofia Mecca
Lutfi Muharram
Ety Lestari
Adam Ibrahim Alfath
Muna ilfa Salsabila
Aydan Muhammad Mulk
Nuzha Athalla Muadz
Laila Rahmah
Laula Rizky
Alif Rahmatullah
Diva Lizetiany
Shireen Jenna Rumaisya
H. Rosmana Kurniadi
Siti Zenab Nurlaila
Ibnu Fikri Pamungkas
Arriba Gissani
Aabida Karimah Al Mas'udah
Muhammad Firmansyah
Suci Trisiani Ajeng Purwandani
Tsabita Putri
Asep Rijwan Suhendi
Ami Kulsum
Abdan Syakuuro
Aqila Tamamah Sya'diyatuddaraini
KH. Uwesulkorni
Hj. Neneng Haerani
Rachman
Rina Yuniawati
Muhammad Fadhillah Dinurahman
Muhammad Nabil Adzikrullah
Muhammad Nur Ikhsan Kamil
Agus Ahmadyani
Denti Sofiatul Jannah
Syaqiyya Kahayasa Putri Agustina
Zhafira Khanza Humaira Agustina
Ayesha Dhafia Rumaiza Agustina
Devi Firdaus Fauzi
Rani Apriyanti
Muhammad Zaid Nur Fauzi
Muhammad Thariq Al Fauzi
Siti Hanifah Muslimah Fauzi
R. Wachyu Yulianto
Pipih Sopiah
H. Abdul Sobur
Aida Sri Yulistiani
Fawwaz Muhammad Naufal
Rizki Mulfirmansyah
Amelia Nur Fauza
Muhammad Elfarezh Mulfaza
Muhammad Alkhawarizmy
Muhammad Atharrazka
Asep
Ineu Herlinawati
Angga Muchammad Ramdhan
Hilma Nadia Yulian
Naqysha Nursyifa Anindya
Alby Muhammad Farsyad
Muhammad Baihaqqi Dzakiyulhaq
H. Erwin Solahudin
Hj. Iin Haniyati
Shafa Nur Athiyyah Zahra
Aqila Muhammad Daffa
Irwan Firmansyah
Dina Kamalasari
Muhammad Najib Al Fatih
Muhammad Abrisam Al Farizy
Nabila Humaira Azzahra
Windu Triarto
Indri Sri Wahyuni
Mochamad Rafka Putra Pratama
Mochamad Razka Dwi Putra
KH. Amir SIdik
Hj. Tini Kusmiati
Cecep Suhendi
Ratih Surtikanti
Adhara Musyaffa Bilal
Aldebaran Ujabi Bariq
Shaula Naazneen Bahia
Iwan Kurniawan
Rizkarima
Iesha Prameswari Lathofa
Iklima Pramesti Almahyra
Ikram Pramudya Al-Fatih
Gundar Kolyubi
Putri Nurlaela Hasan
Fazura Arzeti Kolyubi
Khalid Khairy Kolyubi
Fazira Ayra Kolyubi
Dadang Amir Muslim
Dedeh
Anas Maulana
Anita
Yanyan Barnansyah
Roswandi
Nura Shofa Marwiyyah
Abyan Athhar
Kashimah Adawiyah
Adzharussyukri
Agus Supriadi
Reni Kurniawati
Salmaa Felia Mentari
Salwaa Aulia Pelangi
NAMES;

    /** @var list<string> */
    private const FEMALE_MEMBERS = [
        'Nyi HJ. E. Khodijah Binti Aheh', 'Hj. Euis Djulaeha', 'Imas Nur Asiah',
        'Siti Mardiyah Ulfah', 'Asla Aliza Afkariyah', 'Aisya Syuba Mahdiyah',
        'Hj. Dian Sudiarni', 'Roza Dira Haqi', 'Aliya Dira Kholqi',
        'Hj. Lina Rosdiana', "Ummu Hawa Assa'diyah", 'Amira Shofia Mecca',
        'Ety Lestari', 'Muna ilfa Salsabila', 'Nuzha Athalla Muadz', 'Laila Rahmah',
        'Laula Rizky', 'Diva Lizetiany', 'Shireen Jenna Rumaisya',
        'Siti Zenab Nurlaila', 'Arriba Gissani', "Aabida Karimah Al Mas'udah",
        'Suci Trisiani Ajeng Purwandani', 'Tsabita Putri', 'Ami Kulsum',
        "Aqila Tamamah Sya'diyatuddaraini", 'Hj. Neneng Haerani', 'Rina Yuniawati',
        'Denti Sofiatul Jannah', 'Syaqiyya Kahayasa Putri Agustina',
        'Zhafira Khanza Humaira Agustina', 'Ayesha Dhafia Rumaiza Agustina',
        'Rani Apriyanti', 'Siti Hanifah Muslimah Fauzi', 'Pipih Sopiah',
        'Aida Sri Yulistiani', 'Amelia Nur Fauza', 'Ineu Herlinawati',
        'Hilma Nadia Yulian', 'Naqysha Nursyifa Anindya', 'Hj. Iin Haniyati',
        'Shafa Nur Athiyyah Zahra', 'Dina Kamalasari', 'Nabila Humaira Azzahra',
        'Indri Sri Wahyuni', 'Hj. Tini Kusmiati', 'Ratih Surtikanti',
        'Shaula Naazneen Bahia', 'Rizkarima', 'Iesha Prameswari Lathofa',
        'Iklima Pramesti Almahyra', 'Putri Nurlaela Hasan', 'Fazura Arzeti Kolyubi',
        'Fazira Ayra Kolyubi', 'Dedeh', 'Anita', 'Nura Shofa Marwiyyah',
        'Kashimah Adawiyah', 'Reni Kurniawati', 'Salmaa Felia Mentari',
        'Salwaa Aulia Pelangi',
    ];

    /**
     * Each entry is [father, mother, children].
     *
     * @var list<array{string, string, list<string>}>
     */
    private const FAMILIES = [
        ['KH. Fahrurozi Bin Ama KH Sididiq', 'Nyi HJ. E. Khodijah Binti Aheh', [
            'KH. Didin Muhyiddin', 'KH. Uwesulkorni', 'Pipih Sopiah',
            'KH. Amir SIdik', 'Dadang Amir Muslim',
        ]],
        ['KH. Didin Muhyiddin', 'Hj. Euis Djulaeha', [
            'Imas Nur Asiah', 'H. Ahmad Farid', 'Lutfi Muharram',
            'Muhammad Firmansyah', 'Ami Kulsum',
        ]],
        ['Yayan Supriatna', 'Imas Nur Asiah', [
            'Siti Mardiyah Ulfah', 'Nura Shofa Marwiyyah', 'Kashimah Adawiyah',
            'Adzharussyukri',
        ]],
        ['Mochammad Rifa', 'Siti Mardiyah Ulfah', [
            'Asla Aliza Afkariyah', 'Aisya Syuba Mahdiyah',
        ]],
        ['Roswandi', 'Nura Shofa Marwiyyah', ['Abyan Athhar']],
        ['H. Rizal Yusup Ramdhan', 'Hj. Dian Sudiarni', ['Roza Dira Haqi', 'Aliya Dira Kholqi']],
        ['Risman Rismansyah', 'Roza Dira Haqi', ['Muhammad Ryuma Syahdira']],
        ['H. Ahmad Farid', 'Hj. Lina Rosdiana', ["Ummu Hawa Assa'diyah"]],
        ['Mohamad Musa', "Ummu Hawa Assa'diyah", ['Amira Shofia Mecca']],
        ['Lutfi Muharram', 'Ety Lestari', ['Adam Ibrahim Alfath', 'Laila Rahmah', 'Laula Rizky']],
        ['Adam Ibrahim Alfath', 'Muna ilfa Salsabila', ['Aydan Muhammad Mulk', 'Nuzha Athalla Muadz']],
        ['Alif Rahmatullah', 'Diva Lizetiany', ['Shireen Jenna Rumaisya']],
        ['H. Rosmana Kurniadi', 'Siti Zenab Nurlaila', ['Arriba Gissani']],
        ['Ibnu Fikri Pamungkas', 'Arriba Gissani', ["Aabida Karimah Al Mas'udah"]],
        ['Muhammad Firmansyah', 'Suci Trisiani Ajeng Purwandani', ['Tsabita Putri']],
        ['Asep Rijwan Suhendi', 'Ami Kulsum', ['Abdan Syakuuro', "Aqila Tamamah Sya'diyatuddaraini"]],
        ['KH. Uwesulkorni', 'Hj. Neneng Haerani', ['Rina Yuniawati', 'Denti Sofiatul Jannah', 'Devi Firdaus Fauzi']],
        ['Rachman', 'Rina Yuniawati', [
            'Muhammad Fadhillah Dinurahman', 'Muhammad Nabil Adzikrullah', 'Muhammad Nur Ikhsan Kamil',
        ]],
        ['Agus Ahmadyani', 'Denti Sofiatul Jannah', [
            'Syaqiyya Kahayasa Putri Agustina', 'Zhafira Khanza Humaira Agustina',
            'Ayesha Dhafia Rumaiza Agustina',
        ]],
        ['Devi Firdaus Fauzi', 'Rani Apriyanti', [
            'Muhammad Zaid Nur Fauzi', 'Muhammad Thariq Al Fauzi', 'Siti Hanifah Muslimah Fauzi',
        ]],
        ['R. Wachyu Yulianto', 'Pipih Sopiah', [
            'Aida Sri Yulistiani', 'Ineu Herlinawati', 'H. Erwin Solahudin',
            'Irwan Firmansyah', 'Indri Sri Wahyuni',
        ]],
        ['H. Abdul Sobur', 'Aida Sri Yulistiani', ['Fawwaz Muhammad Naufal', 'Amelia Nur Fauza']],
        ['Rizki Mulfirmansyah', 'Amelia Nur Fauza', [
            'Muhammad Elfarezh Mulfaza', 'Muhammad Alkhawarizmy', 'Muhammad Atharrazka',
        ]],
        ['Asep', 'Ineu Herlinawati', ['Hilma Nadia Yulian']],
        ['Angga Muchammad Ramdhan', 'Hilma Nadia Yulian', [
            'Naqysha Nursyifa Anindya', 'Alby Muhammad Farsyad', 'Muhammad Baihaqqi Dzakiyulhaq',
        ]],
        ['H. Erwin Solahudin', 'Hj. Iin Haniyati', ['Shafa Nur Athiyyah Zahra', 'Aqila Muhammad Daffa']],
        ['Irwan Firmansyah', 'Dina Kamalasari', [
            'Muhammad Najib Al Fatih', 'Muhammad Abrisam Al Farizy', 'Nabila Humaira Azzahra',
        ]],
        ['Windu Triarto', 'Indri Sri Wahyuni', ['Mochamad Rafka Putra Pratama', 'Mochamad Razka Dwi Putra']],
        ['KH. Amir SIdik', 'Hj. Tini Kusmiati', ['Ratih Surtikanti', 'Rizkarima', 'Gundar Kolyubi']],
        ['Cecep Suhendi', 'Ratih Surtikanti', ['Adhara Musyaffa Bilal', 'Aldebaran Ujabi Bariq', 'Shaula Naazneen Bahia']],
        ['Iwan Kurniawan', 'Rizkarima', ['Iesha Prameswari Lathofa', 'Iklima Pramesti Almahyra', 'Ikram Pramudya Al-Fatih']],
        ['Gundar Kolyubi', 'Putri Nurlaela Hasan', ['Fazura Arzeti Kolyubi', 'Khalid Khairy Kolyubi', 'Fazira Ayra Kolyubi']],
        ['Dadang Amir Muslim', 'Dedeh', ['Anas Maulana', 'Yanyan Barnansyah']],
        ['Anas Maulana', 'Anita', []],
        ['Agus Supriadi', 'Reni Kurniawati', ['Salmaa Felia Mentari', 'Salwaa Aulia Pelangi']],
    ];

    public function run(): void
    {
        $ownerEmail = config('family-tree.seeders.bani_fahruroji_owner_email');

        if (! is_string($ownerEmail) || trim($ownerEmail) === '') {
            throw new RuntimeException(
                'Set BANI_FAHRUROJI_OWNER_EMAIL to an existing verified user before running BaniFahrurojiSeeder.'
            );
        }

        $owner = User::query()
            ->where('email', trim($ownerEmail))
            ->where('status', 'active')
            ->first();

        if ($owner === null) {
            throw new RuntimeException('The configured Bani Fahruroji owner must be an existing active user.');
        }

        DB::transaction(function () use ($owner): void {
            $family = Family::withTrashed()->firstOrNew(['uuid' => self::FAMILY_UUID]);
            $family->fill([
                'name' => 'Bani Fahruroji',
                'slug' => 'bani-fahruroji',
                'description' => 'Silsilah keluarga Bani Fahruroji, diimpor dari diagram draw.io.',
                'created_by' => $owner->id,
            ]);
            $family->save();
            $family->restore();

            $role = FamilyUserRole::withTrashed()->firstOrNew([
                'family_id' => $family->id,
                'user_id' => $owner->id,
            ]);
            $role->fill([
                'uuid' => $role->uuid ?: $this->uuid('owner:'.$owner->email),
                'role' => FamilyUserRole::ROLE_OWNER,
            ]);
            $role->save();
            $role->restore();

            $members = [];
            foreach ($this->memberNames() as $name) {
                $member = FamilyMember::withTrashed()->firstOrNew([
                    'uuid' => $this->uuid('member:'.$name),
                ]);
                $member->fill([
                    'family_id' => $family->id,
                    'family_branch_id' => null,
                    'full_name' => $name,
                    'gender' => in_array($name, self::FEMALE_MEMBERS, true) ? 'female' : 'male',
                    'religion' => 'islam',
                    'is_alive' => true,
                    'biography' => 'Data awal diimpor dari diagram keluarga Fahruroji.',
                    'created_by' => $owner->id,
                ]);
                $member->save();
                $member->restore();
                $members[$name] = $member;
            }

            foreach (self::BRANCH_ROOTS as $branchRoot) {
                $branch = FamilyBranch::withTrashed()->firstOrNew([
                    'uuid' => $this->uuid('branch:'.$branchRoot),
                ]);
                $branch->fill([
                    'family_id' => $family->id,
                    'name' => 'Cabang '.$branchRoot,
                    'description' => "Keturunan {$branchRoot} beserta pasangan.",
                ]);
                $branch->save();
                $branch->restore();

                foreach ($this->branchMemberNames($branchRoot) as $memberName) {
                    $members[$memberName]->update(['family_branch_id' => $branch->id]);
                }
            }

            foreach (self::FAMILIES as [$father, $mother, $children]) {
                $this->relationship($family, $members[$father], $members[$mother], MemberRelationship::TYPE_HUSBAND);
                $this->relationship($family, $members[$mother], $members[$father], MemberRelationship::TYPE_WIFE);

                foreach ($children as $child) {
                    $this->relationship($family, $members[$father], $members[$child], MemberRelationship::TYPE_FATHER);
                    $this->relationship($family, $members[$mother], $members[$child], MemberRelationship::TYPE_MOTHER);
                }
            }
        });
    }

    /**
     * @return list<string>
     */
    private function memberNames(): array
    {
        return array_values(array_filter(array_map('trim', explode("\n", self::MEMBERS))));
    }

    /**
     * @return list<string>
     */
    private function branchMemberNames(string $branchRoot): array
    {
        $members = [$branchRoot => true];
        $queue = [$branchRoot];

        while ($queue !== []) {
            $current = array_shift($queue);

            foreach (self::FAMILIES as [$father, $mother, $children]) {
                if ($father !== $current && $mother !== $current) {
                    continue;
                }

                foreach ([$father, $mother, ...$children] as $relative) {
                    if (isset($members[$relative])) {
                        continue;
                    }

                    $members[$relative] = true;
                    $queue[] = $relative;
                }
            }
        }

        return array_keys($members);
    }

    private function relationship(
        Family $family,
        FamilyMember $source,
        FamilyMember $target,
        string $type,
    ): void {
        $relationship = MemberRelationship::withTrashed()->firstOrNew([
            'uuid' => $this->uuid("relationship:{$source->uuid}:{$target->uuid}:{$type}"),
        ]);
        $relationship->fill([
            'family_id' => $family->id,
            'source_member_id' => $source->id,
            'target_member_id' => $target->id,
            'relationship_type' => $type,
            'notes' => 'Relasi dasar diimpor dari diagram keluarga Fahruroji.',
        ]);
        $relationship->save();
        $relationship->restore();
    }

    private function uuid(string $value): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_URL, 'family-tree:bani-fahruroji:'.$value)->toString();
    }
}
