<?php

/*
|--------------------------------------------------------------------------
| Data Tautan Lintas-Keluarga (Cross-Family Link)
|--------------------------------------------------------------------------
|
| Isi data asli keluarga KEDUA (keluarga yang akan ditautkan) dan titik
| pernikahan lintas-keluarga. Keluarga pertama (partner) diasumsikan sudah
| ada di database — cukup sebutkan slug + nama anggotanya.
|
| Setelah diisi, jalankan:
|   php artisan db:seed --class=CrossFamilyLinkSeeder
|
| Catatan:
| - Semua tanggal format Y-m-d (contoh 1970-01-31).
| - Kolom bertanda [opsional] boleh dikosongkan.
| - Baris dengan nilai kosong ("") akan dilewati oleh seeder.
|
*/

return [

    // ---------------------------------------------------------------------
    // KELUARGA KEDUA
    // ---------------------------------------------------------------------
    'family' => [
        'name' => 'Hidayat',          // contoh: Keluarga Besar Wijaya
        'slug' => 'hidayat-family',          // huruf kecil & strip, unik. contoh: keluarga-besar-wijaya
        'origin_city' => 'Sukabumi',   // contoh: Surabaya
        'description' => 'Keluarga Hidayat',   // [opsional]
    ],

    // ---------------------------------------------------------------------
    // OWNER / ADMIN KELUARGA KEDUA
    // Seeder membuat user ini bila email belum terdaftar.
    // ---------------------------------------------------------------------
    'owner' => [
        'name' => 'Owner Keluarga Hidayat',
        'email' => 'owner-hidayat@mail.com',
        'phone' => '081563486962',
        'password' => '',      // [opsional] default: password
    ],

    // ---------------------------------------------------------------------
    // ANGGOTA KELUARGA KEDUA
    //
    // key       : pengenal unik, tanpa spasi (dipakai di 'relationships' & 'link')
    // full_name : nama lengkap apa adanya
    // nickname  : [opsional]
    // gender    : male | female
    // is_alive  : true | false
    // death_date/death_place: hanya bila is_alive = false
    // ---------------------------------------------------------------------
    'members' => [
        [
            'key' => 'kepala_keluarga_hidayat',
            'full_name' => 'Hidayat',
            'nickname' => 'hidayat',
            'gender' => 'male',
            'birth_date' => '1950-03-15',
            'birth_place' => '',
            'is_alive' => false,
            'death_date' => '2010-05-20',
            'death_place' => 'Sukabumi',
            'biography' => '',
        ],

        [
            'key' => 'pasangan_kepala_keluarga_hidayat',
            'full_name' => 'Kartika',
            'nickname' => 'kartika',
            'gender' => 'female',
            'birth_date' => '',
            'birth_place' => 'Sukabumi',
            'is_alive' => false,
            'death_date' => '',
            'death_place' => 'Sukabumi',
            'biography' => '',
        ],
        [
            'key' => 'kakak_ipar_pertama',
            'full_name' => 'Rina',
            'nickname' => 'rina',
            'gender' => 'female',
            'birth_date' => '1983-04-27',
            'birth_place' => 'Sukabumi',
            'is_alive' => true,
            'death_date' => null,
            'death_place' => null,
            'biography' => '',
        ],
        [
            'key' => 'kak_ipar_kedua',
            'full_name' => 'Rini',
            'nickname' => 'rini',
            'gender' => 'female',
            'birth_date' => '1983-04-27',
            'birth_place' => 'Sukabumi',
            'is_alive' => true,
            'death_date' => null,
            'death_place' => null,
            'biography' => '',
        ],
        [
            'key' => 'kak_ipar_ketiga',
            'full_name' => 'Eris Riswanto',
            'nickname' => 'eris',
            'gender' => 'male',
            'birth_date' => '1985-04-27',
            'birth_place' => 'Sukabumi',
            'is_alive' => true,
            'death_date' => null,
            'death_place' => null,
            'biography' => '',
        ],
        // Anggota keluarga kedua yang menikah dengan anggota keluarga partner.
        [
            'key' => 'rani_apriyanti',
            'full_name' => 'Rani Apriyanti',
            'nickname' => 'rani',
            'gender' => 'female',
            'birth_date' => '',
            'birth_place' => 'Sukabumi',
            'is_alive' => true,
            'death_date' => null,
            'death_place' => null,
            'biography' => '',
        ],
        // Tambahkan anggota lain (saudara, anak, dst) sesuai kebutuhan.
    ],

    // ---------------------------------------------------------------------
    // RELASI DASAR DI DALAM KELUARGA KEDUA
    //
    // type   : father | mother | husband | wife
    // source : orang tua (untuk father/mother) atau suami (husband)
    // target : anak (untuk father/mother) atau istri (wife)
    // ---------------------------------------------------------------------
    'relationships' => [
        // ['source' => 'kepala_keluarga', 'target' => 'calon_menantu', 'type' => 'father'],
        // ['source' => 'pasangan_kepala', 'target' => 'calon_menantu', 'type' => 'mother'],
        // ['source' => 'kepala_keluarga', 'target' => 'pasangan_kepala', 'type' => 'husband'],
    ],

    // ---------------------------------------------------------------------
    // TITIK PERNIKAHAN LINTAS-KELUARGA
    //
    // family_member_key : key anggota keluarga KEDUA yang menikah
    // partner_member    : anggota keluarga PARTNER (yang sudah ada)
    //   - family_slug   : slug keluarga partner, contoh: keluarga-demo-santoso
    //   - full_name     : nama lengkap anggota partner (harus cocok persis)
    // status            : active (langsung tampil) | pending (perlu approve)
    // children_home_family : second | partner  (anak tinggal di keluarga mana)
    // ---------------------------------------------------------------------
    'link' => [
        'family_member_key' => 'rani_apriyanti',
        'partner_member' => [
            'family_slug' => 'bani-fahruroji',
            'full_name' => 'Devi Firdaus Fauzi',
        ],
        'status' => 'active',
        'children_home_family' => 'partner',
        'expires_at' => null, // contoh: '2027-12-31 23:59:59' atau null
        'requested_by_email' => '',              // [opsional] default: owner keluarga kedua
        'approved_by_family_email' => '',        // [opsional] default: owner keluarga kedua
        'approved_by_linked_family_email' => '', // [opsional] default: owner keluarga partner
    ],

];
