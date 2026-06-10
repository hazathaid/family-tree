# business-rules.md

Project: Family Tree Platform Indonesia

Version: 1.0

---

# Purpose

Dokumen ini mendefinisikan seluruh aturan bisnis yang wajib dipatuhi oleh:

* Backend
* Mobile App
* Relationship Engine
* Tree Generator
* Admin Panel

Jika terjadi konflik antara implementasi dan dokumen ini, maka dokumen ini menjadi sumber kebenaran.

---

# Core Principles

1. Data keluarga harus konsisten.
2. Tidak boleh ada relasi biologis yang mustahil.
3. Tidak boleh ada graph keluarga yang siklik.
4. Semua hubungan turunan dihitung oleh Relationship Engine.
5. Data harus tetap valid walaupun keluarga memiliki lebih dari 100.000 anggota.

---

# Family Ownership Rules

## BR-001

Satu keluarga wajib memiliki minimal satu Owner.

---

## BR-002

Owner dapat:

* Mengelola keluarga
* Menghapus anggota
* Mengelola admin keluarga

---

## BR-003

Minimal harus ada satu Owner aktif.

Owner terakhir tidak boleh dihapus.

---

# Member Rules

## BR-101

Nama depan wajib diisi.

---

## BR-102

Tanggal lahir boleh kosong.

Karena data leluhur lama sering tidak lengkap.

---

## BR-103

Jenis kelamin wajib dipilih.

Nilai:

```text
male
female
unknown
```

---

## BR-104

Status hidup wajib ada.

Nilai:

```text
alive
deceased
unknown
```

---

## BR-105

Jika status = deceased

Maka:

```text
death_date
```

boleh diisi.

---

## BR-106

Jika status = alive

Maka:

```text
death_date
```

harus kosong.

---

# Parent Rules

## BR-201

Satu anggota hanya boleh memiliki:

```text
1 ayah biologis
```

---

## BR-202

Satu anggota hanya boleh memiliki:

```text
1 ibu biologis
```

---

## BR-203

Tidak boleh memiliki:

```text
2 ayah biologis
```

---

## BR-204

Tidak boleh memiliki:

```text
2 ibu biologis
```

---

## BR-205

Ayah harus memiliki gender:

```text
male
```

---

## BR-206

Ibu harus memiliki gender:

```text
female
```

---

# Marriage Rules

## BR-301

Hubungan suami hanya boleh:

```text
male
```

---

## BR-302

Hubungan istri hanya boleh:

```text
female
```

---

## BR-303

Hubungan pasangan harus dua arah.

Contoh:

```text
Ahmad -> husband -> Siti

Siti -> wife -> Ahmad
```

harus dibuat otomatis.

---

## BR-304

Pasangan tidak boleh menunjuk dirinya sendiri.

---

# Age Validation Rules

## BR-401

Anak tidak boleh lahir sebelum orang tua.

---

## BR-402

Orang tua harus lebih tua dari anak.

---

## BR-403

Minimal selisih usia:

```text
10 tahun
```

antara orang tua dan anak.

---

## BR-404

Jika tanggal lahir tidak tersedia

Validasi usia dilewati.

---

# Self Reference Rules

## BR-501

Seseorang tidak boleh menjadi ayah dirinya sendiri.

---

## BR-502

Seseorang tidak boleh menjadi ibu dirinya sendiri.

---

## BR-503

Seseorang tidak boleh menjadi anak dirinya sendiri.

---

## BR-504

Seseorang tidak boleh menjadi pasangan dirinya sendiri.

---

# Graph Cycle Rules

## BR-601

Graph keluarga tidak boleh memiliki siklus.

Contoh terlarang:

```text
Ahmad

↓

Budi

↓

Joko

↓

Ahmad
```

---

## BR-602

Sebelum menyimpan relasi baru sistem wajib melakukan:

```text
Cycle Detection
```

---

## BR-603

Jika siklus ditemukan:

```text
Validation Failed
```

---

# Relationship Engine Rules

## BR-701

Database hanya boleh menyimpan:

```text
father
mother
child
husband
wife
```

---

## BR-702

Tidak boleh menyimpan:

```text
pakde
bude
om
tante
sepupu
keponakan
mertua
menantu
buyut
cicit
```

---

## BR-703

Semua relasi turunan wajib dihitung.

---

## BR-704

Relationship Engine adalah source of truth.

---

# Duplicate Member Rules

## BR-801

Sistem harus mendeteksi kemungkinan duplikasi.

Kriteria:

```text
Nama sama
Tanggal lahir sama
Nama orang tua sama
```

---

## BR-802

Jika duplikasi ditemukan:

Tampilkan peringatan.

---

## BR-803

Admin dapat melakukan:

```text
Merge Member
```

---

# Merge Member Rules

## BR-901

Merge hanya boleh dilakukan admin keluarga.

---

## BR-902

Saat merge:

Semua relasi harus dipindahkan.

---

## BR-903

Semua foto harus dipindahkan.

---

## BR-904

Semua artikel harus dipindahkan.

---

## BR-905

Member lama harus diarsipkan.

---

# Deceased Member Rules

## BR-1001

Anggota meninggal tidak boleh login.

---

## BR-1002

Anggota meninggal tetap muncul di pohon keluarga.

---

## BR-1003

Anggota meninggal harus memiliki:

```text
† marker
```

---

## BR-1004

Data anggota meninggal tidak boleh dihapus sembarangan.

---

# Family Tree Rules

## BR-1101

Tree wajib memiliki root node.

---

## BR-1102

Root default:

```text
Current User
```

---

## BR-1103

Tree wajib mendukung:

```text
Ancestor
Descendant
Full
```

---

## BR-1104

Tree wajib dapat di-generate dari anggota mana pun.

---

# Article Rules

## BR-1201

Artikel harus memiliki judul.

---

## BR-1202

Artikel harus memiliki penulis.

---

## BR-1203

Draft tidak tampil publik.

---

## BR-1204

Artikel yang dipublikasikan muncul di Timeline.

---

# Photo Rules

## BR-1301

Hanya format berikut yang diperbolehkan:

```text
jpg
jpeg
png
webp
```

---

## BR-1302

Ukuran maksimal:

```text
10 MB
```

---

## BR-1303

Thumbnail harus dibuat otomatis.

---

## BR-1304

Foto dapat menandai anggota keluarga.

---

# Event Rules

## BR-1401

Acara wajib memiliki tanggal.

---

## BR-1402

Acara wajib memiliki pembuat.

---

## BR-1403

RSVP hanya boleh:

```text
yes
no
maybe
```

---

# Notification Rules

## BR-1501

Notifikasi harus dibuat ketika:

* Anggota baru ditambahkan
* Artikel dipublikasikan
* Foto diunggah
* Acara dibuat

---

# Privacy Rules

## BR-1601

Setiap keluarga dapat menentukan:

```text
Public
Private
Invite Only
```

---

## BR-1602

Private Family tidak dapat dilihat publik.

---

## BR-1603

Invite Only hanya dapat diakses anggota yang diundang.

---

# Audit Rules

## BR-1701

Perubahan berikut wajib dicatat:

* Create Member
* Update Member
* Delete Member
* Merge Member
* Relationship Change

---

## BR-1702

Audit log tidak boleh dihapus oleh user biasa.

---

# Cache Rules

## BR-1801

Relationship Cache berlaku:

```text
24 jam
```

---

## BR-1802

Tree Cache berlaku:

```text
24 jam
```

---

## BR-1803

Jika relasi berubah:

Cache terkait wajib dibersihkan.

---

# Search Rules

## BR-1901

Pencarian harus mendukung:

* Nama lengkap
* Nama panggilan

---

## BR-1902

Pencarian tidak membedakan huruf besar dan kecil.

---

# Security Rules

## BR-2001

Semua endpoint wajib diautentikasi kecuali halaman publik.

---

## BR-2002

Setiap aksi penting harus melalui authorization policy.

---

## BR-2003

Rate limiting wajib aktif.

---

# Data Integrity Rules

## BR-2101

Tidak boleh ada orphan relationship.

---

## BR-2102

Tidak boleh ada relationship yang menunjuk anggota yang sudah dihapus.

---

## BR-2103

Semua foreign key wajib valid.

---

# Critical Rules

1. Relationship Engine adalah sumber kebenaran relasi keluarga.
2. Derived relationships tidak boleh disimpan di database.
3. Graph keluarga tidak boleh memiliki siklus.
4. Satu anggota maksimal memiliki satu ayah biologis dan satu ibu biologis.
5. Tree Generator wajib menggunakan graph traversal.
6. Semua perubahan relasi harus memicu cache invalidation.
7. Semua fitur harus menjaga integritas data keluarga.
