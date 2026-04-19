-- ============================================================
-- HaggleNote — Database Schema
-- Run this file in phpMyAdmin or MySQL CLI before launching.
-- ============================================================

CREATE DATABASE IF NOT EXISTS noteon
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE noteon;

-- ------------------------------------------------------------
-- Users: Stores registered accounts
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100)  NOT NULL,
    email      VARCHAR(100)  NOT NULL UNIQUE,
    password   VARCHAR(255)  NOT NULL,
    created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Workspaces: Each user can own multiple workspaces
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS workspaces (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT           NOT NULL,
    name       VARCHAR(150)  NOT NULL,
    created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Sections: Groupings for pages in the sidebar
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sections (
    id           VARCHAR(50) PRIMARY KEY,
    workspace_id INT           NOT NULL,
    icon         VARCHAR(50)   DEFAULT '📁',
    name         VARCHAR(150)  NOT NULL,
    position     INT           NOT NULL DEFAULT 0,
    FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Pages: Hierarchical pages inside a workspace
--   parent_id = NULL means a top-level page
--   Deleting a parent cascades to all child pages
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pages (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    workspace_id INT           NOT NULL,
    section_id   VARCHAR(50)   NULL,
    parent_id    INT           NULL,
    title        VARCHAR(200)  NOT NULL DEFAULT 'Untitled',
    created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP     NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
    FOREIGN KEY (section_id)   REFERENCES sections(id)   ON DELETE SET NULL,
    FOREIGN KEY (parent_id)    REFERENCES pages(id)      ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Blocks: Content units inside a page
--   type     : 'text' | 'heading' | 'checklist'
--   position : Integer ordering within the page (1-based)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS blocks (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    page_id  INT          NOT NULL,
    type     VARCHAR(50)  NOT NULL DEFAULT 'text',
    content  TEXT,
    position INT          NOT NULL DEFAULT 0,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Checklist Items: Individual items inside a checklist block
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS checklist_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    block_id   INT     NOT NULL,
    content    TEXT,
    is_checked BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (block_id) REFERENCES blocks(id) ON DELETE CASCADE
) ENGINE=InnoDB;
