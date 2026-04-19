# Noteon

> **Your workspace, simplified.**  
> A clean, distraction-free workspace for capturing ideas, building docs, and organizing everything with nested pages, flexible content blocks, and customizable sections.

---

## Table of Contents

1. [Overview](#overview)
2. [Features](#features)
3. [Tech Stack](#tech-stack)
4. [Project Structure](#project-structure)
5. [Database Schema](#database-schema)
6. [API Reference](#api-reference)
7. [Frontend Modules](#frontend-modules)
8. [Setup & Installation](#setup--installation)
9. [User Flow](#user-flow)
10. [Customization System](#customization-system)
11. [Known Limitations & Roadmap](#known-limitations--roadmap)

---

## Overview

**Noteon** is a Notion-inspired personal knowledge management web application. It allows users to create and organize notes using a block-based editor, nested page hierarchy, and grouped sections — all in a premium dark-themed interface built with vanilla PHP and JavaScript (no heavy frameworks).

The app was built progressively from a simple note-taking tool into a full-featured workspace with:
- Multi-workspace support per user
- Section-based sidebar organization
- A rich block editor (text, headings, checklists)
- Live page customization (font, size, background patterns)
- A beautiful landing page with silk canvas animation

---

## Features

### 🔐 Authentication
- User **registration** and **login** with session-based auth
- Passwords hashed with `password_hash()` (bcrypt)
- A default workspace is auto-created on registration
- Session guard on all authenticated routes

### 🏢 Workspaces
- Each user can have **multiple workspaces**
- Workspaces can be **created**, **renamed**, and **deleted**
- The active workspace is always displayed in the top-left sidebar switcher
- Deleting a workspace cascades to all its pages, blocks, and sections via database `ON DELETE CASCADE`

### 📂 Sections
- Pages can be grouped into **Sections** (like folders in a sidebar)
- Sections have a **custom emoji icon** chosen from an inline picker
- Sections support **create**, **rename** (via modal), and **delete**
- Deleting a section sets its pages' `section_id` to `NULL` (pages become "uncategorized" — not deleted)
- Pages without a section appear under an implicit **"Pages"** group

### 📄 Pages
- Hierarchical **nested pages** (parent-child relationship)
- Pages have an emoji icon (editable), a title, and an auto-saved content body
- Each page opens as a **tab** in the top navigation bar (Notion-style)
- Closing the last tab returns you to the Home view
- Pages can be created inside a section or as top-level uncategorized pages
- Sub-pages can be created inside any page

### ✍️ Block Editor
- Each page contains a list of **content blocks**
- Block types:
  - **Text** — plain paragraph
  - **Heading** — large H1-style section title
  - **Checklist** — interactive to-do items with checkbox state
- Blocks auto-save with an **800ms debounce** after each keystroke
- A **word count** is displayed live in the page meta bar
- Blocks can be added, deleted, and reordered via a context menu (⋮ icon)
- **Slash command** (`/`) menu for quick block-type switching
- **Drag-to-reorder** blocks within the page

### 🎨 Customize Panel (Three-dots menu)
Accessed via the `⋮` button in the top-right of the topbar:
- **Font Size**: Small / Default / Large
- **Font Family**: Serif (Cormorant Garamond), Sans-serif (Inter), Mono (JetBrains Mono)
- **Page Background**: 8 options — Clean, Grid, Dots, Lines, Crosshatch, Waves, Warm (amber glow), Night (indigo glow)
- All preferences are persisted in `localStorage` and restored on page load

### 🏠 Home View
- Personalized greeting (`Good morning, [name].`)
- Live date display
- Quick action cards: New Page, New Workspace, Search
- Scrollable "Recent Pages" list

### 🔍 Search
- Real-time client-side search across all page titles in the sidebar
- Matching pages are instantly filtered as you type

---

## Tech Stack

| Layer       | Technology                                      |
|-------------|-------------------------------------------------|
| Backend     | PHP 8+ (procedural + OOP models)                |
| Database    | MySQL 8 via PDO (XAMPP / MariaDB)               |
| Frontend    | Vanilla JavaScript (ES6+ IIFE modules)          |
| Styling     | Vanilla CSS (custom design system)              |
| Fonts       | Google Fonts: Cormorant Garamond, Inter, Playfair Display |
| Animation   | Custom canvas-based Silk animation (landing page) |
| Server      | Apache (XAMPP local environment)                |
| Auth        | PHP session (`$_SESSION`)                       |
| Persistence | `localStorage` for user UI preferences          |

> **No build tools, no npm, no frameworks.** Everything is served directly as PHP + static files through XAMPP.

---

## Project Structure

```
hagglenote/
├── index.php                  # Entry point — redirects to views/index.php
├── schema.sql                 # Full MySQL database schema (run once to set up)
│
├── config/
│   └── database.php           # PDO singleton — getDB() helper function
│
├── models/                    # Data-access layer (static PHP classes)
│   ├── UserModel.php          # register(), login(), findByEmail()
│   ├── WorkspaceModel.php     # create(), list(), rename(), delete(), findById()
│   ├── PageModel.php          # create(), getByWorkspace(), update(), delete()
│   └── BlockModel.php         # getByPage(), upsert(), delete(), reorder()
│
├── api/                       # JSON REST-like endpoints (called via fetch)
│   ├── auth.php               # register | login | logout | check
│   ├── workspace.php          # create | list | rename | delete
│   ├── section.php            # create | list | rename | delete
│   ├── page.php               # create | list | update | delete
│   └── block.php              # get | save | delete | reorder
│
├── views/
│   ├── index.php              # Landing page (silk animation, login/register modal)
│   └── editor.php             # Main app shell (sidebar + topbar + editor)
│
└── assets/
    ├── css/
    │   └── style.css          # Global + landing page styles
    └── js/
        ├── app.js             # Bootstrap, global state (App), utilities
        ├── workspace.js       # WorkspaceManager IIFE — switcher, CRUD, dropdown
        ├── page.js            # PageManager IIFE — sidebar tree, sections, tabs
        └── editor.js          # Editor IIFE — block rendering, auto-save, slash menu
```

---

## Database Schema

### `users`
| Column     | Type         | Notes                    |
|------------|--------------|--------------------------|
| id         | INT PK AI    |                          |
| name       | VARCHAR(100) | Display name             |
| email      | VARCHAR(100) | Unique, used for login   |
| password   | VARCHAR(255) | bcrypt hash              |
| created_at | TIMESTAMP    |                          |

### `workspaces`
| Column     | Type         | Notes                             |
|------------|--------------|-----------------------------------|
| id         | INT PK AI    |                                   |
| user_id    | INT FK       | → users.id ON DELETE CASCADE      |
| name       | VARCHAR(150) |                                   |
| created_at | TIMESTAMP    |                                   |

### `sections`
| Column       | Type        | Notes                                     |
|--------------|-------------|-------------------------------------------|
| id           | VARCHAR(50) | UUID string PK                            |
| workspace_id | INT FK      | → workspaces.id ON DELETE CASCADE         |
| icon         | VARCHAR(50) | Emoji, default 📁                         |
| name         | VARCHAR(150)|                                           |
| position     | INT         | For future reordering                     |

### `pages`
| Column       | Type         | Notes                                         |
|--------------|--------------|-----------------------------------------------|
| id           | INT PK AI    |                                               |
| workspace_id | INT FK       | → workspaces.id ON DELETE CASCADE             |
| section_id   | VARCHAR(50)  | → sections.id ON DELETE SET NULL (nullable)   |
| parent_id    | INT FK       | → pages.id ON DELETE CASCADE (nullable)       |
| title        | VARCHAR(200) | Default 'Untitled'                            |
| created_at   | TIMESTAMP    |                                               |
| updated_at   | TIMESTAMP    | Auto-updated on row change                    |

### `blocks`
| Column   | Type        | Notes                                   |
|----------|-------------|-----------------------------------------|
| id       | INT PK AI   |                                         |
| page_id  | INT FK      | → pages.id ON DELETE CASCADE            |
| type     | VARCHAR(50) | `text` / `heading` / `checklist`        |
| content  | TEXT        |                                         |
| position | INT         | Ordering within page (1-based)          |

### `checklist_items`
| Column     | Type      | Notes                            |
|------------|-----------|----------------------------------|
| id         | INT PK AI |                                  |
| block_id   | INT FK    | → blocks.id ON DELETE CASCADE    |
| content    | TEXT      |                                  |
| is_checked | BOOLEAN   | Default FALSE                    |

---

## API Reference

All endpoints return JSON. All require an active PHP session (except `auth.php`).

### `api/auth.php`
| Action     | Body                      | Response                |
|------------|---------------------------|-------------------------|
| `register` | `{name, email, password}` | `{success, redirect}`   |
| `login`    | `{email, password}`       | `{success, redirect}`   |
| `logout`   | —                         | `{success, redirect}`   |
| `check`    | —                         | `{authenticated, user?}`|

### `api/workspace.php`
| Action   | Body         | Response               |
|----------|--------------|------------------------|
| `create` | `{name}`     | `{success, workspace}` |
| `list`   | —            | `{workspaces[]}`       |
| `rename` | `{id, name}` | `{success}`            |
| `delete` | `{id}`       | `{success}`            |

### `api/section.php`
| Action   | Body                         | Response                    |
|----------|------------------------------|-----------------------------|
| `create` | `{workspace_id, name, icon}` | `{success, id, name, icon}` |
| `list`   | `?workspace_id=X`            | `{sections[]}`              |
| `rename` | `{id, name, icon}`           | `{success}`                 |
| `delete` | `{id}`                       | `{success}`                 |

### `api/page.php`
| Action   | Body                                              | Response          |
|----------|---------------------------------------------------|-------------------|
| `create` | `{workspace_id, parent_id?, section_id?, title}`  | `{success, page}` |
| `list`   | `?workspace_id=X`                                 | `{pages[], sections[]}` |
| `update` | `{id, title?}`                                    | `{success}`       |
| `delete` | `{id}`                                            | `{success}`       |

### `api/block.php`
| Action    | Body                              | Response      |
|-----------|-----------------------------------|---------------|
| `get`     | `?page_id=X`                      | `{blocks[]}`  |
| `save`    | `{page_id, blocks[]}`             | `{success}`   |
| `delete`  | `{id}`                            | `{success}`   |
| `reorder` | `{page_id, order[]}` (block ids)  | `{success}`   |

---

## Frontend Modules

### `app.js` — Global Bootstrap
- Defines the global `App` object with `App.state` (`workspaceId`, `pageId`, `isDragging`)
- Provides `App.confirm()` — a custom modal replacement for `window.confirm()`
- Utilities: `apiPost()`, `apiGet()`, `showToast()`, `debounce()`, `setSaveState()`
- Bootstraps `WorkspaceManager.init()` on `DOMContentLoaded`

### `workspace.js` — WorkspaceManager
- Loads all workspaces for the logged-in user on init
- Renders the workspace switcher dropdown with edit/delete actions per item
- Handles modal UI for **Create** and **Rename** workspace
- Uses `App.confirm()` for delete confirmation

### `page.js` — PageManager
- Fetches all pages AND sections for the active workspace
- Renders the sidebar tree: sections with their pages, then uncategorized pages
- Manages **tabs** in the topbar (open, close, switch)
- Handles page creation (with optional section assignment), deletion
- Manages section CRUD — all via a shared modal with inline emoji picker
- Exposes `window.PageManager` for cross-module access

### `editor.js` — Editor
- Renders the full page view: emoji icon, title, meta bar, blocks
- Handles block creation (Enter key), deletion (Backspace on empty), type switching
- Auto-saves each block with an 800ms debounce via `apiPost`
- Implements the **Slash command** (`/`) menu for block-type selection
- Implements **drag-to-reorder** blocks
- Exposes `window.Editor` for cross-module access

---

## Setup & Installation

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) (PHP 8+ and MySQL/MariaDB)
- A modern web browser

### Steps

**1. Place project files:**
```
/Applications/XAMPP/xamppfiles/htdocs/hagglenote/
```

**2. Start XAMPP** — ensure Apache and MySQL are both running.

**3. Create the database** — open phpMyAdmin at `http://localhost/phpmyadmin` and import `schema.sql`, or run from terminal:
```bash
/Applications/XAMPP/xamppfiles/bin/mysql -u root hagglenote < schema.sql
```

**4. Configure DB credentials** (if needed) — edit `config/database.php`:
```php
$host     = '127.0.0.1';
$username = 'root';
$password = '';       // update if your MySQL root has a password
$dbname   = 'hagglenote';
```

**5. Open the app:**
```
http://localhost/hagglenote/
```

**6. Register** a new account — a default workspace is created automatically and you're taken directly to the editor.

---

## User Flow

```
Landing Page (index.php)
    │
    ├── Register ──→ auto login ──→ editor.php
    └── Login    ──→ auth check ──→ editor.php
                                        │
                        ┌───────────────┴───────────────────────┐
                        │              EDITOR SHELL              │
                        ├───────────────────────────────────────┤
                        │  Sidebar                               │
                        │   ├── Workspace Switcher (w/ edit/del) │
                        │   ├── Search bar (real-time filter)    │
                        │   ├── Section groups (w/ emoji icon)   │
                        │   │     └── Pages (nested tree)        │
                        │   ├── Uncategorized Pages              │
                        │   └── Add Section / Add Page buttons   │
                        │                                        │
                        │  Topbar                                │
                        │   ├── Home tab (always present)        │
                        │   ├── Open page tabs (closeable)       │
                        │   ├── Save indicator                   │
                        │   └── ⋮ Customize panel                │
                        │                                        │
                        │  Content Area                          │
                        │   ├── Home View (greeting + recent)    │
                        │   └── Page Editor                      │
                        │         ├── Emoji icon (clickable)     │
                        │         ├── Title (auto-saved)         │
                        │         └── Blocks (text / h1 / ✓)    │
                        └───────────────────────────────────────┘
```

---

## Customization System

The Customize Panel (accessible via `⋮` in the topbar) applies live styling to the editor and persists settings in `localStorage`:

| Setting     | Options                                                          | `localStorage` Key |
|-------------|------------------------------------------------------------------|--------------------|
| Font Size   | Small (`0.875rem`), Default (`1rem`), Large (`1.2rem`)           | `cp-size`          |
| Font Family | Serif (Cormorant Garamond), Sans (Inter), Mono (JetBrains Mono)  | `cp-font`          |
| Background  | none, grid, dots, lines, crosshatch, waves, amber, night         | `cp-bg`            |

- **Background** is applied to `#editor-scroll` (full-width container) for edge-to-edge coverage
- **Font & Size** are applied to `#blocks-container` and `#page-title-editor`
- A `MutationObserver` on `#blocks-container` re-applies font preferences whenever a new page is loaded
- All preferences survive page refresh and browser restarts

---

## Known Limitations & Roadmap

### Current Limitations
- No drag-and-drop between sections (pages cannot be moved between sections via drag)
- No section reordering UI (the `position` DB column exists but has no frontend control)
- Client-side search only (title filtering in JavaScript, no server-side full-text search)
- No file/image upload support
- Single-user per session (no real-time collaboration)

### Roadmap
- [ ] Drag-and-drop pages between sections
- [ ] Section reordering via drag handle
- [ ] Image block type (file upload)
- [ ] Page cover image / banner
- [ ] Full-text server-side search
- [ ] Page sharing / public read-only links
- [ ] Export page as Markdown or PDF
- [ ] Mobile-responsive sidebar (drawer overlay)
- [ ] Keyboard shortcuts (Ctrl+K search, Ctrl+N new page, Ctrl+S force save)

---

*Built with care using plain PHP, Vanilla JS, and a lot of attention to detail.*
