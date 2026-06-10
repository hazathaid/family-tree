# tree-generation-engine.md

Version: 1.0

Project: Family Tree Platform Indonesia

---

# Overview

Tree Generation Engine bertanggung jawab untuk:

* Menghasilkan bagan silsilah keluarga.
* Menampilkan hubungan antar anggota.
* Menentukan posisi node.
* Menampilkan foto dan informasi anggota.
* Mengekspor pohon keluarga ke PNG dan PDF.
* Mendukung pohon keluarga besar hingga puluhan generasi.

---

# Goals

Sistem harus mampu:

* Generate pohon keluarga dari siapa pun.
* Generate ancestor tree.
* Generate descendant tree.
* Generate full family tree.
* Render hingga 100.000 anggota.
* Export ke PNG.
* Export ke PDF.
* Mendukung web dan mobile.

---

# Source Data

Data berasal dari:

```text id="8g5lpi"
family_members
member_relationships
```

Tidak boleh menggunakan tabel lain sebagai sumber utama.

---

# Tree Engine Architecture

```text id="7f2zdl"
Database

↓

Relationship Service

↓

Graph Builder

↓

Tree Builder

↓

Layout Engine

↓

Renderer

↓

Export Engine
```

---

# Graph Builder

Tugas:

Mengubah relasi menjadi graph.

Node:

```text id="q7h2x6"
family_member
```

Edge:

```text id="q7d02z"
father
mother
child
husband
wife
```

Contoh:

```text id="y29ly7"
Ahmad

father

Budi
```

Graph:

```text id="hhq1m9"
Ahmad → Budi
```

---

# Tree Modes

## 1. Ancestor Tree

Menampilkan leluhur.

Contoh:

```text id="10j9kh"
Buyut

↓

Kakek

↓

Ayah

↓

Saya
```

Parameter:

```text id="7p44ta"
depth
```

Default:

```text id="i8lqjh"
5 generasi
```

---

## 2. Descendant Tree

Menampilkan keturunan.

Contoh:

```text id="n0h56z"
Saya

↓

Anak

↓

Cucu

↓

Cicit
```

---

## 3. Full Tree

Menampilkan seluruh keluarga yang terhubung.

Termasuk:

* Orang tua
* Saudara
* Pasangan
* Anak
* Sepupu

---

# Generation Levels

Root User:

```text id="g8h9zj"
Level 0
```

Parents:

```text id="b2u7s0"
Level -1
```

Grandparents:

```text id="v8b60t"
Level -2
```

Children:

```text id="48i6s6"
Level +1
```

Grandchildren:

```text id="rb2v2x"
Level +2
```

---

# Root Selection

User dapat memilih:

```text id="7x8s7q"
Saya
Ayah
Kakek
Anggota tertentu
```

Root akan menjadi pusat pohon.

---

# Layout Types

## Vertical Layout

Default.

```text id="driv7m"
Buyut

↓

Kakek

↓

Ayah

↓

Saya

↓

Anak
```

---

## Horizontal Layout

```text id="zjlwmg"
Buyut → Kakek → Ayah → Saya → Anak
```

---

## Radial Layout

Pusat:

```text id="znwxkh"
Saya
```

Relasi menyebar melingkar.

Cocok untuk:

* Pohon besar
* Tampilan modern

---

## Compact Layout

Untuk mobile.

Fokus pada:

* 3 generasi
* Scroll horizontal

---

# Node Design

Setiap node menampilkan:

```text id="w0qlx0"
Foto
Nama Lengkap
Nama Panggilan
Tahun Lahir
Status
```

Contoh:

```text id="ht7d4v"
[Foto]

Ahmad Santoso

(Budi)

1980
```

---

# Living Status

Jika hidup:

```text id="mnz8wd"
Badge Hijau
```

Jika meninggal:

```text id="hwdm6m"
Badge Abu-Abu
Icon Memorial
```

---

# Memorial Marker

Untuk anggota meninggal:

```text id="fwhh1v"
†
```

Contoh:

```text id="b8z31o"
† Ahmad Santoso
```

---

# Root Highlight

Root user harus ditonjolkan.

Contoh:

```text id="hhpp55"
Border lebih tebal
Background berbeda
```

---

# Relationship Labels

Antar node harus menampilkan:

```text id="v1r67g"
Ayah
Ibu
Suami
Istri
```

Opsional:

```text id="t7e6ic"
Pakde
Sepupu
Menantu
```

Dihitung dari Relationship Engine.

---

# Node Click Action

Saat node diklik:

Tampilkan:

```text id="2m2l84"
Foto
Biografi
Pekerjaan
Pendidikan
Hubungan dengan saya
Album foto
```

---

# Expand Collapse

Node harus bisa:

```text id="8nmjql"
Expand
Collapse
```

Untuk keluarga besar.

---

# Lazy Loading

Jika anggota > 1000

Gunakan:

```text id="g1q2r8"
Lazy Loading
```

Agar browser tetap cepat.

---

# Search Integration

User dapat mencari:

```text id="g5ec6v"
Nama
Nama Panggilan
```

Hasil:

Node langsung difokuskan.

---

# Tree Builder Algorithm

Traversal:

```text id="n26tgu"
Breadth First Search
```

Karena:

* Cepat
* Stabil
* Cocok untuk graph keluarga

---

# Ancestor Traversal

Pseudo:

```text id="f49g4y"
root

father

grandfather

great-grandfather
```

Berhenti jika:

```text id="r9l1s2"
depth tercapai
```

---

# Descendant Traversal

Pseudo:

```text id="3i6h95"
root

children

grandchildren

great-grandchildren
```

---

# Full Tree Traversal

Traversal:

```text id="vhwjlwm"
BFS Multi Direction
```

Mencakup:

```text id="57e0az"
parent
child
spouse
sibling
```

---

# Export Engine

## PNG Export

Endpoint:

```text id="msjwe9"
/tree/export/png
```

Target:

```text id="v5s7n9"
300 DPI
```

Ukuran:

```text id="9h2w5e"
A4
A3
A2
```

---

## PDF Export

Endpoint:

```text id="5ttmnw"
/tree/export/pdf
```

Isi:

* Judul keluarga
* Tanggal generate
* Pohon keluarga
* Statistik keluarga

---

# Tree Statistics

Tambahkan:

```text id="v32yzc"
Jumlah Anggota
Jumlah Generasi
Anggota Hidup
Anggota Meninggal
```

---

# Export Header

Contoh:

```text id="7z1jdd"
Bagan Silsilah
Keluarga Besar Ahmad
Dibuat pada:
2026-06-10
```

---

# Export Footer

Contoh:

```text id="yl59h8"
Generated by Family Tree Platform Indonesia
```

---

# Performance Targets

Tree < 1.000 anggota

```text id="2l8c0z"
< 1 detik
```

Tree < 10.000 anggota

```text id="j7w0gz"
< 3 detik
```

Tree < 100.000 anggota

```text id="ch2smk"
< 5 detik
```

---

# Tree Cache

Table:

```text id="fyg6fk"
member_tree_cache
```

Disimpan:

```json id="44pf4v"
{
  "tree": {}
}
```

TTL:

```text id="x1nl8g"
24 jam
```

---

# Mobile Rendering Rules

Default:

```text id="w0t1wj"
Compact Layout
```

Max:

```text id="1qnl2e"
3 generasi per layar
```

Fitur:

```text id="lckcyk"
Pinch Zoom
Pan
Expand
Collapse
```

---

# Future Features

## Timeline Overlay

Node dapat menunjukkan:

```text id="j5cp8n"
Ulang Tahun
Hari Wafat
Acara Keluarga
```

---

## AI Story Overlay

Klik anggota:

```text id="1ktwz5"
Generate Riwayat Hidup
```

Menggunakan AI.

---

## Family Heatmap

Visualisasi:

```text id="1sgxgz"
Persebaran keluarga per kota
```

---

# Critical Rules

1. Tree tidak boleh langsung membaca database.

2. Semua data harus melalui Relationship Engine.

3. Semua hubungan keluarga harus dihitung secara dinamis.

4. Tree Generator tidak boleh menyimpan relasi turunan.

5. Root User harus selalu menjadi pusat visualisasi.

6. Sistem wajib mendukung keluarga besar lebih dari 100.000 anggota.

7. Semua mode export harus identik dengan tampilan web.

```
```
