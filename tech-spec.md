# tech-spec.md

Project: Family Tree Platform Indonesia

Version: 1.0

---

# System Overview

Family Tree Platform Indonesia adalah platform web dan mobile untuk:

* Manajemen silsilah keluarga
* Relationship Engine
* Tree Generator
* Artikel keluarga
* Arsip foto keluarga
* Event keluarga
* Timeline keluarga

Architecture:

```text
Flutter Mobile

↓

REST API

↓

Laravel Backend

↓

MySQL + Redis + Object Storage
```

---

# Technology Stack

## Backend

Framework:

```text
Laravel 12
PHP 8.3
```

Requirements:

```text
PHP 8.3+
Composer 2+
```

---

## Database

Engine:

```text
MySQL 8
```

Character Set:

```sql
utf8mb4
```

Collation:

```sql
utf8mb4_unicode_ci
```

---

## Cache

Engine:

```text
Redis
```

Used For:

* Relationship Cache
* Tree Cache
* Dashboard Cache
* Session Cache
* Queue

---

## Queue

Driver:

```text
Redis Queue
```

Background Jobs:

* Tree Export
* PDF Export
* Notification Sending
* Image Processing
* Thumbnail Generation

---

## Frontend Web

Technology:

```text
Blade
Bootstrap 5
Alpine.js
```

Do NOT use:

```text
Vue
React
Angular
```

unless explicitly required.

---

## Mobile

Framework:

```text
Flutter
```

Minimum:

```text
Android 10+
iOS 15+
```

State Management:

```text
Riverpod
```

Networking:

```text
Dio
```

Local Storage:

```text
Hive
```

---

# Laravel Packages

## Authentication

Package:

```bash
laravel/sanctum
```

Purpose:

* API Authentication
* Mobile Authentication

---

## Roles & Permissions

Package:

```bash
spatie/laravel-permission
```

Purpose:

* RBAC
* Family Roles
* Admin Roles

---

## Activity Logs

Package:

```bash
spatie/laravel-activitylog
```

Purpose:

* User Activities
* Audit Tracking

---

## Media Library

Package:

```bash
spatie/laravel-medialibrary
```

Purpose:

* Profile Photos
* Family Photos
* Documents

---

## Slugs

Package:

```bash
spatie/laravel-sluggable
```

Purpose:

* Family URLs
* Article URLs

---

## Backup

Package:

```bash
spatie/laravel-backup
```

Purpose:

* Automated Backup

---

## Excel Export

Package:

```bash
maatwebsite/excel
```

Purpose:

* Reports
* Member Export

---

## PDF Export

Package:

```bash
barryvdh/laravel-dompdf
```

Purpose:

* Family Tree PDF
* Reports

---

## Image Processing

Package:

```bash
intervention/image
```

Purpose:

* Resize
* Crop
* Thumbnail

---

## API Documentation

Package:

```bash
dedoc/scramble
```

Purpose:

* Automatic API Documentation

---

## UUID Support

Package:

```bash
ramsey/uuid
```

---

# Folder Structure

```text
app/

├── Actions/
├── DTOs/
├── Exceptions/

├── Models/

├── Repositories/
│   ├── Contracts/
│   └── Eloquent/

├── Services/

├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/

├── Policies/

├── Jobs/

├── Events/

├── Listeners/

└── Console/
```

---

# Architectural Rules

## Controller

Controller Responsibilities:

* Receive request
* Call service
* Return response

Controllers must not contain business logic.

---

## Service Layer

All business logic belongs here.

Examples:

```text
RelationshipService
FamilyTreeService
ArticleService
NotificationService
```

---

## Repository Layer

Responsible for:

* Database access
* Query optimization

Services must not query Eloquent directly.

---

## DTO Layer

Use DTOs for:

* Service Inputs
* Service Outputs

Avoid passing raw arrays.

---

# Database Standards

Every table must contain:

```sql
id
uuid
created_at
updated_at
```

When appropriate:

```sql
deleted_at
```

Use SoftDeletes.

---

# UUID Strategy

Primary Key:

```sql
BIGINT
```

Public Identifier:

```sql
UUID
```

Example:

```sql
id = 1001

uuid = 6f63cfe5...
```

---

# Storage Strategy

Storage Driver:

```text
S3 Compatible
```

Supported:

* MinIO
* AWS S3
* Cloudflare R2

Directories:

```text
avatars/
family-photos/
documents/
articles/
exports/
```

---

# Image Standards

Profile Photo:

```text
512x512
```

Thumbnail:

```text
128x128
```

Article Cover:

```text
1200x630
```

---

# Family Tree Engine

Core Service:

```text
FamilyTreeService
```

Responsibilities:

* Build Graph
* Build Tree
* Generate Layout
* Export Tree

---

# Relationship Engine

Core Service:

```text
RelationshipService
```

Responsibilities:

* BFS Traversal
* Relationship Resolution
* Cache Handling

---

# Graph Algorithm

Preferred:

```text
Breadth First Search (BFS)
```

Use BFS for:

* Relationship lookup
* Family tree traversal

Do NOT use recursive traversal for large trees.

---

# Cache Strategy

Redis Keys

Relationship:

```text
relationship:{source}:{target}
```

Tree:

```text
tree:{member_id}:{mode}
```

Dashboard:

```text
dashboard:{family_id}
```

TTL:

```text
24 hours
```

---

# API Standards

Version:

```text
/api/v1
```

Format:

```json
{
  "success": true,
  "message": "Success",
  "data": {}
}
```

Errors:

```json
{
  "success": false,
  "message": "Validation Error",
  "errors": {}
}
```

---

# Tree Export Strategy

PNG Export

Technology:

```text
Headless Chromium
```

Recommended Package:

```bash
spatie/browsershot
```

Purpose:

* High resolution PNG export

---

# PDF Export Strategy

Flow:

```text
Tree HTML

↓

PDF Render

↓

Download
```

Library:

```bash
barryvdh/laravel-dompdf
```

---

# Security

Authentication:

```text
Sanctum
```

Authorization:

```text
Policies
Spatie Permission
```

Protection:

* CSRF
* XSS
* SQL Injection
* Rate Limiting

---

# Logging

Activity:

```text
activity_logs
```

Audit:

```text
audit_logs
```

Critical actions must be logged.

---

# Testing

Framework:

```text
PHPUnit
```

Additional:

```text
Laravel Pest
```

Preferred:

```text
Pest
```

Coverage:

```text
80% minimum
```

Relationship Engine:

```text
95% minimum
```

---

# CI/CD

Pipeline

1. Composer Install
2. Pint
3. PHPStan
4. PHPUnit/Pest
5. Build Docker Image
6. Deploy

---

# Docker Services

Containers:

```text
app
nginx
mysql
redis
queue
scheduler
```

---

# Monitoring

Tools:

```text
Laravel Horizon
Laravel Telescope
```

Production:

```text
Sentry
```

---

# Scalability Targets

Per Family:

```text
100.000 Members
500.000 Relationships
1.000.000 Photos
```

Performance:

```text
Relationship Lookup < 500ms

Tree Generation < 5s

Dashboard < 2s
```

---

# Critical Technical Rules

1. Relationship Engine is the source of truth.

2. Family Tree must be generated from graph traversal.

3. Never store derived relationships.

4. All business logic belongs in Services.

5. Controllers must remain thin.

6. Repository Pattern is mandatory.

7. Redis caching must be used for expensive operations.

8. Mobile application must consume REST API only.

9. Tree exports must run through queues.

10. Every feature must have tests before completion.
