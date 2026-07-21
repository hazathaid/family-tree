# tasks.md

Project: Family Tree Platform Indonesia

Version: 1.1

---

# Project Milestones

Phase 1 - Foundation & Authentication

Phase 2 - Family Management

Phase 3 - Family Members

Phase 4 - Relationship Engine

Phase 5 - Family Tree Generator

Phase 6 - Articles

Phase 7 - Photo Archive

Phase 8 - Timeline

Phase 9 - Events

Phase 10 - Notifications

Phase 11 - Search

Phase 12 - Reporting

Phase 13 - Mobile Application

Phase 14 - Gamification

Phase 15 - Administration

Phase 16 - Production Readiness

Phase 17 - Web Application

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

# Phase 17 - Web Application

Phase 17 builds the Blade web client on top of the existing service and repository layers. Web controllers must remain thin and must not duplicate business logic from API controllers or services.

Implementation order is mandatory. Complete and verify one task before starting the next task.

## Step 1 - Web Foundation

## FT-1701 Web Design System and Shared Layout

Dependencies:

* FT-101

Deliverables:

* Bootstrap 5 design tokens matching `ui-spec.md`
* Inter font with system fallback
* Public, guest, authenticated, and error page layouts
* Responsive top navigation, sidebar, mobile navigation, and footer
* Reusable Blade components for buttons, forms, cards, badges, alerts, pagination, modals, empty states, and loading states
* Vite entry points for shared CSS and JavaScript

Acceptance Criteria:

* Layout works on mobile, tablet, and desktop breakpoints
* Main navigation is keyboard accessible
* Active navigation and validation states are visible
* No Vue, React, or Angular dependency
* Shared components have feature or view tests

---

## FT-1702 Public Landing Page

Dependencies:

* FT-1701

Deliverables:

* Hero section
* Product features
* Family tree preview
* Testimonials
* FAQ
* Call to action and footer
* SEO title, description, Open Graph, and semantic markup

Acceptance Criteria:

* Guest can reach register and login within one click
* Page follows the content and visual direction in `ui-spec.md`
* Responsive images do not cause layout shift
* Landing page feature test passes

---

## Step 2 - Web Authentication

## FT-1703 Authentication Pages and Session Flow

Dependencies:

* FT-103
* FT-1701

Deliverables:

* Login page
* Register page
* Forgot and reset password pages
* Email verification page
* Logout action
* Session-based web authentication using the existing user model and authentication services
* Guest and authenticated middleware redirects

Acceptance Criteria:

* User can register, login, logout, reset password, and verify email from the web
* Forms use Form Request Validation
* CSRF protection is enabled
* Authentication errors do not expose internal exceptions
* Authentication feature tests pass

---

## FT-1704 Web Onboarding and Family Selection

Dependencies:

* FT-201
* FT-202
* FT-1703

Deliverables:

* First-login onboarding
* Create-family flow
* Existing-family selector
* Active family context stored in session
* Empty state for users without a family

Acceptance Criteria:

* New user can create a family and enter its dashboard
* User with multiple families can switch active family
* Family membership authorization is enforced
* Onboarding feature tests pass

---

## Step 3 - Application Home

## FT-1705 Web Dashboard

Dependencies:

* FT-204
* FT-1704

Deliverables:

* Welcome banner
* Member, living, deceased, article, photo, and event statistics
* Recent family activity
* Upcoming birthdays and events
* Notifications summary
* Family facts and recently added members
* Cache-aware dashboard web controller

Acceptance Criteria:

* Dashboard data comes from existing services or dedicated presentation services
* Dashboard respects active family authorization
* Dashboard loads in less than 2 seconds under the documented test dataset
* Every widget has an empty state
* Dashboard feature and performance tests pass

---

## Step 4 - Family and Member Management

## FT-1706 Family Profile and Settings

Dependencies:

* FT-201
* FT-202
* FT-203
* FT-1704

Deliverables:

* Family profile page
* Edit family profile and identity assets
* Family branch management pages
* Family member invitations and role management
* Privacy and notification settings tabs

Acceptance Criteria:

* Owner and admin actions follow RBAC policies
* Destructive actions require confirmation
* Uploads follow documented type and size limits
* Family settings feature tests pass

---

## FT-1707 Member Directory

Dependencies:

* FT-301
* FT-1705

Deliverables:

* Paginated member list
* Search, gender, living status, and branch filters
* Responsive desktop table and mobile cards
* Sort controls
* Member empty state

Acceptance Criteria:

* Filters can be combined and remain in the URL query string
* Queries are paginated and do not load the full family into memory
* Only members from the active family are visible
* Directory feature tests pass

---

## FT-1708 Member Create, Edit, and Detail Pages

Dependencies:

* FT-302
* FT-303
* FT-304
* FT-1707

Deliverables:

* Tabbed member form for basic info, family info, biography, photos, and documents
* Member detail sections for profile, relationships, photos, articles, and timeline
* Profile photo upload and replacement
* Deceased member presentation with memorial marker
* Policy-protected edit and delete actions

Acceptance Criteria:

* Create and update use Form Request Validation
* Profile photos are validated and thumbnails are displayed
* Deceased members display the `†` marker consistently
* Member web feature tests pass

---

## FT-1709 Relationship Management UI

Dependencies:

* FT-401
* FT-402
* FT-404
* FT-1708

Deliverables:

* Add, update, and remove base relationships
* Member relationship list
* Relationship-to-me labels from Relationship Engine
* Searchable member selectors
* Clear graph validation messages

Acceptance Criteria:

* UI only submits father, mother, child, husband, or wife relationships
* Derived relationships are never stored
* Circular and invalid parent relationships are rejected
* Relationship mutations invalidate related caches
* Relationship web feature tests pass

---

## Step 5 - Flagship Family Tree

## FT-1710 Interactive Family Tree Viewer

Dependencies:

* FT-501 through FT-505
* FT-1709

Deliverables:

* Ancestor, descendant, and full tree modes
* Root member and depth selectors
* Vertical, horizontal, and compact layouts
* Pan, zoom, center, expand, and collapse controls
* Search and focus member
* Living, photo, nickname, and relationship-label filters
* Member detail drawer
* Lazy loading for large trees

Acceptance Criteria:

* Tree data comes from Family Tree and Relationship services
* BFS output is rendered without recalculating relationships in JavaScript
* Root member is visually highlighted
* Mobile tree uses compact layout by default
* Keyboard controls and accessible labels are available
* Tree viewer feature and performance tests pass

---

## FT-1711 Web Tree Export and Print Flow

Dependencies:

* FT-506
* FT-507
* FT-508
* FT-1710

Deliverables:

* PNG export action
* PDF export action
* A4, A3, and A2 print size selector
* Export progress, success, and failure states
* Printable tree header, statistics, generation date, and footer

Acceptance Criteria:

* Export uses queued backend export services
* Export output matches selected tree mode and root
* Unauthorized users cannot download another family's export
* Export and print feature tests pass

---

## Step 6 - Family Content and Engagement

## FT-1712 Articles Web Module

Dependencies:

* FT-601 through FT-605
* FT-1705

Deliverables:

* Article cards and filters
* Article detail
* Create, edit, draft, publish, and delete flows
* Category selection and cover image
* Comments, likes, and featured article presentation

Acceptance Criteria:

* Content is sanitized before rendering
* Mutations follow policies and Form Request Validation
* Draft articles are hidden from unauthorized users
* Article web feature tests pass

---

## FT-1713 Photo Archive Web Module

Dependencies:

* FT-701 through FT-703
* FT-1705

Deliverables:

* Responsive photo gallery
* Album list and detail pages
* Photo upload and detail pages
* Member tagging
* Image thumbnails, placeholders, and lazy loading

Acceptance Criteria:

* File type and 10 MB family photo limit are enforced
* Gallery is paginated
* Images include useful alternative text
* Photo archive web feature tests pass

---

## FT-1714 Events and RSVP Web Module

Dependencies:

* FT-901 through FT-903
* FT-1705

Deliverables:

* Upcoming and past event lists
* Event detail
* Create, edit, and delete flows
* RSVP controls and attendee list
* Event reminder status

Acceptance Criteria:

* Dates are displayed in the configured application timezone
* RSVP supports yes, no, and maybe
* Event permissions are enforced
* Event web feature tests pass

---

## FT-1715 Timeline and Notifications Web Module

Dependencies:

* FT-801
* FT-802
* FT-1001
* FT-1705

Deliverables:

* Paginated family timeline
* Member, photo, article, and event filters
* Notification bell with unread count
* Recent notification dropdown
* Notification list, mark-read, and mark-all-read actions

Acceptance Criteria:

* Timeline and notifications are scoped to the authenticated user and active family
* Filters do not trigger unbounded queries
* Notification state updates without a full application reload when Alpine.js is available
* Timeline and notification feature tests pass

---

## Step 7 - Discovery, Insights, and User Account

## FT-1716 Global Search Web Interface

Dependencies:

* FT-1101
* FT-1102
* FT-1705

Deliverables:

* Global search input in authenticated navigation
* Grouped member, article, and event results
* Advanced filters
* Paginated result pages

Acceptance Criteria:

* Search is restricted to families accessible by the user
* Search term is validated and safely escaped
* Empty and no-result states provide useful next actions
* Search web feature tests pass

---

## FT-1717 Reports and Gamification Web Module

Dependencies:

* FT-1201
* FT-1202
* FT-1401 through FT-1403
* FT-1705

Deliverables:

* Family statistic and activity report cards
* Generation, city, growth, and activity visualizations
* Date filters and accessible data tables
* User points, badges, and leaderboard pages

Acceptance Criteria:

* Reports use cached reporting services
* Visual information has a text or table alternative
* Report access follows family policies
* Report and gamification feature tests pass

---

## FT-1718 User Profile, Preferences, and Security

Dependencies:

* FT-104
* FT-1703

Deliverables:

* User profile and avatar page
* Notification preferences
* Change password flow
* Active session information
* Account security settings

Acceptance Criteria:

* Current password is required for sensitive changes
* Avatar upload follows profile photo validation rules
* Session fixation protection is verified
* Profile and security feature tests pass

---

## Step 8 - Administration and Release Quality

## FT-1719 Web Administration Console

Dependencies:

* FT-1501 through FT-1503
* FT-1701

Deliverables:

* User management pages
* Family moderation pages
* Audit log viewer and export action
* Administrative navigation and dashboard

Acceptance Criteria:

* All routes require super admin authorization
* Moderation actions require confirmation and create audit records
* Internal exception data is never displayed
* Administration feature tests pass

---

## FT-1720 Web Accessibility, Performance, and Release Hardening

Dependencies:

* FT-1701 through FT-1719

Deliverables:

* Responsive and cross-browser review
* Keyboard navigation and screen reader review
* Color contrast and responsive typography review
* Query count and N+1 review
* Asset optimization and cache headers
* Custom 403, 404, 419, 422, 429, and 500 pages
* Web smoke test suite

Acceptance Criteria:

* Important actions are reachable within three clicks
* Initial authenticated page and dashboard load in less than 2 seconds under the documented test dataset
* Tree viewer loads in less than 5 seconds under its documented target dataset
* No critical accessibility issue remains
* `composer test`, `composer analyse`, `composer pint`, and `npm run build` pass

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

For Phase 17 web-only tasks, migration, model, repository, API Resource, and API documentation are required only when the task changes backend data or API behavior. Every Phase 17 task must still include:

* Thin Web Controller or documented direct view route
* Existing Service reuse or dedicated presentation service
* Form Request Validation for every POST, PUT, PATCH, and DELETE input
* Authorization Policy enforcement
* Responsive Blade views and reusable components
* Empty, loading, validation, and error states where applicable
* Unit tests for new presentation or service logic
* Web Feature Tests
* Updated web documentation
* Passing Pint, PHPStan, PHPUnit, and frontend build checks
