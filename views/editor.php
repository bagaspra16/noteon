<?php
/**
 * views/editor.php — Main workspace editor v4 (Notion-style)
 */
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: /hagglenote/views/index.php');
  exit;
}
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'You');
$userInitial = strtoupper(substr($userName, 0, 1));
$firstName = explode(' ', $userName)[0];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HaggleNote — Workspace</title>
  <meta name="robots" content="noindex">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400;1,500&display=swap"
    rel="stylesheet">
  <style>
    /* =====================================================================
   RESET & ROOT
   ===================================================================== */
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html,
    body {
      height: 100%;
      overflow: hidden;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: #0a0a0b;
      color: #e4e4e7;
      -webkit-font-smoothing: antialiased;
    }

    /* =====================================================================
   APP SHELL
   ===================================================================== */
    .app-shell {
      display: flex;
      height: 100vh;
      overflow: hidden;
    }

    /* =====================================================================
   SIDEBAR
   ===================================================================== */
    .sidebar {
      width: 260px;
      min-width: 260px;
      height: 100vh;
      background: #111113;
      border-right: 1px solid rgba(255, 255, 255, 0.06);
      display: flex;
      flex-direction: column;
      overflow: hidden;
      transition: width 0.22s cubic-bezier(0.4, 0, 0.2, 1), min-width 0.22s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.18s ease;
      position: relative;
      z-index: 20;
      flex-shrink: 0;
    }

    .sidebar.collapsed {
      width: 0;
      min-width: 0;
      opacity: 0;
      pointer-events: none;
    }

    /* Header */
    .sidebar-header {
      padding: 0.75rem 0.625rem 0.5rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      flex-shrink: 0;
    }

    /* Workspace row */
    .ws-row {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.4rem 0.5rem;
      border-radius: 8px;
      cursor: pointer;
      transition: background 0.15s ease;
    }

    .ws-row:hover {
      background: rgba(255, 255, 255, 0.05);
    }

    .ws-avatar {
      width: 30px;
      height: 30px;
      background: linear-gradient(135deg, #FFAB00 0%, #FF8C00 100%);
      border-radius: 7px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.875rem;
      font-weight: 700;
      color: #fff;
      flex-shrink: 0;
      box-shadow: 0 2px 8px rgba(255, 171, 0, 0.3);
    }

    .ws-info {
      flex: 1;
      min-width: 0;
    }

    .ws-name-text {
      font-size: 0.8125rem;
      font-weight: 600;
      color: #f4f4f5;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      line-height: 1.3;
    }

    .ws-plan-badge {
      font-size: 0.6rem;
      color: #52525b;
      letter-spacing: 0.05em;
      text-transform: uppercase;
    }

    .ws-chevron {
      width: 14px;
      height: 14px;
      color: #52525b;
      flex-shrink: 0;
      transition: transform 0.2s ease;
    }

    .ws-row:hover .ws-chevron {
      color: #a1a1aa;
    }

    /* User row */
    .user-row {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.3rem 0.5rem;
      margin-top: 0.125rem;
      border-radius: 8px;
    }

    .user-avatar {
      width: 22px;
      height: 22px;
      background: #27272a;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.625rem;
      font-weight: 700;
      color: #a1a1aa;
      flex-shrink: 0;
    }

    .user-name-label {
      font-size: 0.75rem;
      color: #71717a;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      flex: 1;
    }

    /* Search bar */
    .sidebar-search-wrap {
      padding: 0.5rem 0.625rem 0;
      flex-shrink: 0;
    }

    .sidebar-search-box {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255, 255, 255, 0.07);
      border-radius: 7px;
      padding: 0.375rem 0.625rem;
      cursor: text;
      transition: border-color 0.15s ease, background 0.15s ease;
    }

    .sidebar-search-box:hover {
      border-color: rgba(255, 255, 255, 0.13);
      background: rgba(255, 255, 255, 0.06);
    }

    .sidebar-search-box.focused {
      border-color: rgba(255, 171, 0, 0.4);
      background: rgba(255, 255, 255, 0.04);
    }

    .sidebar-search-box svg {
      flex-shrink: 0;
      color: #52525b;
    }

    .sidebar-search-box.focused svg {
      color: #FFAB00;
    }

    #sidebar-search-input {
      flex: 1;
      background: transparent;
      border: none;
      outline: none;
      color: #f4f4f5;
      font-size: 0.8125rem;
      font-family: 'Inter', sans-serif;
      min-width: 0;
    }

    #sidebar-search-input::placeholder {
      color: #52525b;
    }

    .sidebar-search-clear {
      background: none;
      border: none;
      color: #52525b;
      cursor: pointer;
      padding: 0;
      display: none;
      font-size: 0.75rem;
      line-height: 1;
    }

    .sidebar-search-clear:hover {
      color: #a1a1aa;
    }

    .sidebar-search-clear.visible {
      display: block;
    }

    /* Search results (replaces sidebar-body during search) */
    .sidebar-search-results {
      flex: 1;
      overflow-y: auto;
      padding: 0.375rem 0.375rem;
      display: none;
    }

    .sidebar-search-results.active {
      display: block;
    }

    .search-result-item {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.4375rem 0.5rem;
      border-radius: 7px;
      cursor: pointer;
      transition: background 0.1s ease;
    }

    .search-result-item:hover {
      background: rgba(255, 255, 255, 0.05);
    }

    .search-result-item.active {
      background: rgba(255, 171, 0, 0.1);
    }

    .search-result-icon {
      font-size: 0.875rem;
      flex-shrink: 0;
    }

    .search-result-info {
      flex: 1;
      min-width: 0;
    }

    .search-result-title {
      font-size: 0.8125rem;
      color: #d4d4d8;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .search-result-title mark {
      background: rgba(255, 171, 0, 0.3);
      color: #FFAB00;
      border-radius: 2px;
      padding: 0 1px;
    }

    .search-result-path {
      font-size: 0.6875rem;
      color: #52525b;
      margin-top: 0.0625rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .search-no-results {
      text-align: center;
      padding: 2rem 1rem;
      color: #3f3f46;
      font-size: 0.8125rem;
    }

    /* Sidebar body */
    .sidebar-body {
      flex: 1;
      overflow-y: auto;
      overflow-x: hidden;
      padding: 0.25rem 0.375rem 0.5rem;
    }

    .sidebar-body::-webkit-scrollbar {
      width: 3px;
    }

    .sidebar-body::-webkit-scrollbar-track {
      background: transparent;
    }

    .sidebar-body::-webkit-scrollbar-thumb {
      background: #27272a;
      border-radius: 2px;
    }

    /* Home + New Page quick nav */
    .sidebar-nav-item {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.375rem 0.5rem;
      border-radius: 7px;
      cursor: pointer;
      transition: background 0.12s ease, color 0.12s ease;
      color: #71717a;
      font-size: 0.8125rem;
      user-select: none;
      border: none;
      background: transparent;
      width: 100%;
      text-align: left;
    }

    .sidebar-nav-item:hover {
      background: rgba(255, 255, 255, 0.05);
      color: #d4d4d8;
    }

    .sidebar-nav-item.active {
      background: rgba(255, 171, 0, 0.1);
      color: #FFAB00;
    }

    .sidebar-nav-item svg {
      flex-shrink: 0;
    }

    /* Divider */
    .sidebar-divider {
      height: 1px;
      background: rgba(255, 255, 255, 0.05);
      margin: 0.25rem 0.5rem;
    }

    /* Section groups */
    .sidebar-group {
      margin-bottom: 0.125rem;
    }

    .sidebar-group-header {
      display: flex;
      align-items: center;
      gap: 0.25rem;
      padding: 0.3125rem 0.5rem;
      border-radius: 6px;
      cursor: pointer;
      user-select: none;
      transition: background 0.12s ease;
    }

    .sidebar-group-header:hover {
      background: rgba(255, 255, 255, 0.04);
    }

    .sidebar-group-header:hover .group-actions {
      opacity: 1;
    }

    .group-toggle-icon {
      width: 14px;
      height: 14px;
      color: #52525b;
      flex-shrink: 0;
      transition: transform 0.18s ease;
    }

    .sidebar-group-header.open .group-toggle-icon {
      transform: rotate(90deg);
    }

    .group-icon {
      font-size: 0.85rem;
      flex-shrink: 0;
      margin-right: 0.375rem;
      line-height: 1;
    }

    .group-label {
      font-size: 0.6875rem;
      font-weight: 600;
      color: #52525b;
      text-transform: uppercase;
      letter-spacing: 0.07em;
      flex: 1;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .sidebar-group-header:hover .group-label {
      color: #a1a1aa;
    }

    .group-actions {
      display: flex;
      align-items: center;
      gap: 2px;
      opacity: 0;
      transition: opacity 0.12s ease;
      flex-shrink: 0;
    }

    /* Always show on mobile / touch */
    @media (pointer: coarse) {
      .group-actions {
        opacity: 1;
      }
    }

    .group-action-btn {
      width: 22px;
      height: 22px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 5px;
      border: none;
      background: transparent;
      color: #71717a;
      cursor: pointer;
      padding: 0;
      transition: background 0.1s ease, color 0.1s ease;
    }

    .group-action-btn:hover {
      background: rgba(255, 255, 255, 0.1);
      color: #f4f4f5;
    }

    .group-action-btn.danger:hover {
      background: rgba(220, 38, 38, 0.15);
      color: #f87171;
    }

    .sidebar-group-items {
      padding-left: 0.25rem;
    }

    .sidebar-group-items.hidden {
      display: none;
    }

    /* Page tree nodes */
    .page-tree-item {
      position: relative;
    }

    .page-tree-row {
      display: flex;
      align-items: center;
      gap: 0.25rem;
      padding: 0.3125rem 0.5rem;
      border-radius: 6px;
      cursor: pointer;
      transition: background 0.1s ease, color 0.1s ease;
      color: #71717a;
      font-size: 0.8125rem;
      min-height: 32px;
      user-select: none;
      position: relative;
    }

    .page-tree-row:hover {
      background: rgba(255, 255, 255, 0.04);
      color: #d4d4d8;
    }

    .page-tree-row:hover .page-actions {
      opacity: 1;
    }

    .page-tree-row.active {
      background: rgba(255, 171, 0, 0.1);
      color: #FFAB00;
    }

    .page-tree-row.active::before {
      content: '';
      position: absolute;
      left: 0;
      top: 20%;
      bottom: 20%;
      width: 2px;
      background: #FFAB00;
      border-radius: 2px;
    }

    .page-toggle {
      width: 16px;
      height: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      border-radius: 3px;
      font-size: 0.5rem;
      color: #3f3f46;
      transition: transform 0.15s ease, color 0.1s ease;
    }

    .page-toggle:hover {
      color: #71717a;
    }

    .page-toggle.open {
      transform: rotate(90deg);
    }

    .page-toggle.invisible {
      visibility: hidden;
    }

    .page-icon {
      font-size: 0.9rem;
      flex-shrink: 0;
      line-height: 1;
    }

    .page-title-text {
      flex: 1;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      line-height: 1.4;
      font-size: 0.8125rem;
    }

    /* PAGE ACTION BUTTONS — larger & more visible */
    .page-actions {
      display: flex;
      align-items: center;
      gap: 2px;
      opacity: 0.35;
      transition: opacity 0.15s ease;
      flex-shrink: 0;
    }

    .page-tree-row:hover .page-actions {
      opacity: 1;
    }

    .page-action-btn {
      width: 24px;
      height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 5px;
      border: none;
      background: transparent;
      color: #71717a;
      cursor: pointer;
      font-size: 0.8125rem;
      padding: 0;
      transition: background 0.1s ease, color 0.1s ease;
    }

    .page-action-btn:hover {
      background: rgba(255, 255, 255, 0.1);
      color: #f4f4f5;
    }

    .page-action-btn.delete:hover {
      background: rgba(220, 38, 38, 0.15);
      color: #f87171;
    }

    .page-children {
      padding-left: 0.75rem;
    }

    .page-children.hidden {
      display: none;
    }

    /* Sidebar quick actions */
    .sidebar-quick-actions {
      padding: 0.25rem 0.375rem 0;
      flex-shrink: 0;
    }

    /* Sidebar footer */
    .sidebar-footer {
      padding: 0.5rem 0.375rem 0.625rem;
      border-top: 1px solid rgba(255, 255, 255, 0.05);
      flex-shrink: 0;
    }

    .btn-logout {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      width: 100%;
      padding: 0.4rem 0.5rem;
      border-radius: 6px;
      border: none;
      background: transparent;
      color: #52525b;
      font-size: 0.8125rem;
      cursor: pointer;
      font-family: 'Inter', sans-serif;
      transition: background 0.12s ease, color 0.12s ease;
    }

    .btn-logout:hover {
      background: rgba(220, 38, 38, 0.08);
      color: #f87171;
    }

    /* =====================================================================
   SIDEBAR TOGGLE BUTTON
   ===================================================================== */
    .sidebar-toggle-btn {
      position: fixed;
      top: 11px;
      left: 268px;
      z-index: 30;
      width: 26px;
      height: 26px;
      background: #1a1a1e;
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: #71717a;
      transition: left 0.22s cubic-bezier(0.4, 0, 0.2, 1), background 0.15s ease, color 0.15s ease;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }

    .sidebar-toggle-btn:hover {
      background: #27272a;
      color: #d4d4d8;
    }

    .sidebar-toggle-btn.sidebar-closed {
      left: 12px;
    }

    /* =====================================================================
   EDITOR MAIN
   ===================================================================== */
    .editor-main {
      flex: 1;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      min-width: 0;
    }

    /* =====================================================================
   TOPBAR + TABS
   ===================================================================== */
    .editor-topbar {
      display: flex;
      align-items: stretch;
      background: rgba(10, 10, 11, 0.95);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      height: 48px;
      flex-shrink: 0;
      position: relative;
      z-index: 10;
      overflow: hidden;
    }

    /* =====================================================================
   CUSTOMIZE PANEL
   ===================================================================== */
    #customize-panel {
      position: fixed;
      top: 56px;
      right: 12px;
      width: 272px;
      background: #1c1c1e;
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 12px;
      box-shadow: 0 12px 40px rgba(0,0,0,0.6);
      z-index: 200;
      padding: 1rem;
      display: none;
      flex-direction: column;
      gap: 1.25rem;
      font-family: 'Inter', sans-serif;
      animation: panelIn 0.15s ease;
    }

    #customize-panel.open { display: flex; }

    @keyframes panelIn {
      from { opacity: 0; transform: translateY(-6px) scale(0.98); }
      to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    .cp-section-title {
      font-size: 0.6875rem;
      font-weight: 600;
      color: #52525b;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      margin-bottom: 0.5rem;
    }

    /* Font size buttons */
    .cp-font-size-row {
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .cp-font-size-btn {
      flex: 1;
      padding: 6px 0;
      border-radius: 7px;
      border: 1px solid rgba(255,255,255,0.08);
      background: transparent;
      color: #71717a;
      cursor: pointer;
      font-family: 'Inter', sans-serif;
      transition: all 0.12s ease;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 2px;
    }

    .cp-font-size-btn:hover { background: rgba(255,255,255,0.05); color: #a1a1aa; }
    .cp-font-size-btn.active { border-color: #FFAB00; background: rgba(255,171,0,0.1); color: #FFAB00; }

    .cp-font-size-btn .fs-label { font-size: 0.6rem; letter-spacing: 0.05em; text-transform: uppercase; }
    .cp-font-size-btn .fs-preview {
      font-family: 'Cormorant Garamond', serif;
      line-height: 1;
    }

    /* Font family options */
    .cp-font-option {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 8px 10px;
      border-radius: 7px;
      border: 1px solid rgba(255,255,255,0.06);
      background: transparent;
      color: #a1a1aa;
      cursor: pointer;
      font-size: 0.8125rem;
      font-family: 'Inter', sans-serif;
      transition: all 0.12s ease;
      width: 100%;
      margin-bottom: 4px;
    }

    .cp-font-option:hover { background: rgba(255,255,255,0.04); color: #d4d4d8; }
    .cp-font-option.active { border-color: #FFAB00; background: rgba(255,171,0,0.08); color: #FFAB00; }

    .cp-font-option .font-swatch {
      font-size: 1.1rem;
      line-height: 1;
      width: 28px;
      flex-shrink: 0;
    }
    .cp-font-option .font-name { font-weight: 500; }
    .cp-font-option .font-desc { font-size: 0.7rem; color: #52525b; margin-top: 1px; }

    /* Background pattern grid */
    .cp-bg-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 6px;
    }

    .cp-bg-option {
      aspect-ratio: 1;
      border-radius: 6px;
      border: 2px solid transparent;
      cursor: pointer;
      overflow: hidden;
      transition: border-color 0.12s ease, transform 0.1s ease;
      position: relative;
    }

    .cp-bg-option:hover { transform: scale(1.06); }
    .cp-bg-option.active { border-color: #FFAB00; }

    .cp-bg-option .bg-preview {
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.6rem;
      color: rgba(255,255,255,0.4);
      letter-spacing: 0.03em;
    }


    /* Tabs container (scrollable) */
    .tabs-container {
      display: flex;
      align-items: stretch;
      flex: 1;
      overflow-x: auto;
      overflow-y: hidden;
      min-width: 0;
      padding-left: 2.75rem;
      /* space for sidebar toggle */
    }

    .tabs-container::-webkit-scrollbar {
      height: 0;
    }

    /* Single tab */
    .editor-tab {
      display: flex;
      align-items: center;
      gap: 0.375rem;
      padding: 0 1rem;
      height: 100%;
      border-right: 1px solid rgba(255, 255, 255, 0.05);
      cursor: pointer;
      user-select: none;
      flex-shrink: 0;
      min-width: 0;
      max-width: 200px;
      position: relative;
      color: #71717a;
      font-size: 0.8125rem;
      transition: color 0.15s ease, background 0.15s ease;
      white-space: nowrap;
    }

    .editor-tab:hover {
      color: #d4d4d8;
      background: rgba(255, 255, 255, 0.03);
    }

    .editor-tab.active {
      color: #f4f4f5;
      background: rgba(255, 255, 255, 0.03);
    }

    .editor-tab.active::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 2px;
      background: #FFAB00;
    }

    .tab-icon {
      font-size: 0.8125rem;
      flex-shrink: 0;
    }

    .tab-text-wrap {
      display: flex;
      flex-direction: column;
      flex: 1;
      min-width: 0;
      justify-content: center;
      line-height: 1.1;
      margin-left: 0.125rem;
    }

    .tab-title {
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      font-size: 0.8125rem;
    }

    .tab-breadcrumb {
      font-size: 0.625rem;
      color: #52525b;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      letter-spacing: 0.02em;
      padding-bottom: 2px;
    }

    .tab-close {
      width: 18px;
      height: 18px;
      flex-shrink: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 4px;
      border: none;
      background: transparent;
      color: #52525b;
      cursor: pointer;
      font-size: 0.75rem;
      padding: 0;
      transition: background 0.1s ease, color 0.1s ease;
      opacity: 0;
    }

    .editor-tab:hover .tab-close,
    .editor-tab.active .tab-close {
      opacity: 1;
    }

    .tab-close:hover {
      background: rgba(255, 255, 255, 0.1);
      color: #f87171;
    }

    /* Home tab (special, no close) */
    .editor-tab.home-tab {
      min-width: 44px;
      max-width: 44px;
      padding: 0;
      justify-content: center;
    }

    /* New tab button */
    .tab-new-btn {
      width: 42px;
      height: 100%;
      flex-shrink: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      border: none;
      background: transparent;
      color: #3f3f46;
      cursor: pointer;
      font-size: 1rem;
      transition: color 0.15s ease, background 0.15s ease;
      border-right: 1px solid rgba(255, 255, 255, 0.04);
    }

    .tab-new-btn:hover {
      color: #FFAB00;
      background: rgba(255, 171, 0, 0.05);
    }

    /* Topbar right actions */
    .topbar-right {
      display: flex;
      align-items: center;
      gap: 0.375rem;
      padding: 0 0.875rem;
      flex-shrink: 0;
      border-left: 1px solid rgba(255, 255, 255, 0.05);
    }

    .save-indicator {
      font-size: 0.75rem;
      color: #52525b;
      display: flex;
      align-items: center;
      gap: 0.35rem;
      padding: 0.25rem 0.5rem;
      border-radius: 6px;
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .save-indicator.visible {
      opacity: 1;
    }

    .save-indicator.saved {
      color: #4ade80;
    }

    .save-indicator.error {
      color: #f87171;
    }

    @keyframes spin {
      to {
        transform: rotate(360deg);
      }
    }

    .save-spinner {
      animation: spin 0.8s linear infinite;
    }

    .topbar-icon-btn {
      width: 30px;
      height: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 7px;
      border: 1px solid rgba(255, 255, 255, 0.07);
      background: rgba(255, 255, 255, 0.03);
      color: #71717a;
      cursor: pointer;
      transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    }

    .topbar-icon-btn:hover {
      background: rgba(255, 255, 255, 0.07);
      border-color: rgba(255, 255, 255, 0.13);
      color: #d4d4d8;
    }

    /* =====================================================================
   SCROLLABLE CONTENT
   ===================================================================== */
    .editor-scroll {
      flex: 1;
      overflow-y: auto;
      overflow-x: hidden;
      scroll-behavior: smooth;
    }

    .editor-scroll::-webkit-scrollbar {
      width: 5px;
    }

    .editor-scroll::-webkit-scrollbar-track {
      background: transparent;
    }

    .editor-scroll::-webkit-scrollbar-thumb {
      background: #1e1e22;
      border-radius: 3px;
    }

    .editor-scroll::-webkit-scrollbar-thumb:hover {
      background: #27272a;
    }

    /* =====================================================================
   HOME VIEW (Notion-style)
   ===================================================================== */
    #home-view {
      max-width: 820px;
      margin: 0 auto;
      padding: 3rem 2.5rem 6rem;
      animation: fadeSlideIn 0.3s ease;
    }

    @keyframes fadeSlideIn {
      from {
        opacity: 0;
        transform: translateY(12px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .home-greeting {
      font-family: 'Cormorant Garamond', Georgia, serif;
      font-size: 2.5rem;
      font-weight: 400;
      color: #f4f4f5;
      line-height: 1.2;
      margin-bottom: 0.25rem;
      letter-spacing: -0.02em;
    }

    .home-date {
      font-size: 0.875rem;
      color: #52525b;
      margin-bottom: 2.5rem;
      font-weight: 400;
    }

    /* Quick actions */
    .home-quick-actions {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 0.75rem;
      margin-bottom: 2.5rem;
    }

    @media (max-width: 640px) {
      .home-quick-actions {
        grid-template-columns: 1fr;
      }
    }

    .home-action-card {
      display: flex;
      align-items: center;
      gap: 0.875rem;
      padding: 1rem 1.125rem;
      background: #17171a;
      border: 1px solid rgba(255, 255, 255, 0.07);
      border-radius: 10px;
      cursor: pointer;
      transition: border-color 0.2s ease, background 0.2s ease, transform 0.15s ease;
    }

    .home-action-card:hover {
      border-color: rgba(255, 171, 0, 0.3);
      background: rgba(255, 171, 0, 0.04);
      transform: translateY(-1px);
    }

    .home-action-icon {
      width: 38px;
      height: 38px;
      border-radius: 9px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.125rem;
      flex-shrink: 0;
    }

    .home-action-icon.amber {
      background: rgba(255, 171, 0, 0.15);
    }

    .home-action-icon.blue {
      background: rgba(99, 102, 241, 0.15);
    }

    .home-action-icon.green {
      background: rgba(34, 197, 94, 0.12);
    }

    .home-action-title {
      font-size: 0.875rem;
      font-weight: 600;
      color: #d4d4d8;
      line-height: 1.3;
    }

    .home-action-desc {
      font-size: 0.75rem;
      color: #52525b;
      margin-top: 0.125rem;
    }

    /* Section heading */
    .home-section-heading {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 0.75rem;
    }

    .home-section-title {
      font-size: 0.75rem;
      font-weight: 600;
      color: #52525b;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }

    /* Recent pages */
    .home-recent-list {
      display: flex;
      flex-direction: column;
      gap: 0.25rem;
      margin-bottom: 2.5rem;
    }

    .home-recent-empty {
      padding: 2rem 1.5rem;
      text-align: center;
      background: #17171a;
      border: 1px dashed rgba(255, 255, 255, 0.07);
      border-radius: 10px;
      color: #3f3f46;
      font-size: 0.875rem;
    }

    .home-page-row {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.6875rem 1rem;
      border-radius: 8px;
      cursor: pointer;
      transition: background 0.1s ease;
      border: 1px solid transparent;
    }

    .home-page-row:hover {
      background: #17171a;
      border-color: rgba(255, 255, 255, 0.06);
    }

    .home-page-icon {
      font-size: 1.125rem;
      flex-shrink: 0;
    }

    .home-page-info {
      flex: 1;
      min-width: 0;
    }

    .home-page-title {
      font-size: 0.9375rem;
      font-weight: 500;
      color: #d4d4d8;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .home-page-meta {
      font-size: 0.75rem;
      color: #52525b;
      margin-top: 0.1875rem;
    }

    .home-page-arrow {
      color: #3f3f46;
      flex-shrink: 0;
    }

    .home-page-row:hover .home-page-arrow {
      color: #71717a;
    }

    /* =====================================================================
   PAGE EDITOR CONTENT
   ===================================================================== */
    .editor-content {
      max-width: 740px;
      margin: 0 auto;
      width: 100%;
      padding: 3.5rem 2.5rem 14rem;
      min-height: 100%;
    }

    /* Emoji button */
    .page-emoji-btn {
      font-size: 3.25rem;
      line-height: 1;
      cursor: pointer;
      display: inline-block;
      margin-bottom: 1rem;
      padding: 0.25rem 0.375rem;
      border-radius: 8px;
      transition: transform 0.15s ease, background 0.15s ease;
      border: none;
      background: transparent;
    }

    .page-emoji-btn:hover {
      background: rgba(255, 255, 255, 0.06);
      transform: scale(1.05);
    }

    /* Page title */
    .page-title-editor {
      font-family: 'Cormorant Garamond', Georgia, serif;
      font-size: 3rem;
      font-weight: 400;
      color: #FFFFFF;
      letter-spacing: -0.025em;
      border: none;
      background: transparent;
      outline: none;
      width: 100%;
      padding: 0;
      margin-bottom: 0.5rem;
      line-height: 1.1;
      display: block;
      word-break: break-word;
      caret-color: #FFAB00;
    }

    .page-title-editor:empty::before {
      content: attr(data-placeholder);
      color: #52525b;
      pointer-events: none;
    }

    /* Meta row */
    .page-meta {
      font-family: 'Inter', sans-serif;
      font-size: 0.8125rem;
      color: #3f3f46;
      margin-bottom: 2.5rem;
      padding-bottom: 1.5rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.04);
      display: flex;
      align-items: center;
      gap: 0.625rem;
      flex-wrap: wrap;
    }

    .page-meta-sep {
      color: #52525b;
    }

    /* =====================================================================
   BLOCKS
   ===================================================================== */
    .blocks-container {
      display: flex;
      flex-direction: column;
      gap: 0;
    }

    @keyframes blockSlideIn {
      from {
        opacity: 0;
        transform: translateY(5px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .block-item {
      display: flex;
      align-items: flex-start;
      gap: 0.25rem;
      padding: 0.125rem 0;
      border-radius: 6px;
      position: relative;
      transition: background 0.1s ease;
      animation: blockSlideIn 0.18s ease forwards;
    }

    .block-item:hover {
      background: rgba(255, 255, 255, 0.018);
    }

    .block-item:hover .block-handle {
      opacity: 1;
    }

    .block-item:hover .block-menu-btn {
      opacity: 1;
    }

    .block-gutter {
      display: flex;
      flex-direction: row;
      align-items: flex-start;
      gap: 4px;
      flex-shrink: 0;
      padding-top: 0.25rem;
      width: 34px;
      justify-content: flex-end;
      margin-right: 4px;
    }

    .block-handle {
      opacity: 0.35;
      cursor: grab;
      color: #71717a;
      width: 28px;
      height: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 6px;
      transition: opacity 0.15s ease, color 0.15s ease, background 0.15s ease;
      user-select: none;
    }

    .block-handle:hover {
      color: #d4d4d8;
      background: rgba(255, 255, 255, 0.08);
      opacity: 1 !important;
    }

    .block-handle:active {
      cursor: grabbing;
      background: rgba(255, 171, 0, 0.15);
      color: #FFAB00;
    }

    .block-menu-btn {
      opacity: 0.35;
      background: none;
      border: none;
      color: #71717a;
      cursor: pointer;
      width: 24px;
      height: 26px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 4px;
      transition: opacity 0.15s ease, background 0.15s ease, color 0.15s ease;
      flex-shrink: 0;
      padding: 0;
    }

    .block-menu-btn:hover {
      background: rgba(220, 38, 38, 0.15);
      color: #f87171;
      opacity: 1 !important;
    }

    .block-body {
      flex: 1;
      min-width: 0;
      padding: 0;
    }

    /* Text block */
    .block-text {
      font-family: 'Inter', sans-serif;
      color: #c4c4c8;
      font-size: 1.0625rem;
      line-height: 1.85;
      outline: none;
      width: 100%;
      min-height: 1.85em;
      padding: 0.1875rem 0;
      word-break: break-word;
      white-space: pre-wrap;
      caret-color: #FFAB00;
      transition: color 0.1s ease;
    }

    .block-text:focus {
      color: #e8e8ea;
    }

    .block-text:focus:empty::before {
      content: attr(data-ph);
      color: #71717a;
      pointer-events: none;
    }

    /* Heading block */
    .block-heading {
      font-family: 'Inter', sans-serif;
      color: #FFFFFF;
      font-size: 1.625rem;
      font-weight: 700;
      letter-spacing: -0.02em;
      line-height: 1.3;
      outline: none;
      width: 100%;
      min-height: 1.4em;
      padding: 0.5rem 0 0.25rem;
      word-break: break-word;
      white-space: pre-wrap;
      caret-color: #FFAB00;
      margin-top: 0.5rem;
    }

    .block-heading:focus:empty::before {
      content: attr(data-ph);
      color: #71717a;
      pointer-events: none;
    }

    /* Checklist */
    .checklist-block {
      display: flex;
      flex-direction: column;
      gap: 0.25rem;
      padding: 0.125rem 0;
    }

    .checklist-row {
      display: flex;
      align-items: flex-start;
      gap: 0.625rem;
      padding: 0.25rem 0.375rem;
      border-radius: 6px;
      transition: background 0.1s ease;
    }

    .checklist-row:hover {
      background: rgba(255, 255, 255, 0.025);
    }

    .checklist-checkbox {
      appearance: none;
      -webkit-appearance: none;
      width: 18px;
      height: 18px;
      min-width: 18px;
      border: 1.5px solid #3f3f46;
      border-radius: 5px;
      cursor: pointer;
      flex-shrink: 0;
      margin-top: 0.2rem;
      position: relative;
      transition: border-color 0.15s ease, background 0.15s ease;
      background: transparent;
    }

    .checklist-checkbox:hover {
      border-color: #FFAB00;
    }

    .checklist-checkbox:checked {
      background: #FFAB00;
      border-color: #FFAB00;
    }

    .checklist-checkbox:checked::after {
      content: '';
      position: absolute;
      inset: 0;
      background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 10 8'%3E%3Cpath d='M1 4l3 3 5-6' stroke='white' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round' fill='none'/%3E%3C/svg%3E") center/10px no-repeat;
    }

    .checklist-label {
      flex: 1;
      color: #c4c4c8;
      font-size: 1.0625rem;
      line-height: 1.75;
      outline: none;
      min-height: 1.75em;
      word-break: break-word;
      white-space: pre-wrap;
      cursor: text;
      caret-color: #FFAB00;
      transition: color 0.15s ease;
    }

    .checklist-label.checked {
      color: #52525b;
      text-decoration: line-through;
      text-decoration-color: #3f3f46;
    }

    .checklist-label:focus:empty::before {
      content: 'List item…';
      color: #71717a;
      pointer-events: none;
    }

    .btn-delete-item {
      opacity: 0;
      background: none;
      border: none;
      color: #3f3f46;
      cursor: pointer;
      font-size: 0.875rem;
      padding: 0.125rem 0.25rem;
      border-radius: 4px;
      transition: opacity 0.1s ease, color 0.1s ease, background 0.1s ease;
      line-height: 1;
      flex-shrink: 0;
      margin-top: 0.2rem;
    }

    .checklist-row:hover .btn-delete-item {
      opacity: 1;
    }

    .btn-delete-item:hover {
      color: #f87171;
      background: rgba(220, 38, 38, 0.1);
    }

    .btn-add-checklist-item {
      display: flex;
      align-items: center;
      gap: 0.375rem;
      background: none;
      border: none;
      color: #3f3f46;
      font-size: 0.875rem;
      cursor: pointer;
      padding: 0.3125rem 0.375rem;
      border-radius: 5px;
      transition: color 0.1s ease, background 0.1s ease;
      margin-left: 1.75rem;
      margin-top: 0.125rem;
    }

    .btn-add-checklist-item:hover {
      color: #FFAB00;
      background: rgba(255, 171, 0, 0.07);
    }

    /* Add block */
    .add-block-area {
      margin-top: 1.25rem;
      position: relative;
    }

    .btn-add-block {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.5rem 0.875rem;
      border-radius: 8px;
      border: 1px dashed rgba(255, 255, 255, 0.07);
      background: transparent;
      color: #3f3f46;
      font-size: 0.875rem;
      cursor: pointer;
      transition: border-color 0.2s ease, color 0.2s ease, background 0.2s ease;
      font-family: 'Inter', sans-serif;
    }

    .btn-add-block:hover {
      border-color: rgba(255, 171, 0, 0.35);
      color: #FFAB00;
      background: rgba(255, 171, 0, 0.04);
    }

    .block-type-picker {
      position: absolute;
      top: calc(100% + 6px);
      left: 0;
      background: #1e1e22;
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      padding: 0.5rem;
      display: none;
      gap: 0.25rem;
      z-index: 50;
      box-shadow: 0 16px 48px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(0, 0, 0, 0.4);
    }

    .block-type-picker.show {
      display: flex;
    }

    .block-type-option {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.375rem;
      padding: 0.75rem 1rem;
      border-radius: 8px;
      border: none;
      background: transparent;
      color: #71717a;
      cursor: pointer;
      transition: background 0.12s ease, color 0.12s ease, transform 0.1s ease;
      font-size: 0.75rem;
      font-weight: 500;
      min-width: 72px;
    }

    .block-type-option:hover {
      background: rgba(255, 171, 0, 0.1);
      color: #FFAB00;
      transform: translateY(-1px);
    }

    .block-type-option .type-icon {
      font-size: 1.25rem;
    }

    .block-type-option .type-label {
      font-size: 0.6875rem;
    }

    /* =====================================================================
   SLASH MENU
   ===================================================================== */
    #slash-menu,
    #block-options-menu {
      position: fixed;
      background: #1e1e22;
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      padding: 0.375rem;
      z-index: 200;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.65), 0 0 0 1px rgba(0, 0, 0, 0.4);
      min-width: 230px;
      display: none;
    }

    #slash-menu.show,
    #block-options-menu.show {
      display: block;
    }

    .slash-menu-header {
      font-size: 0.625rem;
      font-weight: 600;
      color: #52525b;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      padding: 0.375rem 0.75rem 0.25rem;
    }

    .slash-item {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.5625rem 0.75rem;
      border-radius: 7px;
      border: none;
      background: transparent;
      color: #a1a1aa;
      cursor: pointer;
      transition: background 0.1s ease, color 0.1s ease;
      width: 100%;
      text-align: left;
    }

    .slash-item:hover,
    .slash-item.active {
      background: rgba(255, 171, 0, 0.1);
      color: #FFFFFF;
    }

    .slash-item-icon {
      width: 32px;
      height: 32px;
      background: rgba(255, 255, 255, 0.06);
      border-radius: 7px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1rem;
      flex-shrink: 0;
    }

    .slash-item-info {
      flex: 1;
    }

    .slash-item-name {
      font-size: 0.875rem;
      font-weight: 500;
    }

    .slash-item-desc {
      font-size: 0.75rem;
      color: #52525b;
      margin-top: 0.0625rem;
    }

    /* =====================================================================
   EMPTY STATE
   ===================================================================== */
    .empty-state {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 60vh;
      gap: 1rem;
      text-align: center;
      color: #3f3f46;
      padding: 2rem;
      animation: fadeSlideIn 0.3s ease;
    }

    .empty-state-icon {
      font-size: 3rem;
      filter: grayscale(0.3);
    }

    .empty-state-title {
      font-size: 1.0625rem;
      font-weight: 600;
      color: #52525b;
      letter-spacing: -0.01em;
    }

    .empty-state-subtitle {
      font-size: 0.9375rem;
      max-width: 280px;
      line-height: 1.65;
    }

    .empty-state-action {
      margin-top: 0.5rem;
      padding: 0.5rem 1.375rem;
      background: rgba(255, 171, 0, 0.1);
      border: 1px solid rgba(255, 171, 0, 0.2);
      border-radius: 8px;
      color: #FFAB00;
      font-size: 0.9375rem;
      font-weight: 500;
      cursor: pointer;
      transition: background 0.15s ease;
      font-family: 'Inter', sans-serif;
    }

    .empty-state-action:hover {
      background: rgba(255, 171, 0, 0.18);
    }

    /* =====================================================================
   MODALS
   ===================================================================== */
    .ws-modal {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.7);
      backdrop-filter: blur(6px);
      -webkit-backdrop-filter: blur(6px);
      z-index: 100;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.2s ease;
    }

    .ws-modal.active {
      opacity: 1;
      pointer-events: all;
    }

    .modal-card {
      background: #1c1c1f;
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 16px;
      padding: 2rem;
      width: 100%;
      max-width: 400px;
      box-shadow: 0 24px 80px rgba(0, 0, 0, 0.7), 0 0 0 1px rgba(255, 255, 255, 0.04);
    }

    .modal-card h2 {
      font-size: 1rem;
      font-weight: 600;
      color: #f4f4f5;
      margin: 0 0 1.25rem;
      font-family: 'Inter', sans-serif;
    }

    .modal-input {
      width: 100%;
      background: #111113;
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 8px;
      padding: 0.6875rem 0.875rem;
      color: #FFFFFF;
      font-family: 'Inter', sans-serif;
      font-size: 0.9375rem;
      outline: none;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .modal-input:focus {
      border-color: rgba(255, 171, 0, 0.6);
      box-shadow: 0 0 0 3px rgba(255, 171, 0, 0.12);
    }

    .modal-input::placeholder {
      color: #52525b;
    }

    .btn-primary {
      width: 100%;
      padding: 0.6875rem 1.5rem;
      background: linear-gradient(135deg, #FFAB00, #FF8C00);
      color: #fff;
      font-weight: 600;
      font-size: 0.9375rem;
      border: none;
      border-radius: 9px;
      cursor: pointer;
      transition: opacity 0.2s ease, transform 0.1s ease;
      font-family: 'Inter', sans-serif;
    }

    .btn-primary:hover {
      opacity: 0.92;
    }

    .btn-primary:active {
      transform: scale(0.98);
    }

    .btn-primary:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    .alert-error {
      background: rgba(220, 38, 38, 0.12);
      border: 1px solid rgba(220, 38, 38, 0.3);
      color: #fca5a5;
      border-radius: 8px;
      padding: 0.625rem 0.875rem;
      font-size: 0.875rem;
      display: none;
    }

    .alert-error.show {
      display: block;
    }

    /* Workspace dropdown */
    #ws-dropdown {
      display: none;
      position: fixed;
      z-index: 90;
      background: #1c1c1f;
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      padding: 0.375rem;
      width: 230px;
      box-shadow: 0 16px 48px rgba(0, 0, 0, 0.6);
    }

    /* =====================================================================
   TOAST
   ===================================================================== */
    .toast {
      position: fixed;
      bottom: 1.5rem;
      right: 1.5rem;
      background: #2C2C2C;
      border: 1px solid rgba(255, 255, 255, 0.09);
      border-radius: 10px;
      padding: 0.75rem 1.25rem;
      font-size: 0.875rem;
      color: #FFFFFF;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
      z-index: 300;
      transform: translateY(2rem);
      opacity: 0;
      transition: transform 0.25s cubic-bezier(.34, 1.56, .64, 1), opacity 0.25s ease;
      pointer-events: none;
    }

    .toast.show {
      transform: translateY(0);
      opacity: 1;
    }

    .toast.success {
      border-color: rgba(34, 197, 94, 0.25);
    }

    .toast.error {
      border-color: rgba(220, 38, 38, 0.25);
    }

    /* =====================================================================
   FOCUS MODE
   ===================================================================== */
    body.focus-mode .sidebar {
      opacity: 0.4;
      transition: opacity 0.3s ease;
    }

    body.focus-mode .sidebar:hover {
      opacity: 1;
    }

    /* =====================================================================
   DRAG-OVER / SKELETON
   ===================================================================== */
    .block-item.drag-over {
      box-shadow: 0 -2px 0 0 #FFAB00;
    }

    .page-skeleton {
      animation: fadeSlideIn 0.3s ease;
    }

    .skeleton-line {
      background: linear-gradient(90deg, #27272a 25%, #3f3f46 50%, #27272a 75%);
      background-size: 200% 100%;
      animation: skeleton-shimmer 1.5s infinite linear;
    }

    @keyframes skeleton-shimmer {
      0% {
        background-position: 200% 0;
      }

      100% {
        background-position: -200% 0;
      }
    }

    /* =====================================================================
   CONFIRM MODAL
   ===================================================================== */
    .ws-modal-content {
      background: #1c1c1f;
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 14px;
      padding: 1.75rem;
      max-width: 360px;
      width: calc(100% - 2rem);
      box-shadow: 0 24px 80px rgba(0, 0, 0, 0.7);
    }

    .ws-btn-cancel,
    .ws-btn-submit {
      padding: 0.5rem 1.125rem;
      border-radius: 8px;
      border: none;
      font-size: 0.875rem;
      font-weight: 500;
      cursor: pointer;
      transition: opacity 0.15s ease;
      font-family: 'Inter', sans-serif;
    }

    .ws-btn-cancel {
      background: rgba(255, 255, 255, 0.07);
      color: #a1a1aa;
    }

    .ws-btn-cancel:hover {
      background: rgba(255, 255, 255, 0.12);
    }

    .ws-btn-submit {
      background: #ef4444;
      color: #fff;
    }

    .ws-btn-submit:hover {
      opacity: 0.88;
    }

    /* Inline edit */
    .inline-edit-input {
      font-size: inherit;
      font-weight: inherit;
      font-family: inherit;
      color: #f4f4f5;
      background: rgba(255, 171, 0, 0.08);
      border: 1px solid rgba(255, 171, 0, 0.4);
      border-radius: 4px;
      padding: 0 0.25rem;
      outline: none;
      width: 100%;
      min-width: 0;
    }

    /* =====================================================================
   RESPONSIVE
   ===================================================================== */
    @media (max-width: 768px) {
      .sidebar {
        width: 0;
        min-width: 0;
        opacity: 0;
        pointer-events: none;
      }

      .sidebar-toggle-btn {
        left: 12px;
      }

      .editor-content {
        padding: 2.5rem 1.25rem 8rem;
      }

      .page-title-editor {
        font-size: 2rem;
      }

      .home-quick-actions {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body>

  <!-- Sidebar toggle — always visible -->
  <button class="sidebar-toggle-btn" id="sidebar-toggle-btn" title="Toggle sidebar" aria-label="Toggle sidebar">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
      stroke-linecap="round">
      <rect x="3" y="3" width="18" height="18" rx="2" />
      <path d="M9 3v18" />
    </svg>
  </button>

  <!-- ====================================================================
     APP SHELL
     ==================================================================== -->
  <div class="app-shell" id="app">

    <!-- ==============================================================
       SIDEBAR
       ============================================================== -->
    <aside class="sidebar" id="sidebar">

      <!-- Header -->
      <div class="sidebar-header">
        <div class="ws-row" id="btn-workspace-switcher" title="Switch workspace">
          <div class="ws-avatar" id="ws-avatar">W</div>
          <div class="ws-info">
            <div class="ws-name-text" id="ws-name-display">Loading…</div>
            <div class="ws-plan-badge"></div>
          </div>
          <svg class="ws-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round">
            <path d="M6 9l6 6 6-6" />
          </svg>
        </div>
        <div class="user-row">
          <div class="user-avatar"><?= $userInitial ?></div>
          <span class="user-name-label"><?= $userName ?></span>
        </div>
      </div>

      <!-- Search -->
      <div class="sidebar-search-wrap">
        <div class="sidebar-search-box" id="sidebar-search-box">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round">
            <circle cx="11" cy="11" r="8" />
            <path d="M21 21l-4.35-4.35" />
          </svg>
          <input type="text" id="sidebar-search-input" placeholder="Search pages…" autocomplete="off"
            spellcheck="false">
          <button class="sidebar-search-clear" id="sidebar-search-clear" title="Clear search">&times;</button>
        </div>
      </div>

      <!-- Search results (shown while searching) -->
      <div class="sidebar-search-results" id="sidebar-search-results"></div>

      <!-- Default sidebar navigation -->
      <div class="sidebar-body" id="sidebar-body">

        <!-- Quick Nav -->
        <div style="margin: 0.375rem 0 0.125rem;">
          <button class="sidebar-nav-item" id="btn-home" title="Home">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
              <polyline points="9 22 9 12 15 12 15 22" />
            </svg>
            Home
          </button>
        </div>

        <div class="sidebar-divider"></div>

        <!-- Groups + Pages Container -->
        <div id="sidebar-pages">
          <!-- Rendered by page.js -->
        </div>

        <!-- Add section -->
        <button class="sidebar-nav-item" id="btn-add-group"
          style="color:#3f3f46; font-size:0.75rem; margin-top:0.25rem;">
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
            stroke-linecap="round">
            <path d="M12 5v14M5 12h14" />
          </svg>
          Add section
        </button>
      </div>

      <!-- Quick add page -->
      <div class="sidebar-quick-actions">
        <button class="sidebar-nav-item" id="btn-add-page" style="color:#71717a;">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round">
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
            <polyline points="14 2 14 8 20 8" />
            <line x1="12" y1="18" x2="12" y2="12" />
            <line x1="9" y1="15" x2="15" y2="15" />
          </svg>
          New Page
        </button>
      </div>

      <!-- Footer -->
      <div class="sidebar-footer">
        <button class="sidebar-nav-item" id="btn-new-workspace"
          style="font-size:0.8125rem; color:#52525b; margin-bottom:0.125rem;">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
            stroke-linecap="round">
            <rect x="3" y="3" width="7" height="7" rx="1" />
            <rect x="14" y="3" width="7" height="7" rx="1" />
            <rect x="3" y="14" width="7" height="7" rx="1" />
            <path d="M17.5 14v6M14.5 17h6" />
          </svg>
          New Workspace
        </button>
        <button class="btn-logout" id="btn-logout">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9" />
          </svg>
          Log out
        </button>
      </div>
    </aside>

    <!-- ==============================================================
       MAIN EDITOR
       ============================================================== -->
    <main class="editor-main" id="editor-main">

      <!-- Topbar with tabs -->
      <header class="editor-topbar" id="editor-topbar">
        <!-- Tabs (scrollable) -->
        <div class="tabs-container" id="tabs-container">
          <!-- Home tab always first -->
          <div class="editor-tab home-tab active" id="tab-home" data-tab="home" title="Home">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
              <polyline points="9 22 9 12 15 12 15 22" />
            </svg>
          </div>
          <!-- Page tabs injected here by JS -->
        </div>

        <!-- Right actions -->
        <div class="topbar-right">
          <span class="save-indicator" id="save-indicator">
            <svg id="save-spinner" class="save-spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor"
              stroke-width="2" style="display:none; width:13px; height:13px;">
              <circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle>
              <path d="M12 2a10 10 0 0 1 10 10"></path>
            </svg>
            <span id="save-text">Saved</span>
          </span>
          <!-- More options -->
          <button class="topbar-icon-btn" title="More options" id="btn-topbar-more">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
              stroke-linecap="round">
              <circle cx="12" cy="5" r="1" />
              <circle cx="12" cy="12" r="1" />
              <circle cx="12" cy="19" r="1" />
            </svg>
          </button>
        </div>
      </header>

      <!-- Customize panel (slides from three-dots button) -->
      <div id="customize-panel">
        <!-- Font Size -->
        <div>
          <div class="cp-section-title">Font Size</div>
          <div class="cp-font-size-row">
            <button class="cp-font-size-btn" data-size="small">
              <span class="fs-preview" style="font-size:1.1rem;">Ag</span>
              <span class="fs-label">Small</span>
            </button>
            <button class="cp-font-size-btn active" data-size="default">
              <span class="fs-preview" style="font-size:1.5rem;">Ag</span>
              <span class="fs-label">Default</span>
            </button>
            <button class="cp-font-size-btn" data-size="large">
              <span class="fs-preview" style="font-size:1.9rem;">Ag</span>
              <span class="fs-label">Large</span>
            </button>
          </div>
        </div>

        <!-- Font Family -->
        <div>
          <div class="cp-section-title">Font</div>
          <button class="cp-font-option active" data-font="serif">
            <span class="font-swatch" style="font-family:'Cormorant Garamond',serif;">Aa</span>
            <span>
              <div class="font-name">Serif</div>
              <div class="font-desc">Elegant &amp; literary</div>
            </span>
          </button>
          <button class="cp-font-option" data-font="sans">
            <span class="font-swatch" style="font-family:'Inter',sans-serif;">Aa</span>
            <span>
              <div class="font-name">Sans-serif</div>
              <div class="font-desc">Clean &amp; modern</div>
            </span>
          </button>
          <button class="cp-font-option" data-font="mono">
            <span class="font-swatch" style="font-family:'JetBrains Mono',monospace;">Aa</span>
            <span>
              <div class="font-name">Mono</div>
              <div class="font-desc">Code-like precision</div>
            </span>
          </button>
        </div>

        <!-- Page Background -->
        <div>
          <div class="cp-section-title">Page Background</div>
          <div class="cp-bg-grid">
            <div class="cp-bg-option active" data-bg="none" title="Clean">
              <div class="bg-preview" style="background:#18181b;">Clean</div>
            </div>
            <div class="cp-bg-option" data-bg="grid" title="Grid">
              <div class="bg-preview" style="background:#18181b; background-image: linear-gradient(rgba(255,255,255,0.06) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,0.06) 1px,transparent 1px); background-size:16px 16px;"></div>
            </div>
            <div class="cp-bg-option" data-bg="dots" title="Dots">
              <div class="bg-preview" style="background:#18181b; background-image:radial-gradient(rgba(255,255,255,0.15) 1px,transparent 1px); background-size:12px 12px;"></div>
            </div>
            <div class="cp-bg-option" data-bg="lines" title="Lines">
              <div class="bg-preview" style="background:#18181b; background-image:linear-gradient(rgba(255,255,255,0.06) 1px,transparent 1px); background-size:100% 18px;"></div>
            </div>
            <div class="cp-bg-option" data-bg="crosshatch" title="Cross">
              <div class="bg-preview" style="background:#18181b; background-image:linear-gradient(45deg,rgba(255,255,255,0.04) 25%,transparent 25%),linear-gradient(-45deg,rgba(255,255,255,0.04) 25%,transparent 25%),linear-gradient(45deg,transparent 75%,rgba(255,255,255,0.04) 75%),linear-gradient(-45deg,transparent 75%,rgba(255,255,255,0.04) 75%); background-size:12px 12px; background-position:0 0,0 6px,6px -6px,-6px 0;"></div>
            </div>
            <div class="cp-bg-option" data-bg="waves" title="Waves">
              <div class="bg-preview" style="background:#18181b; background-image:repeating-linear-gradient(0deg,transparent,transparent 14px,rgba(255,255,255,0.05) 14px,rgba(255,255,255,0.05) 15px),repeating-linear-gradient(90deg,transparent,transparent 14px,rgba(255,255,255,0.03) 14px,rgba(255,255,255,0.03) 15px);"></div>
            </div>
            <div class="cp-bg-option" data-bg="amber" title="Warm">
              <div class="bg-preview" style="background: radial-gradient(ellipse at top left, rgba(255,171,0,0.08) 0%, #18181b 60%);">Warm</div>
            </div>
            <div class="cp-bg-option" data-bg="night" title="Night">
              <div class="bg-preview" style="background: radial-gradient(ellipse at bottom right, rgba(99,102,241,0.1) 0%, #18181b 65%);">Night</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Scrollable writing area -->
      <div class="editor-scroll" id="editor-scroll">

        <!-- ============ HOME VIEW ============ -->
        <div id="home-view" style="display:block;">
          <div style="max-width:820px; margin:0 auto; padding:3rem 2.5rem 6rem;">
            <div class="home-greeting" id="home-greeting">Good morning, <?= $firstName ?>.</div>
            <div class="home-date" id="home-date">—</div>

            <!-- Quick actions -->
            <div class="home-quick-actions" style="margin-top:2rem;">
              <div class="home-action-card" id="home-btn-new-page">
                <div class="home-action-icon amber">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#FFAB00" stroke-width="2"
                    stroke-linecap="round">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
                    <polyline points="14 2 14 8 20 8" />
                    <line x1="12" y1="18" x2="12" y2="12" />
                    <line x1="9" y1="15" x2="15" y2="15" />
                  </svg>
                </div>
                <div>
                  <div class="home-action-title">New Page</div>
                  <div class="home-action-desc">Start writing anything</div>
                </div>
              </div>
              <div class="home-action-card" id="home-btn-new-workspace">
                <div class="home-action-icon blue">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"
                    stroke-linecap="round">
                    <rect x="3" y="3" width="7" height="7" rx="1" />
                    <rect x="14" y="3" width="7" height="7" rx="1" />
                    <rect x="3" y="14" width="7" height="7" rx="1" />
                    <path d="M17.5 14v6M14.5 17h6" />
                  </svg>
                </div>
                <div>
                  <div class="home-action-title">New Workspace</div>
                  <div class="home-action-desc">Create a separate space</div>
                </div>
              </div>
              <div class="home-action-card" id="home-btn-search">
                <div class="home-action-icon green">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2"
                    stroke-linecap="round">
                    <circle cx="11" cy="11" r="8" />
                    <path d="M21 21l-4.35-4.35" />
                  </svg>
                </div>
                <div>
                  <div class="home-action-title">Find Page</div>
                  <div class="home-action-desc">Search across all pages</div>
                </div>
              </div>
            </div>

            <!-- Recent pages -->
            <div style="margin-top:2rem;">
              <div class="home-section-heading">
                <span class="home-section-title">Recent pages</span>
              </div>
              <div class="home-recent-list" id="home-recent-list">
                <div class="home-recent-empty" id="home-recent-empty">
                  No pages yet. Create your first page to get started.
                </div>
              </div>
            </div>

            <!-- All pages -->
            <div style="margin-top:1.5rem;" id="home-all-section">
              <div class="home-section-heading">
                <span class="home-section-title">All pages in workspace</span>
              </div>
              <div class="home-recent-list" id="home-all-list"></div>
            </div>
          </div>
        </div>

        <!-- ============ PAGE / EDITOR ============ -->
        <div id="editor-area" style="display:none;">
          <div class="editor-content" id="editor-content">

            <!-- Skeleton -->
            <div class="page-skeleton" id="page-skeleton" style="display:none; padding-top:3.5rem;">
              <div class="skeleton-line" style="width:52px; height:52px; border-radius:10px; margin-bottom:20px;"></div>
              <div class="skeleton-line" style="height:44px; width:55%; margin-bottom:10px; border-radius:6px;"></div>
              <div class="skeleton-line" style="height:18px; width:130px; margin-bottom:36px; border-radius:4px;"></div>
              <div class="skeleton-line" style="height:22px; width:100%; margin-bottom:14px; border-radius:4px;"></div>
              <div class="skeleton-line" style="height:22px; width:88%; margin-bottom:14px; border-radius:4px;"></div>
              <div class="skeleton-line" style="height:22px; width:94%; margin-bottom:28px; border-radius:4px;"></div>
            </div>

            <!-- Page editor -->
            <div id="page-editor" style="display:none;">
              <div id="page-emoji-btn" class="page-emoji-btn" title="Click to change icon">📄</div>
              <div id="page-title-editor" class="page-title-editor" contenteditable="true" data-placeholder="Untitled"
                role="textbox" aria-label="Page title" spellcheck="false"></div>
              <div class="page-meta" id="page-meta">
                <span id="page-meta-date"></span>
                <span class="page-meta-sep" id="page-meta-sep" style="display:none;">·</span>
                <span id="page-meta-words" style="display:none;"></span>
              </div>
              <div class="blocks-container" id="blocks-container"></div>
              <div class="add-block-area" id="add-block-area">
                <button class="btn-add-block" id="btn-add-block">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round">
                    <path d="M12 5v14M5 12h14" />
                  </svg>
                  Add block
                  <span style="color:#3f3f46; font-size:0.75rem; margin-left:0.25rem;">or / in block</span>
                </button>
                <div class="block-type-picker" id="block-type-picker">
                  <button class="block-type-option" data-type="text">
                    <span class="type-icon">¶</span><span class="type-label">Text</span>
                  </button>
                  <button class="block-type-option" data-type="heading">
                    <span class="type-icon"
                      style="font-weight:800;font-size:1.1rem;font-family:'Inter',sans-serif;">H</span>
                    <span class="type-label">Heading</span>
                  </button>
                  <button class="block-type-option" data-type="checklist">
                    <span class="type-icon">☑</span><span class="type-label">To-do</span>
                  </button>
                </div>
              </div>
            </div><!-- /page-editor -->

          </div><!-- /editor-content -->
        </div><!-- /editor-area -->

      </div><!-- /editor-scroll -->
    </main><!-- /editor-main -->
  </div><!-- /app -->

  <!-- Workspace modal -->
  <div id="modal-workspace" class="ws-modal" role="dialog" aria-modal="true" aria-labelledby="ws-modal-title">
    <div class="modal-card">
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem;">
        <h2 id="ws-modal-title">New Workspace</h2>
        <button id="btn-close-ws-modal"
          style="background:none;border:none;color:#71717a;cursor:pointer;font-size:1.25rem;line-height:1;padding:0;">&times;</button>
      </div>
      <div id="ws-modal-error" class="alert-error" style="margin-bottom:1rem;"></div>
      <label for="ws-name-input"
        style="display:block;font-size:0.8125rem;font-weight:500;color:#a1a1aa;margin-bottom:0.5rem;">Workspace
        name</label>
      <input id="ws-name-input" type="text" class="modal-input" placeholder="e.g. My Projects"
        style="margin-bottom:1.25rem;">
      <button id="btn-ws-create-submit" class="btn-primary">Create workspace</button>
    </div>
  </div>

  <!-- Workspace rename modal -->
  <div id="modal-ws-rename" class="ws-modal" role="dialog" aria-modal="true">
    <div class="modal-card">
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem;">
        <h2>Rename Workspace</h2>
        <button id="btn-close-ws-rename"
          style="background:none;border:none;color:#71717a;cursor:pointer;font-size:1.25rem;line-height:1;padding:0;">&times;</button>
      </div>
      <div id="ws-rename-error" class="alert-error" style="margin-bottom:1rem;"></div>
      <label for="ws-rename-input"
        style="display:block;font-size:0.8125rem;font-weight:500;color:#a1a1aa;margin-bottom:0.5rem;">New name</label>
      <input id="ws-rename-input" type="text" class="modal-input" placeholder="e.g. My Projects"
        style="margin-bottom:1.25rem;">
      <button id="btn-ws-rename-submit" class="btn-primary">Save</button>
    </div>
  </div>

  <!-- Section modal -->
  <div id="modal-section" class="ws-modal" role="dialog" aria-modal="true" aria-labelledby="section-modal-title">
    <div class="modal-card">
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem;">
        <h2 id="section-modal-title">New Section</h2>
        <button id="btn-close-section-modal"
          style="background:none;border:none;color:#71717a;cursor:pointer;font-size:1.25rem;line-height:1;padding:0;">&times;</button>
      </div>
      <div style="display:flex; gap: 1rem; position: relative;">
        <div>
          <label for="section-icon-input"
            style="display:block;font-size:0.8125rem;font-weight:500;color:#a1a1aa;margin-bottom:0.5rem;">Icon</label>
          <div style="position: relative;">
            <input id="section-icon-input" type="text" class="modal-input" placeholder="📁"
              style="margin-bottom:1.25rem; width: 4rem; text-align: center; font-size: 1.25rem; cursor:pointer; user-select:none;" readonly value="📁">
            
            <div id="section-emoji-picker" style="display:none; position:absolute; top:calc(100% - 1rem); left:0; width:180px; max-height:160px; overflow-y:auto; background:#27272a; border: 1px solid rgba(255,255,255,0.1); border-radius:8px; padding:0.5rem; grid-template-columns: repeat(5, 1fr); gap: 0.25rem; z-index:100; box-shadow: 0 4px 12px rgba(0,0,0,0.5);">
              <!-- Study/Work/Notes -->
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">📁</span>
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">📄</span>
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">📝</span>
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">📓</span>
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">📚</span>
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">📌</span>
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">✂️</span>
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">📎</span>
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">💻</span>
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">📱</span>
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">📅</span>
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">⏰</span>
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">💡</span>
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">🧠</span>
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">🚀</span>
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">🎯</span>
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">📈</span>
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">💼</span>
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">🔥</span>
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">✨</span>
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">💵</span>
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">🛒</span>
              
              <!-- Daily Life -->
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">☕️</span>
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">🍔</span>
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">🍎</span>
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">🏠</span>
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">🚗</span>
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">✈️</span>
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">🏖️</span>
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">🎵</span>
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">🎮</span>
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">🏀</span>
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">🏋️</span>
              <span class="modal-emoji-btn" style="cursor:pointer; padding:0.25rem; text-align:center; font-size:1.25rem; transition:transform 0.1s; border-radius:4px;">❤️</span>
            </div>
          </div>
        </div>
        <div style="flex: 1;">
          <label for="section-name-input"
            style="display:block;font-size:0.8125rem;font-weight:500;color:#a1a1aa;margin-bottom:0.5rem;">Section
            name</label>
          <input id="section-name-input" type="text" class="modal-input" placeholder="e.g. Brainstorming"
            style="margin-bottom:1.25rem; width: 100%;">
        </div>
      </div>
      <input id="section-id-input" type="hidden">
      <button id="btn-section-submit" class="btn-primary">Create section</button>
    </div>
  </div>

  <!-- Workspace switcher dropdown -->
  <div id="ws-dropdown">
    <div id="ws-dropdown-list"></div>
  </div>

  <!-- Slash command menu -->
  <div id="slash-menu" role="menu" aria-label="Block type menu">
    <div class="slash-menu-header">Turn into</div>
    <button class="slash-item" data-type="text" role="menuitem">
      <span class="slash-item-icon" style="font-size:0.875rem;color:#a1a1aa;">¶</span>
      <span class="slash-item-info">
        <div class="slash-item-name">Text</div>
        <div class="slash-item-desc">Plain paragraph</div>
      </span>
    </button>
    <button class="slash-item" data-type="heading" role="menuitem">
      <span class="slash-item-icon"
        style="font-weight:800;font-size:0.9rem;font-family:'Inter',sans-serif;color:#a1a1aa;">H1</span>
      <span class="slash-item-info">
        <div class="slash-item-name">Heading</div>
        <div class="slash-item-desc">Large section title</div>
      </span>
    </button>
    <button class="slash-item" data-type="checklist" role="menuitem">
      <span class="slash-item-icon">☑</span>
      <span class="slash-item-info">
        <div class="slash-item-name">To-do list</div>
        <div class="slash-item-desc">Track tasks with checkboxes</div>
      </span>
    </button>
  </div>

  <!-- Block Options menu -->
  <div id="block-options-menu" role="menu" aria-label="Block options menu">
    <div class="slash-menu-header">Block Actions</div>
    <button class="slash-item" id="btn-block-opt-add" role="menuitem">
      <span class="slash-item-icon" style="font-size:1.2rem;color:#a1a1aa;">+</span>
      <span class="slash-item-info">
        <div class="slash-item-name">Add block below</div>
      </span>
    </button>
    <button class="slash-item" id="btn-block-opt-delete" role="menuitem">
      <span class="slash-item-icon" style="color:#f87171;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <polyline points="3 6 5 6 21 6" />
          <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6" />
          <path d="M10 11v6M14 11v6" />
          <path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2" />
        </svg></span>
      <span class="slash-item-info">
        <div class="slash-item-name" style="color:#f87171;">Delete block</div>
      </span>
    </button>
  </div>

  <!-- Confirm modal -->
  <div class="ws-modal" id="modal-confirm">
    <div class="ws-modal-content">
      <h3 id="confirm-title"
        style="margin-top:0;font-size:1rem;color:#f4f4f5;font-weight:600;margin-bottom:0.625rem;font-family:'Inter',sans-serif;">
        Are you sure?</h3>
      <p id="confirm-msg"
        style="color:#71717a;font-size:0.9rem;margin-bottom:0;line-height:1.65;font-family:'Inter',sans-serif;">This
        action cannot be undone.</p>
      <div style="display:flex;align-items:center;justify-content:flex-end;gap:0.625rem;margin-top:1.5rem;">
        <button class="ws-btn-cancel" id="btn-confirm-cancel">Cancel</button>
        <button class="ws-btn-submit" id="btn-confirm-ok">Delete</button>
      </div>
    </div>
  </div>

  <!-- Toast -->
  <div id="toast" class="toast"></div>

  <!-- Scripts -->
  <script src="/hagglenote/assets/js/app.js?v=9"></script>
  <script src="/hagglenote/assets/js/workspace.js?v=9"></script>
  <script src="/hagglenote/assets/js/page.js?v=9"></script>
  <script src="/hagglenote/assets/js/editor.js?v=9"></script>

  <script>
    /* ====================================================================
       SIDEBAR TOGGLE
       ==================================================================== */
    (function () {
      const sidebar = document.getElementById('sidebar');
      const toggleBtn = document.getElementById('sidebar-toggle-btn');
      let collapsed = (localStorage.getItem('hn_sidebar_collapsed') === '1');
      function apply() {
        sidebar.classList.toggle('collapsed', collapsed);
        toggleBtn.classList.toggle('sidebar-closed', collapsed);
      }
      apply();
      toggleBtn.addEventListener('click', () => {
        collapsed = !collapsed;
        apply();
        localStorage.setItem('hn_sidebar_collapsed', collapsed ? '1' : '0');
      });
    })();

    /* ====================================================================
       HOME DATE GREETING
       ==================================================================== */
    (function () {
      const h = new Date().getHours();
      const greet = h < 12 ? 'Good morning' : h < 17 ? 'Good afternoon' : 'Good evening';
      document.getElementById('home-greeting').textContent = greet + ', <?= $firstName ?>.';
      const d = new Date();
      document.getElementById('home-date').textContent = d.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    })();

    /* ====================================================================
       SIDEBAR GROUP TOGGLE
       ==================================================================== */
    document.querySelectorAll('.sidebar-group-header').forEach(header => {
      header.addEventListener('click', function () {
        const items = this.nextElementSibling;
        const isOpen = this.classList.contains('open');
        this.classList.toggle('open', !isOpen);
        if (items) items.classList.toggle('hidden', isOpen);
      });
    });

    /* ====================================================================
       GROUP RENAME
       (handled by PageManager.renameSection in page.js)
       ==================================================================== */

    /* ====================================================================
       WORKSPACE RENAME (double-click)
       ==================================================================== */
    document.getElementById('ws-name-display')?.addEventListener('dblclick', function (e) {
      e.stopPropagation();
      const current = this.textContent;
      const input = document.createElement('input');
      input.type = 'text'; input.value = current; input.className = 'inline-edit-input';
      input.style.cssText = 'font-size:0.8125rem;font-weight:600;';
      this.textContent = ''; this.appendChild(input);
      input.focus(); input.select();
      const finish = async () => {
        const val = input.value.trim() || current;
        this.textContent = val;
        const av = document.getElementById('ws-avatar');
        if (av) av.textContent = val.charAt(0).toUpperCase();
        if (typeof App !== 'undefined' && App.state?.workspaceId) {
          try {
            await fetch('/hagglenote/api/workspace.php?action=rename', {
              method: 'POST', headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ id: App.state.workspaceId, name: val })
            });
          } catch { }
        }
      };
      input.addEventListener('blur', finish);
      input.addEventListener('keydown', ev => {
        if (ev.key === 'Enter') { ev.preventDefault(); input.blur(); }
        if (ev.key === 'Escape') { input.value = current; input.blur(); }
      });
    });

    /* ====================================================================
       ADD SECTION
       Handled by PageManager in page.js
       ==================================================================== */

    /* ====================================================================
       GROUP ADD PAGE button
       ==================================================================== */
    document.getElementById('btn-add-page-to-group')?.addEventListener('click', function (e) {
      e.stopPropagation();
      document.getElementById('btn-add-page')?.click();
    });

    /* ====================================================================
       HOME QUICK ACTION WIRES
       ==================================================================== */
    document.getElementById('home-btn-new-page')?.addEventListener('click', () => document.getElementById('btn-add-page')?.click());
    document.getElementById('home-btn-new-workspace')?.addEventListener('click', () => document.getElementById('btn-new-workspace')?.click());
    document.getElementById('home-btn-search')?.addEventListener('click', () => {
      const input = document.getElementById('sidebar-search-input');
      input?.focus();
    });

    /* NOTE: Home navigation (tab-home / btn-home click) is handled entirely by
       PageManager.showHomeView() in page.js — do NOT add duplicate listeners here. */

    /* ====================================================================
       CUSTOMIZE PANEL — three-dots menu
       ==================================================================== */
    (() => {
      const panel         = document.getElementById('customize-panel');
      const btn           = document.getElementById('btn-topbar-more');
      const editorContent = document.getElementById('editor-content');
      const editorScroll  = document.getElementById('editor-scroll');

      // Saved prefs
      const prefs = {
        size: localStorage.getItem('cp-size') || 'default',
        font: localStorage.getItem('cp-font') || 'serif',
        bg:   localStorage.getItem('cp-bg')   || 'none',
      };

      // Background CSS map (split into parts for safe property assignment)
      const BG = {
        none:       { color: '#18181b', image: 'none',    size: 'auto' },
        grid:       { color: '#18181b', image: 'linear-gradient(rgba(255,255,255,0.05) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,0.05) 1px,transparent 1px)', size: '22px 22px' },
        dots:       { color: '#18181b', image: 'radial-gradient(rgba(255,255,255,0.13) 1px,transparent 1px)', size: '18px 18px' },
        lines:      { color: '#18181b', image: 'linear-gradient(rgba(255,255,255,0.05) 1px,transparent 1px)', size: '100% 26px' },
        crosshatch: { color: '#18181b', image: 'linear-gradient(45deg,rgba(255,255,255,0.03) 25%,transparent 25%),linear-gradient(-45deg,rgba(255,255,255,0.03) 25%,transparent 25%),linear-gradient(45deg,transparent 75%,rgba(255,255,255,0.03) 75%),linear-gradient(-45deg,transparent 75%,rgba(255,255,255,0.03) 75%)', size: '16px 16px' },
        waves:      { color: '#18181b', image: 'repeating-linear-gradient(0deg,transparent,transparent 20px,rgba(255,255,255,0.04) 20px,rgba(255,255,255,0.04) 21px),repeating-linear-gradient(90deg,transparent,transparent 20px,rgba(255,255,255,0.02) 20px,rgba(255,255,255,0.02) 21px)', size: 'auto' },
        amber:      { color: 'transparent', image: 'radial-gradient(ellipse at top left, rgba(255,171,0,0.07) 0%, #18181b 60%)', size: '100% 100%' },
        night:      { color: 'transparent', image: 'radial-gradient(ellipse at bottom right, rgba(99,102,241,0.09) 0%, #18181b 65%)', size: '100% 100%' },
      };

      // Font size scale
      const SIZE = { small: '0.875rem', default: '1rem', large: '1.2rem' };

      // Font family map
      const FONT = {
        serif: "'Cormorant Garamond','Playfair Display',Georgia,serif",
        sans:  "'Inter',system-ui,sans-serif",
        mono:  "'JetBrains Mono','Fira Code',monospace",
      };

      function applyPrefs() {
        // Background — applied to editorScroll (full-width visible area)
        if (editorScroll) {
          const b = BG[prefs.bg] || BG.none;
          editorScroll.style.backgroundColor = b.color;
          editorScroll.style.backgroundImage = b.image;
          editorScroll.style.backgroundSize  = b.size;
        }
        // Font size — applied to editorContent (the writing column)
        if (editorContent) {
          editorContent.style.fontSize = SIZE[prefs.size] || SIZE.default;
        }
        // Font family
        const blocksContainer = document.getElementById('blocks-container');
        const titleEl = document.getElementById('page-title-editor');
        const ff = FONT[prefs.font] || FONT.serif;
        if (blocksContainer) blocksContainer.style.fontFamily = ff;
        if (titleEl) titleEl.style.fontFamily = ff;
      }

      function syncActiveStates() {
        document.querySelectorAll('.cp-font-size-btn').forEach(b => b.classList.toggle('active', b.dataset.size === prefs.size));
        document.querySelectorAll('.cp-font-option').forEach(b => b.classList.toggle('active', b.dataset.font === prefs.font));
        document.querySelectorAll('.cp-bg-option').forEach(b => b.classList.toggle('active', b.dataset.bg === prefs.bg));
      }

      // Apply on load
      applyPrefs();
      syncActiveStates();

      // Toggle panel
      btn?.addEventListener('click', (e) => {
        e.stopPropagation();
        panel.classList.toggle('open');
      });

      // Close when clicking outside
      document.addEventListener('click', (e) => {
        if (panel.classList.contains('open') && !panel.contains(e.target) && e.target !== btn) {
          panel.classList.remove('open');
        }
      });

      // Font size
      document.querySelectorAll('.cp-font-size-btn').forEach(b => {
        b.addEventListener('click', () => {
          prefs.size = b.dataset.size;
          localStorage.setItem('cp-size', prefs.size);
          applyPrefs(); syncActiveStates();
        });
      });

      // Font family
      document.querySelectorAll('.cp-font-option').forEach(b => {
        b.addEventListener('click', () => {
          prefs.font = b.dataset.font;
          localStorage.setItem('cp-font', prefs.font);
          applyPrefs(); syncActiveStates();
        });
      });

      // Background
      document.querySelectorAll('.cp-bg-option').forEach(b => {
        b.addEventListener('click', () => {
          prefs.bg = b.dataset.bg;
          localStorage.setItem('cp-bg', prefs.bg);
          applyPrefs(); syncActiveStates();
        });
      });

      // Re-apply font whenever a page opens (Editor calls renderPage)
      const origRenderPage = Editor.renderPage?.bind(Editor);
      if (origRenderPage) {
        // Patch via MutationObserver watching blocks-container changes
        const observer = new MutationObserver(() => {
          const blocksContainer = document.getElementById('blocks-container');
          const titleEl = document.getElementById('page-title-editor');
          const ff = FONT[prefs.font] || FONT.serif;
          if (blocksContainer) blocksContainer.style.fontFamily = ff;
          if (titleEl) titleEl.style.fontFamily = ff;
        });
        const bc = document.getElementById('blocks-container');
        if (bc) observer.observe(bc, { childList: true });
      }
    })();
  </script>
</body>

</html>