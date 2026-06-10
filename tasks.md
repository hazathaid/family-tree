# tasks.md

Project: Family Tree Platform Indonesia

Version: 1.0

---

# Project Milestones

Phase 1 - Foundation & Authentication

Phase 2 - Family Management

Phase 3 - Relationship Engine

Phase 4 - Tree Generator

Phase 5 - Articles & Timeline

Phase 6 - Events & Notifications

Phase 7 - Mobile Application

Phase 8 - Gamification

Phase 9 - Reporting & Analytics

Phase 10 - Production Readiness

---

# Phase 1 - Foundation & Authentication

## FT-101 Project Setup

Deliverables:

* Laravel 12 installation
* Bootstrap 5 setup
* Sanctum setup
* Redis setup
* Queue setup
* Docker setup

Acceptance Criteria:

* Application runs successfully
* Queue works
* Authentication ready

---

## FT-102 Database Setup

Deliverables:

* Base migrations
* UUID support
* Audit fields

Tables:

* users
* password_reset_tokens
* personal_access_tokens

Acceptance Criteria:

* Migration success
* Seeder success

---

## FT-103 Authentication

Features:

* Register
* Login
* Logout
* Forgot Password
* Email Verification

Acceptance Criteria:

* User can register
* User can login
* User can reset password

---

## FT-104 User Profile

Features:

* View Profile
* Edit Profile
* Change Password
* Upload Avatar

Acceptance Criteria:

* User can update profile

---

# Phase 2 - Family Management

## FT-201 Family CRUD

Features:

* Create Family
* Edit Family
* Delete Family
* View Family

Tables:

* families

Acceptance Criteria:

* CRUD fully functional

---

## FT-202 Family Roles

Features:

* Invite Member
* Assign Role
* Remove Member

Tables:

* family_user_roles

Roles:

* Owner
* Admin
* Member

Acceptance Criteria:

* RBAC enforced

---

## FT-203 Family Branches

Features:

* Create Branch
* Update Branch
* Delete Branch

Tables:

* family_branches

---

## FT-204 Family Dashboard

Widgets:

* Total Members
* Living Members
* Deceased Members
* Articles
* Events

Acceptance Criteria:

* Dashboard loads <2 seconds

---

# Phase 3 - Family Members

## FT-301 Member CRUD

Features:

* Add Member
* Edit Member
* Delete Member
* View Member

Tables:

* family_members

Acceptance Criteria:

* Full CRUD available

---

## FT-302 Member Profile

Features:

* Biography
* Occupation
* Education
* Contact

Acceptance Criteria:

* Data editable

---

## FT-303 Profile Photo

Features:

* Upload Photo
* Crop Photo
* Replace Photo

Acceptance Criteria:

* Images stored correctly

---

## FT-304 Deceased Member Support

Features:

* Memorial marker
* Death date
* Death location

Acceptance Criteria:

* Display † icon

---

# Phase 4 - Relationship Engine

## FT-401 Relationship CRUD

Features:

* Create Relationship
* Edit Relationship
* Delete Relationship

Tables:

* member_relationships

Relationship Types:

* father
* mother
* child
* husband
* wife

Acceptance Criteria:

* Relationship graph valid

---

## FT-402 Relationship Validation

Rules:

* One biological father
* One biological mother
* No cyclic parent relationship

Acceptance Criteria:

* Invalid graph rejected

---

## FT-403 Graph Traversal Engine

Features:

* BFS traversal
* Shortest path calculation

Acceptance Criteria:

* Relationship lookup <500ms

---

## FT-404 Relationship Resolver

Generate:

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

Acceptance Criteria:

* Accurate relationship naming

---

## FT-405 Relationship Cache

Tables:

* member_relationship_cache

Acceptance Criteria:

* Cached lookups supported

---

# Phase 5 - Family Tree Generator

## FT-501 Graph Builder

Features:

* Build graph from database

Acceptance Criteria:

* Graph generated successfully

---

## FT-502 Ancestor Tree

Features:

* Generate ancestors

Acceptance Criteria:

* Configurable depth

---

## FT-503 Descendant Tree

Features:

* Generate descendants

Acceptance Criteria:

* Configurable depth

---

## FT-504 Full Tree

Features:

* Generate complete tree

Acceptance Criteria:

* Large family support

---

## FT-505 Tree Renderer

Layouts:

* Vertical
* Horizontal
* Radial

Acceptance Criteria:

* Responsive rendering

---

## FT-506 Export PNG

Features:

* Generate PNG

Acceptance Criteria:

* High resolution output

---

## FT-507 Export PDF

Features:

* Generate PDF

Acceptance Criteria:

* Printable output

---

## FT-508 Tree Cache

Tables:

* member_tree_cache

Acceptance Criteria:

* Cached tree support

---

# Phase 6 - Articles

## FT-601 Categories

Tables:

* article_categories

CRUD required

---

## FT-602 Article CRUD

Features:

* Create
* Edit
* Delete
* Publish

Tables:

* articles

Acceptance Criteria:

* Rich text supported

---

## FT-603 Comments

Tables:

* article_comments

Features:

* Add Comment
* Edit Comment
* Delete Comment

---

## FT-604 Likes

Tables:

* article_likes

Features:

* Like
* Unlike

---

## FT-605 Featured Articles

Features:

* Pin article
* Featured article section

---

# Phase 7 - Photo Archive

## FT-701 Photo Upload

Features:

* Upload image
* Compress image
* Resize image

Tables:

* member_photos

---

## FT-702 Albums

Features:

* Create Album
* Edit Album
* Delete Album

---

## FT-703 Photo Tagging

Features:

* Tag family members

Acceptance Criteria:

* Member linking works

---

# Phase 8 - Timeline

## FT-801 Activity Timeline

Tables:

* activity_logs

Features:

* Automatic activity feed

---

## FT-802 Timeline Filters

Filters:

* Articles
* Photos
* Events
* Members

---

# Phase 9 - Events

## FT-901 Event CRUD

Tables:

* events

Features:

* Create Event
* Edit Event
* Delete Event

---

## FT-902 RSVP

Tables:

* event_attendees

Features:

* Yes
* No
* Maybe

---

## FT-903 Event Reminder

Features:

* Scheduled notification

---

# Phase 10 - Notifications

## FT-1001 Notification System

Tables:

* notifications

Features:

* In-app notification

---

## FT-1002 Push Notification

Mobile push support

Acceptance Criteria:

* Android
* iOS

---

# Phase 11 - Search

## FT-1101 Global Search

Search:

* Members
* Articles
* Events

Acceptance Criteria:

* Fast search

---

## FT-1102 Advanced Search

Filters:

* Name
* City
* Generation
* Status

---

# Phase 12 - Reporting

## FT-1201 Family Statistics

Features:

* Member statistics
* Generation statistics

---

## FT-1202 Activity Reports

Features:

* Active users
* Upload reports
* Article reports

---

# Phase 13 - Mobile Application

## FT-1301 Flutter Setup

Features:

* Authentication
* API integration

---

## FT-1302 Mobile Dashboard

Features:

* Statistics
* Timeline

---

## FT-1303 Mobile Family Tree

Features:

* Tree Viewer
* Zoom
* Pan

---

## FT-1304 Mobile Notifications

Features:

* Push notifications

---

# Phase 14 - Gamification

## FT-1401 Points System

Actions:

* Add member
* Upload photo
* Write article

---

## FT-1402 Badges

Badges:

* Penjaga Sejarah
* Penulis Keluarga
* Kontributor Aktif
* Ahli Silsilah

---

## FT-1403 Leaderboard

Features:

* Family ranking
* User ranking

---

# Phase 15 - Administration

## FT-1501 User Management

Features:

* Suspend User
* Activate User

---

## FT-1502 Family Moderation

Features:

* Review families
* Remove content

---

## FT-1503 Audit Logs

Tables:

* audit_logs

Features:

* View logs
* Export logs

---

# Phase 16 - Production Readiness

## FT-1601 Security Hardening

Tasks:

* RBAC Audit
* Rate Limiting
* XSS Protection
* CSRF Protection

---

## FT-1602 Backup System

Features:

* Daily Backup
* Restore Process

---

## FT-1603 Monitoring

Tools:

* Laravel Telescope
* Horizon
* Error Tracking

---

# Definition of Done

For every task Codex must generate:

* Migration
* Model
* Repository
* Service
* Controller
* Request Validation
* API Resource
* Unit Test
* Feature Test
* API Documentation

No task is complete without tests passing.
