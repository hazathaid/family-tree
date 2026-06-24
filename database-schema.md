# database-schema.md

Version: 1.0

Project: Family Tree Platform Indonesia

Database: MySQL 8

---

# Database Design Principles

Sistem menggunakan pendekatan:

* Family Graph Database Model (di atas MySQL)
* Node = Anggota Keluarga
* Edge = Hubungan Keluarga

Keuntungan:

* Tidak terbatas jumlah generasi
* Dapat menghitung hubungan otomatis
* Mudah generate family tree
* Mendukung keluarga besar

---

# ERD Overview

```text
users
families

family_members
member_relationships
member_relationship_cache

member_photos
member_documents

articles
article_categories
article_comments
article_likes

events
event_attendees

notifications

activity_logs
audit_logs
```

---

# users

Menyimpan akun login.

| Field             | Type                   |
| ----------------- | ---------------------- |
| id                | BIGINT PK              |
| uuid              | CHAR(36)               |
| name              | VARCHAR(255)           |
| email             | VARCHAR(255) UNIQUE    |
| phone             | VARCHAR(30)            |
| password          | VARCHAR(255)           |
| avatar            | VARCHAR(255)           |
| email_verified_at | TIMESTAMP NULL         |
| last_login_at     | TIMESTAMP NULL         |
| status            | ENUM(active,suspended) |
| created_at        | TIMESTAMP              |
| updated_at        | TIMESTAMP              |

Indexes:

```sql
email
uuid
status
```

---

# families

Data keluarga besar.

| Field       | Type            |
| ----------- | --------------- |
| id          | BIGINT PK       |
| uuid        | CHAR(36)        |
| name        | VARCHAR(255)    |
| slug        | VARCHAR(255)    |
| description | TEXT            |
| origin_city | VARCHAR(255)    |
| logo        | VARCHAR(255)    |
| cover_image | VARCHAR(255)    |
| created_by  | BIGINT FK users |
| created_at  | TIMESTAMP       |
| updated_at  | TIMESTAMP       |

---

# family_user_roles

Role user pada keluarga.

| Field     | Type                     |
| --------- | ------------------------ |
| id        | BIGINT PK                |
| family_id | BIGINT                   |
| user_id   | BIGINT                   |
| role      | ENUM(member,admin,owner) |

---

# family_members

Tabel paling penting.

Setiap orang dalam silsilah disimpan di sini.

| Field     | Type      |
| --------- | --------- |
| id        | BIGINT PK |
| uuid      | CHAR(36) UNIQUE |
| family_id | BIGINT    |
| uuid      | CHAR(36)  |

| first_name | VARCHAR(100) |
| middle_name | VARCHAR(100) |
| last_name | VARCHAR(100) |

| nickname | VARCHAR(100) |
| title_prefix | VARCHAR(50) |
| title_suffix | VARCHAR(50) |

| gender | ENUM(male,female) |

| birth_place | VARCHAR(255) |
| birth_date | DATE |

| death_place | VARCHAR(255) |
| death_date | DATE |

| is_alive | BOOLEAN |

| occupation | VARCHAR(255) |
| education | VARCHAR(255) |

| phone | VARCHAR(50) |
| email | VARCHAR(255) |

| biography | LONGTEXT |

| profile_photo | VARCHAR(255) |

| created_by | BIGINT |
| updated_by | BIGINT |

| created_at | TIMESTAMP |
| updated_at | TIMESTAMP |

Indexes:

```sql
family_id
nickname
first_name
last_name
birth_date
is_alive
```

---

# member_relationships

Jantung sistem silsilah.

Model Graph.

| Field     | Type      |
| --------- | --------- |
| id        | BIGINT PK |
| family_id | BIGINT    |

| source_member_id | BIGINT |
| target_member_id | BIGINT |

| relationship_type | ENUM |

| start_date | DATE NULL |
| end_date | DATE NULL |

| notes | TEXT |

| created_at | TIMESTAMP |
| updated_at | TIMESTAMP |
| deleted_at | TIMESTAMP NULL |

relationship_type:

```text
father
mother
child
husband
wife
```

Relasi turunan seperti pakde, bude, om, tante, sepupu, keponakan, menantu, mertua, buyut, dan cicit tidak disimpan di database. Nilai tersebut wajib dihitung oleh Relationship Engine dari graph relasi dasar.

Index:

```sql
uuid
source_member_id
target_member_id
relationship_type
family_id
family_id, source_member_id, target_member_id, relationship_type
```

---

# member_relationship_cache

Cache hasil pencarian Relationship Engine.

Tabel ini menyimpan hasil lookup dan path traversal, bukan relasi turunan permanen. Data cache wajib dihapus ketika data anggota keluarga atau relasi dasar berubah.

| Field | Type |
| --- | --- |
| id | BIGINT PK |
| uuid | CHAR(36) UNIQUE |
| family_id | BIGINT FK families |
| source_member_id | BIGINT FK family_members |
| target_member_id | BIGINT FK family_members |
| relationship_name | VARCHAR(255) NULL |
| relationship_path | JSON |
| is_connected | BOOLEAN |
| expires_at | TIMESTAMP |
| created_at | TIMESTAMP |
| updated_at | TIMESTAMP |

TTL:

```text
24 jam
```

Indexes:

```sql
uuid
family_id
source_member_id
target_member_id
expires_at
family_id, source_member_id, target_member_id
```

---

# family_branches

Cabang keluarga.

Contoh:

```text
Keluarga Besar Ahmad

├── Cabang Jakarta
├── Cabang Bandung
├── Cabang Surabaya
```

Fields:

| Field       | Type         |
| ----------- | ------------ |
| id          | BIGINT PK    |
| family_id   | BIGINT       |
| name        | VARCHAR(255) |
| description | TEXT         |

---

# member_photos

Album foto anggota.

| Field       | Type         |
| ----------- | ------------ |
| id          | BIGINT PK    |
| member_id   | BIGINT       |
| title       | VARCHAR(255) |
| photo_path  | VARCHAR(255) |
| taken_at    | DATE         |
| description | TEXT         |
| uploaded_by | BIGINT       |

---

# member_documents

Dokumen anggota.

| Field         | Type         |
| ------------- | ------------ |
| id            | BIGINT PK    |
| member_id     | BIGINT       |
| document_type | VARCHAR(50)  |
| file_path     | VARCHAR(255) |
| description   | TEXT         |

Jenis:

```text
KTP
KK
Ijazah
Akta Lahir
Akta Kematian
Foto Lama
Dokumen Lain
```

---

# articles

Artikel keluarga.

| Field          | Type                           |
| -------------- | ------------------------------ |
| id             | BIGINT PK                      |
| family_id      | BIGINT                         |
| author_id      | BIGINT                         |
| category_id    | BIGINT                         |
| title          | VARCHAR(255)                   |
| slug           | VARCHAR(255)                   |
| content        | LONGTEXT                       |
| featured_image | VARCHAR(255)                   |
| published_at   | TIMESTAMP                      |
| status         | ENUM(draft,published,archived) |

---

# article_categories

| Field | Type         |
| ----- | ------------ |
| id    | BIGINT PK    |
| name  | VARCHAR(255) |

Contoh:

```text
Sejarah
Pengumuman
Cerita
Memorial
```

---

# article_comments

| Field      | Type      |
| ---------- | --------- |
| id         | BIGINT PK |
| article_id | BIGINT    |
| user_id    | BIGINT    |
| comment    | TEXT      |

---

# article_likes

| Field      | Type      |
| ---------- | --------- |
| id         | BIGINT PK |
| article_id | BIGINT    |
| user_id    | BIGINT    |

---

# events

Acara keluarga.

| Field        | Type         |
| ------------ | ------------ |
| id           | BIGINT PK    |
| family_id    | BIGINT       |
| title        | VARCHAR(255) |
| description  | TEXT         |
| event_date   | DATETIME     |
| location     | VARCHAR(255) |
| organizer_id | BIGINT       |

---

# event_attendees

| Field    | Type               |
| -------- | ------------------ |
| id       | BIGINT PK          |
| event_id | BIGINT             |
| user_id  | BIGINT             |
| status   | ENUM(yes,no,maybe) |

---

# notifications

| Field   | Type         |
| ------- | ------------ |
| id      | BIGINT PK    |
| user_id | BIGINT       |
| title   | VARCHAR(255) |
| body    | TEXT         |
| is_read | BOOLEAN      |

---

# activity_logs

Timeline keluarga.

| Field         | Type         |
| ------------- | ------------ |
| id            | BIGINT PK    |
| family_id     | BIGINT       |
| user_id       | BIGINT       |
| activity_type | VARCHAR(100) |
| payload       | JSON         |

Contoh:

```text
MEMBER_CREATED
ARTICLE_CREATED
PHOTO_UPLOADED
EVENT_CREATED
```

---

# audit_logs

Untuk keamanan.

| Field      | Type         |
| ---------- | ------------ |
| id         | BIGINT PK    |
| user_id    | BIGINT       |
| action     | VARCHAR(255) |
| table_name | VARCHAR(255) |
| record_id  | BIGINT       |
| old_data   | JSON         |
| new_data   | JSON         |
| created_at | TIMESTAMP    |

---

# Family Tree Optimization Tables

---

# member_tree_cache

Cache hasil generate tree.

| Field        | Type      |
| ------------ | --------- |
| id           | BIGINT PK |
| member_id    | BIGINT    |
| tree_json    | LONGTEXT  |
| generated_at | TIMESTAMP |

---

# member_relationship_cache

Cache hasil perhitungan hubungan.

| Field             | Type         |
| ----------------- | ------------ |
| id                | BIGINT PK    |
| source_member_id  | BIGINT       |
| target_member_id  | BIGINT       |
| relationship_name | VARCHAR(255) |
| computed_at       | TIMESTAMP    |

---

# Future Tables

Phase 2+

family_books
voice_stories
dna_records
grave_locations
family_maps
ai_generated_stories

---

# Expected Capacity

Per Family:

* 100.000 anggota
* 500.000 relasi
* 1.000.000 foto

Target:

* Query Tree < 5 detik
* Relationship Lookup < 500 ms

---

# Critical Rule

Seluruh perhitungan hubungan keluarga HARUS berasal dari tabel:

member_relationships

dan TIDAK BOLEH menyimpan relasi turunan seperti:

* Pakde
* Bude
* Sepupu
* Keponakan
* Menantu

Relasi tersebut harus dihitung secara dinamis oleh Relationship Engine.

```
```
