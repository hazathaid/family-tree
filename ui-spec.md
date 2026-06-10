# ui-spec.md

Project: Family Tree Platform Indonesia

Version: 1.0

---

# Design Philosophy

Application must feel like:

* Family Social Network
* Family Knowledge Base
* Family Tree Platform

Users should have reasons to return regularly.

The application must not feel like a static genealogy archive.

---

# Design Goals

Primary Goals:

1. Easy to use
2. Mobile friendly
3. Fast navigation
4. Family engagement
5. Beautiful family tree visualization

---

# Design Language

Style:

```text
Modern
Clean
Friendly
Family Oriented
```

Inspiration:

```text
MyHeritage
Facebook Groups
LinkedIn
Notion
```

---

# Color Palette

Primary:

```text
#1E88E5
```

Secondary:

```text
#43A047
```

Accent:

```text
#FB8C00
```

Danger:

```text
#E53935
```

Background:

```text
#F5F7FA
```

---

# Typography

Font:

```text
Inter
```

Fallback:

```text
Arial
Sans-serif
```

---

# Responsive Breakpoints

Mobile:

```text
<768px
```

Tablet:

```text
768px - 1024px
```

Desktop:

```text
>1024px
```

---

# Navigation Structure

Main Navigation

```text
Dashboard
Family Tree
Members
Articles
Photos
Events
Timeline
Reports
Settings
```

---

# Public Website

## Landing Page

Sections:

1. Hero Section
2. Features
3. Family Tree Preview
4. Testimonials
5. Pricing (future)
6. FAQ
7. Footer

---

## Hero Section

Headline:

```text
Bangun dan Wariskan Sejarah Keluarga Anda
```

Buttons:

```text
Mulai Sekarang
Lihat Demo
```

Illustration:

Family Tree Preview

---

# Authentication Pages

## Login Page

Fields:

```text
Email
Password
Remember Me
```

Buttons:

```text
Masuk
Daftar
Lupa Password
```

---

## Register Page

Fields:

```text
Nama
Email
Nomor HP
Password
Konfirmasi Password
```

---

# Dashboard

Dashboard is the most important page.

Purpose:

Make users return daily.

---

## Dashboard Layout

Top:

```text
Welcome Banner
```

Middle:

Statistics Cards

Bottom:

Timeline Feed

Right Sidebar:

Upcoming Events
Birthdays
Notifications

---

## Statistics Cards

Show:

```text
Total Anggota

Anggota Hidup

Anggota Meninggal

Artikel

Foto

Acara
```

---

## Activity Feed

Display:

```text
Anggota baru ditambahkan

Artikel baru

Foto baru

Acara baru
```

Style:

Social Media Timeline

---

## Birthday Widget

Display:

```text
Ulang tahun hari ini

Ulang tahun minggu ini
```

---

## Family Facts Widget

Random family facts.

Examples:

```text
Generasi tertua

Anggota tertua

Anggota termuda
```

---

# Family Tree Page

Core Feature

Menu:

```text
Ancestor Tree
Descendant Tree
Full Tree
```

---

## Tree Toolbar

Actions:

```text
Zoom In
Zoom Out
Center
Search
Export PNG
Export PDF
```

---

## Tree Controls

Filters:

```text
Living Only
Show Photos
Show Nicknames
Show Relationship Labels
```

---

## Tree Canvas

Requirements:

```text
Pan
Zoom
Expand
Collapse
```

---

## Tree Node Design

Display:

```text
Photo

Full Name

Nickname

Birth Year

Death Year
```

Example:

```text
[PHOTO]

Ahmad Santoso

(Budi)

1980 - Present
```

---

## Root Node

Highlight:

```text
Border Accent
Larger Card
```

---

## Deceased Member

Display:

```text
† Symbol

Gray Style
```

Example:

```text
† Ahmad Santoso
```

---

## Node Detail Drawer

When clicked:

Show:

```text
Photo

Biography

Occupation

Education

Relationship

Photos

Articles
```

---

# Members Module

## Members List

Table Columns:

```text
Photo
Name
Nickname
Gender
Birth Date
Status
Actions
```

---

## Member Detail

Sections:

```text
Profile
Biography
Relationships
Photos
Articles
Timeline
```

---

## Member Form

Tabs:

```text
Basic Info
Family Info
Biography
Photos
Documents
```

---

# Articles Module

## Article List

Layout:

Card View

Display:

```text
Cover Image

Title

Author

Likes

Comments
```

---

## Article Detail

Sections:

```text
Cover Image
Content
Comments
Related Articles
```

---

## Editor

Features:

```text
Rich Text Editor
Image Upload
Draft
Publish
```

---

# Photos Module

## Gallery View

Layout:

Pinterest Style Grid

Display:

```text
Photo

Title

Tagged Members
```

---

## Photo Detail

Display:

```text
Large Image

Description

Tagged Members

Comments
```

---

# Events Module

## Event List

Card Layout

Display:

```text
Title

Date

Location

RSVP Count
```

---

## Event Detail

Sections:

```text
Description

Location

Attendees

Comments
```

---

# Timeline Module

Purpose:

Increase engagement.

---

## Timeline Feed

Display:

```text
Member Added

Member Updated

Article Published

Photo Uploaded

Event Created
```

---

## Timeline Filters

```text
All

Members

Photos

Articles

Events
```

---

# Notifications

Notification Bell

Display:

```text
Unread Count
```

Dropdown:

```text
Recent Notifications
```

---

# Reports Module

Cards:

```text
Total Members

Generations

Growth

Activity
```

Charts:

```text
Members by Generation

Members by City

Activity Trends
```

---

# Settings Module

Tabs:

```text
Profile

Family Settings

Privacy

Notifications

Security
```

---

# Family Profile Page

Display:

```text
Family Name

Origin

Description

Statistics

Recent Activity
```

---

# Gamification

Purpose:

Increase engagement.

---

## User Points

Actions:

```text
Add Member

Upload Photo

Write Article
```

---

## Badges

Display:

```text
Penjaga Sejarah

Kontributor Aktif

Penulis Keluarga

Ahli Silsilah
```

---

# Mobile App Structure

Bottom Navigation

```text
Home
Tree
Timeline
Events
Profile
```

---

# Mobile Home Screen

Sections:

```text
Dashboard Cards

Timeline

Upcoming Birthdays

Notifications
```

---

# Mobile Tree Screen

Features:

```text
Zoom

Pan

Expand

Collapse

Search
```

---

# Mobile Timeline

Infinite Scroll

Social Style Feed

---

# Empty States

Every page must include:

```text
Illustration

Helpful Message

Call To Action
```

Example:

```text
Belum ada anggota keluarga.

Tambahkan anggota pertama Anda.
```

---

# Accessibility

Requirements:

* Keyboard navigation
* Screen reader friendly
* Proper contrast ratio
* Responsive text sizing

---

# Performance UX Rules

Initial Page Load:

```text
< 2 seconds
```

Dashboard:

```text
< 2 seconds
```

Tree Viewer:

```text
< 5 seconds
```

---

# Engagement Features

Must display on dashboard:

1. Upcoming Birthdays
2. Family Anniversaries
3. New Articles
4. New Photos
5. Family Events
6. Recently Added Members
7. Family Statistics

These widgets are intended to increase daily active usage.

---

# Critical UI Rules

1. Dashboard must feel alive.
2. Family Tree is the flagship feature.
3. Mobile experience is first-class.
4. Every important action should be reachable within 3 clicks.
5. Tree Viewer must support zoom and pan.
6. Photos should appear throughout the application.
7. Timeline should encourage recurring visits.
8. Family history should feel engaging, not archival.
