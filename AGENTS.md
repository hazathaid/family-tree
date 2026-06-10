# AGENTS.md

# Family Tree Platform Indonesia

## Project Overview

Family Tree Platform Indonesia adalah platform Web dan Mobile untuk:

* Manajemen silsilah keluarga
* Dokumentasi sejarah keluarga
* Artikel keluarga
* Arsip foto keluarga
* Event keluarga
* Relationship Engine
* Family Tree Generator

System must support large family trees with more than 100,000 members.

---

# Read First

Before implementing ANY feature always read:

```text
docs/prd.md
docs/database-schema.md
docs/api-spec.md
docs/family-relationship-engine.md
docs/tree-generation-engine.md
docs/tasks.md
```

Never start implementation without reading the documents above.

---

# Development Strategy

DO NOT implement the entire application at once.

Only implement the requested Phase or Task from tasks.md.

Example:

```text
Implement FT-301 only
```

or

```text
Implement Phase 4 only
```

Never generate future phases unless explicitly requested.

---

# Technology Stack

Backend:

* Laravel 12
* PHP 8.3

Database:

* MySQL 8

Cache:

* Redis

Queue:

* Redis Queue

Frontend Web:

* Blade
* Bootstrap 5

Mobile:

* Flutter

Authentication:

* Laravel Sanctum

Storage:

* S3 Compatible Storage

Deployment:

* Docker
* Nginx

---

# Architecture Rules

Mandatory:

* Clean Architecture
* Repository Pattern
* Service Layer Pattern

Controllers must remain thin.

Business logic must NEVER be placed in:

* Controllers
* Models
* Blade Views

Business logic must be implemented inside Services.

---

# Required Folder Structure

```text
app/

├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/

├── Models/

├── Repositories/
│   ├── Contracts/
│   └── Eloquent/

├── Services/

├── Actions/

├── DTOs/

├── Policies/

├── Jobs/

├── Events/

├── Listeners/

└── Exceptions/
```

Follow this structure strictly.

---

# Database Rules

All migrations must:

* Use foreign keys
* Use indexes
* Use timestamps
* Use soft deletes where appropriate

All entities must support UUID.

Example:

```php
$table->uuid('uuid')->unique();
```

---

# Relationship Engine Rules

CRITICAL

Relationship Engine is the heart of the system.

Database may only store:

* father
* mother
* child
* husband
* wife

Never store:

* Pakde
* Bude
* Om
* Tante
* Sepupu
* Keponakan
* Menantu
* Mertua

These relationships must be computed dynamically.

Implementation must follow:

```text
docs/family-relationship-engine.md
```

exactly.

---

# Tree Generator Rules

Implementation must follow:

```text
docs/tree-generation-engine.md
```

exactly.

Tree Generator must:

* Support ancestor trees
* Support descendant trees
* Support full trees

Exports:

* PNG
* PDF

Tree generation must use graph traversal.

Do not hardcode relationships.

---

# Graph Traversal

Preferred algorithm:

```text
Breadth First Search (BFS)
```

Use BFS for:

* Relationship lookup
* Relationship calculation
* Tree generation

Avoid recursive solutions that may fail on very large trees.

---

# Performance Requirements

Relationship lookup:

```text
< 500ms
```

Dashboard:

```text
< 2 seconds
```

Tree generation:

```text
< 5 seconds
```

Support:

```text
100,000 family members
```

per family.

---

# Caching Rules

Use Redis caching.

Cache:

* Relationship calculations
* Dashboard statistics
* Tree generation

Tables:

```text
member_relationship_cache
member_tree_cache
```

must be used when available.

---

# API Rules

All APIs must:

* Follow REST conventions
* Return JSON
* Use API Resources

Success response:

```json
{
  "success": true,
  "message": "Success",
  "data": {}
}
```

Error response:

```json
{
  "success": false,
  "message": "Error",
  "errors": {}
}
```

Never expose internal exceptions.

---

# Validation Rules

Every POST, PUT and PATCH endpoint must use:

```text
Form Request Validation
```

Validation logic must not be placed in controllers.

---

# Security Rules

Mandatory:

* RBAC
* Authorization Policies
* CSRF Protection
* XSS Protection
* SQL Injection Protection
* Rate Limiting

All uploads must be validated.

Allowed uploads:

* jpg
* jpeg
* png
* webp
* pdf

---

# Upload Rules

Profile photos:

```text
Max 5 MB
```

Documents:

```text
Max 20 MB
```

Family photos:

```text
Max 10 MB
```

Generate thumbnails automatically.

---

# Logging Rules

All critical actions must be logged.

Examples:

* Member created
* Member updated
* Relationship created
* Relationship deleted
* Article published
* Event created

Use:

```text
activity_logs
audit_logs
```

---

# UI Rules

Responsive first.

Support:

* Desktop
* Tablet
* Mobile

Bootstrap 5 only.

Avoid unnecessary JavaScript frameworks.

Prefer:

* Blade
* Alpine.js

when interaction is required.

---

# Mobile Rules

Flutter consumes REST API only.

Never duplicate business logic in Flutter.

All business logic must remain in Laravel.

Flutter responsibilities:

* Display
* Input
* Notifications

Laravel responsibilities:

* Processing
* Validation
* Relationship calculation
* Tree generation

---

# Testing Rules

Every task must generate:

* Unit Tests
* Feature Tests

Required coverage:

```text
80% minimum
```

Relationship Engine:

```text
95% minimum
```

must be covered.

---

# Required Tests For Relationship Engine

Generate tests for:

* Ayah
* Ibu
* Kakek
* Nenek
* Pakde
* Bude
* Om
* Tante
* Sepupu
* Keponakan
* Menantu
* Mertua
* Buyut
* Cicit

Do not skip these tests.

---

# Code Quality Rules

Use:

* PHPStan
* Laravel Pint

Code must pass:

```bash
composer test
composer analyse
composer pint
```

before task completion.

---

# Documentation Rules

For every completed task generate:

* API documentation
* Database documentation
* Service documentation

Update docs when implementation changes.

---

# Task Completion Checklist

A task is NOT complete until all are delivered:

* Migration
* Model
* Repository
* Service
* Controller
* Request Validation
* API Resource
* Tests
* Documentation

Missing any item means task is incomplete.

---

# Critical Rule

Never simplify the Relationship Engine.

Never replace graph traversal with hardcoded logic.

Never store derived family relationships in the database.

Relationship Engine and Tree Generator are core business features and must always follow the project specifications.
