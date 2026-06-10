# Phase 1 Database Documentation

Phase 1 implements only foundation and authentication tables.

## users

Stores login accounts.

| Field | Type | Notes |
| --- | --- | --- |
| id | BIGINT | Primary key |
| uuid | UUID | Unique public identifier |
| name | VARCHAR(255) | Required |
| email | VARCHAR(255) | Unique, indexed |
| phone | VARCHAR(30) | Nullable, indexed |
| password | VARCHAR(255) | Hashed |
| avatar | VARCHAR(255) | Public disk path |
| email_verified_at | TIMESTAMP | Nullable |
| last_login_at | TIMESTAMP | Nullable |
| status | ENUM(active,suspended) | Indexed |
| remember_token | VARCHAR(100) | Nullable |
| created_at | TIMESTAMP | Audit field |
| updated_at | TIMESTAMP | Audit field |

## password_reset_tokens

Laravel password broker table keyed by email.

## personal_access_tokens

Laravel Sanctum token table for mobile and API authentication.

## sessions

Laravel session table. Redis is configured as the default runtime session driver in `.env.example`; this table remains available for local fallback and framework compatibility.

## cache and cache_locks

Laravel cache tables. Redis is configured as the default runtime cache store in `.env.example`.

## jobs, job_batches, failed_jobs

Queue tables for fallback and operational visibility. Redis is configured as the default queue connection in `.env.example`.

## Deferred Tables

Family, member, relationship, tree, article, event, notification, activity log, and audit log tables are intentionally not created in Phase 1. They belong to Phase 2 and later tasks.
