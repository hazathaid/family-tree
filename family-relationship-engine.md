# family-relationship-engine.md

Version: 1.0

Project: Family Tree Platform Indonesia

---

# Overview

Relationship Engine bertanggung jawab untuk:

1. Menghitung hubungan keluarga otomatis.
2. Menentukan panggilan keluarga.
3. Menghasilkan relasi dari sudut pandang anggota tertentu.
4. Mendukung multi-generasi.
5. Mendukung ekspor bagan keluarga.

---

# Design Principle

Sistem hanya menyimpan relasi dasar.

Database hanya boleh menyimpan:

```text
father
mother
child
husband
wife
```

Opsional:

```text
adoptive_father
adoptive_mother
guardian
step_father
step_mother
```

Sistem TIDAK menyimpan:

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
cucu
```

Semua dihitung otomatis.

---

# Family Graph Model

Node:

```text
family_member
```

Edge:

```text
father
mother
child
husband
wife
```

Contoh:

Ahmad
│
father
│
Budi

Artinya:

Budi → father → Ahmad

atau

Ahmad → child → Budi

````

---

# Relationship Resolution

Input:

```text
source_member_id
target_member_id
````

Contoh:

```text
Saya = Ahmad
Target = Joko
```

Output:

```text
Pakde
```

---

# Relationship Priority

Urutan evaluasi:

1. Self
2. Parent
3. Child
4. Sibling
5. Grandparent
6. Grandchild
7. Uncle/Aunt
8. Cousin
9. Nephew/Niece
10. In-Law
11. Extended Family

---

# Level 1 Relationships

## Self

Rule:

```text
source = target
```

Output:

```text
Saya
```

---

## Father

Rule:

```text
target adalah father dari source
```

Output:

```text
Ayah
```

---

## Mother

Rule:

```text
target adalah mother dari source
```

Output:

```text
Ibu
```

---

## Husband

Rule:

```text
target adalah husband
```

Output:

```text
Suami
```

---

## Wife

Rule:

```text
target adalah wife
```

Output:

```text
Istri
```

---

## Son

Rule:

```text
target child
gender male
```

Output:

```text
Anak Laki-Laki
```

---

## Daughter

Rule:

```text
target child
gender female
```

Output:

```text
Anak Perempuan
```

---

# Level 2 Relationships

## Brother

Rule:

```text
Memiliki ayah atau ibu yang sama
gender male
```

Output:

```text
Saudara Laki-Laki
```

---

## Sister

Rule:

```text
Memiliki ayah atau ibu yang sama
gender female
```

Output:

```text
Saudara Perempuan
```

---

## Grandfather

Rule:

```text
Father(Father)
atau
Mother(Father)
```

Output:

```text
Kakek
```

---

## Grandmother

Rule:

```text
Father(Mother)
atau
Mother(Mother)
```

Output:

```text
Nenek
```

---

## Grandson

Output:

```text
Cucu Laki-Laki
```

---

## Granddaughter

Output:

```text
Cucu Perempuan
```

---

# Level 3 Relationships

## Uncle

Rule:

```text
Brother(Father)
atau
Brother(Mother)
```

Output:

```text
Om
```

---

## Aunt

Rule:

```text
Sister(Father)
atau
Sister(Mother)
```

Output:

```text
Tante
```

---

# Indonesian Relationship Naming

Jika usia lebih tua dari ayah:

Output:

```text
Pakde
```

Jika usia lebih muda dari ayah:

Output:

```text
Om
```

Jika usia lebih tua dari ibu:

Output:

```text
Bude
```

Jika usia lebih muda dari ibu:

Output:

```text
Tante
```

---

# Cousin

Rule:

```text
Child(Uncle)
atau
Child(Aunt)
```

Output:

```text
Sepupu
```

---

# Nephew

Rule:

```text
Child(Sibling)
gender male
```

Output:

```text
Keponakan Laki-Laki
```

---

# Niece

Rule:

```text
Child(Sibling)
gender female
```

Output:

```text
Keponakan Perempuan
```

---

# In-Law Relationships

## Father In Law

Rule:

```text
Father(Spouse)
```

Output:

```text
Mertua Laki-Laki
```

---

## Mother In Law

Rule:

```text
Mother(Spouse)
```

Output:

```text
Mertua Perempuan
```

---

## Son In Law

Rule:

```text
Husband(Daughter)
```

Output:

```text
Menantu Laki-Laki
```

---

## Daughter In Law

Rule:

```text
Wife(Son)
```

Output:

```text
Menantu Perempuan
```

---

# Multi Generation Relationships

## Great Grandfather

Rule:

```text
Father(Father(Father))
```

Output:

```text
Buyut
```

---

## Great Grandmother

Output:

```text
Buyut
```

---

## Great Grandchild

Output:

```text
Cicit
```

---

# Generation Calculation

Root User:

```text
Generation 0
```

Parents:

```text
Generation -1
```

Grandparents:

```text
Generation -2
```

Children:

```text
Generation +1
```

Grandchildren:

```text
Generation +2
```

---

# Relationship Traversal Algorithm

Traversal:

```text
Breadth First Search (BFS)
```

Reason:

* Fast
* Shortest relationship path
* Supports large families

Pseudo:

```text
START source

queue.push(source)

WHILE queue not empty

visit node

if target found

return path

END
```

---

# Relationship Path Examples

Example:

Ahmad → Father → Budi

Output:

```text
Ayah
```

Example:

Ahmad → Father → Budi
Budi → Brother → Joko

Output:

```text
Pakde
```

Example:

Ahmad → Father → Budi
Budi → Brother → Joko
Joko → Child → Andi

Output:

```text
Sepupu
```

---

# Relationship Cache

Tabel:

```text
member_relationship_cache
```

Purpose:

Menghindari kalkulasi ulang.

Cache:

```text
source_member_id
target_member_id
relationship_name
computed_at
```

TTL:

```text
24 jam
```

---

# Tree Generation Modes

## Ancestor Tree

Menampilkan leluhur.

```text
Saya
↑
Ayah
↑
Kakek
↑
Buyut
```

---

## Descendant Tree

Menampilkan keturunan.

```text
Saya
↓
Anak
↓
Cucu
↓
Cicit
```

---

## Full Tree

Menampilkan seluruh relasi.

---

# Naming Customization

Sistem harus mendukung:

Indonesia:

```text
Ayah
Ibu
Pakde
Bude
Om
Tante
```

English:

```text
Father
Mother
Uncle
Aunt
Cousin
```

Bahasa daerah dapat ditambahkan di masa depan.

---

# Critical Rule

Relationship Engine adalah sumber kebenaran.

Database hanya menyimpan relasi dasar.

Semua hubungan keluarga turunan wajib dihitung melalui Graph Traversal Engine.

Tidak boleh menyimpan:

* Pakde
* Bude
* Om
* Tante
* Sepupu
* Keponakan
* Menantu
* Mertua

di database.

```
```
