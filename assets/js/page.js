/**
 * assets/js/page.js  (v5 — Navigation + Tabs + Search + Home)
 *
 * PageManager — handles:
 *  ✓ Page list loading & sidebar tree rendering
 *  ✓ Tab bar management (open/switch/close tabs)
 *  ✓ Home view with real pages + workspace data
 *  ✓ Sidebar search (immediate filter by title)
 *  ✓ Page creation / deletion / title updates
 *
 * Depends on: App, apiGet, apiPost, showToast, Editor (editor.js)
 */

const PageManager = (() => {

  let pages = [];   // flat array of all pages in the workspace
  let sections = [];   // flat array of all sections
  let activeId = null; // currently open page id
  let openTabs = [];   // array of { id, title, icon, wsName, parent }

  // ============================================================
  // LOAD pages for a workspace and render sidebar + home
  // ============================================================
  async function loadPages(workspaceId) {
    pages = [];
    sections = [];
    activeId = null;

    const [pageData, sectionData] = await Promise.all([
      apiGet(`/noteon/api/page.php?action=list&workspace_id=${workspaceId}`),
      apiGet(`/noteon/api/section.php?action=list&workspace_id=${workspaceId}`)
    ]);

    if (pageData && pageData.pages) {
      pages = pageData.pages;
    }

    if (sectionData && sectionData.sections) {
      sections = sectionData.sections;
    }

    renderSidebar();
    renderHome();

    // Set home button as active initially (we start on home)
    document.getElementById('btn-home')?.classList.add('active');
    document.getElementById('tab-home')?.classList.add('active');
  }

  // ============================================================
  // SIDEBAR TREE
  // ============================================================
  function renderSidebar(filterQuery = '') {
    const container = document.getElementById('sidebar-pages');
    if (!container) return;
    container.innerHTML = '';

    const query = (filterQuery || '').toLowerCase().trim();
    const filtered = query
      ? pages.filter(p => (p.title || 'Untitled').toLowerCase().includes(query))
      : pages;

    // Display "No pages yet" only if there are absolutely no sections and no pages
    if (filtered.length === 0 && sections.length === 0) {
      container.innerHTML = `
        <div style="color:#3f3f46;cursor:default;font-size:0.8125rem;
                    padding:0.4rem 0.75rem;pointer-events:none;">
          ${query ? 'No pages match "' + escHtml(query) + '"' : 'No pages yet'}
        </div>`;
      return;
    }

    // Define helper to render a section
    function renderSection(sectionId, sectionName) {
      const sectionPages = query
        ? filtered.filter(p => String(p.section_id || null) === String(sectionId || null) && (!p.parent_id || String(p.parent_id) === '0'))
        : pages.filter(p => String(p.section_id || null) === String(sectionId || null) && (!p.parent_id || String(p.parent_id) === '0'));

      if (!query && sectionId === null && sectionPages.length === 0 && sections.length > 0) return null;

      const groupEl = document.createElement('div');
      groupEl.className = 'sidebar-group';
      if (sectionId) groupEl.id = `sidebar-group-${sectionId}`;

      const header = document.createElement('div');
      header.className = 'sidebar-group-header open';
      if (sectionId) header.dataset.group = sectionId;

      if (sectionId) {
        header.innerHTML = `
          <svg class="group-toggle-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M9 18l6-6-6-6"/></svg>
          <span class="group-icon" style="margin-right: 0.375rem;">${escHtml(sectionName.icon || '📁')}</span>
          <span class="group-label">${escHtml(sectionName.label)}</span>
          <div class="group-actions">
            <button class="group-action-btn" title="Rename" onclick="event.stopPropagation(); window.PageManager.renameSection('${sectionId}')">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
              </svg>
            </button>
            <button class="group-action-btn danger" title="Delete section" onclick="event.stopPropagation(); window.PageManager.deleteSection('${sectionId}')">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
            <button class="group-action-btn" title="Add page to section" onclick="event.stopPropagation(); window.PageManager.createPage(App.state.workspaceId, null, '${sectionId}')">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            </button>
          </div>
        `;

        header.addEventListener('click', function () {
          const items = this.nextElementSibling;
          const open = this.classList.contains('open');
          this.classList.toggle('open', !open);
          if (items) items.classList.toggle('hidden', open);
        });
      } else {
        // Default section (Uncategorized) header
        header.innerHTML = `
          <svg class="group-toggle-icon" style="opacity:0;" viewBox="0 0 24 24" fill="none"><path d="M9 18l6-6-6-6"/></svg>
          <span class="group-label">Pages</span>
          <div class="group-actions">
            <button class="group-action-btn" title="Add page" onclick="event.stopPropagation(); window.PageManager.createPage(App.state.workspaceId)">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            </button>
          </div>
        `;
      }

      const itemsContainer = document.createElement('div');
      itemsContainer.className = 'sidebar-group-items';

      sectionPages.forEach(page => {
        itemsContainer.appendChild(buildTreeNode(page, 0, query));
      });

      groupEl.appendChild(header);
      groupEl.appendChild(itemsContainer);
      return groupEl;
    }

    // Always render sections first, then uncategorized
    sections.forEach(sec => {
      const el = renderSection(sec.id, { label: sec.name, icon: sec.icon });
      if (el) container.appendChild(el);
    });

    // Uncategorized pages (section_id = null)
    const defaultSec = renderSection(null, { label: 'Pages', icon: '' });
    if (defaultSec) container.appendChild(defaultSec);
  }

  // ============================================================
  // BUILD a sidebar tree node
  // ============================================================
  function buildTreeNode(page, depth, highlight = '') {
    const children = highlight
      ? []
      : pages.filter(p => String(p.parent_id) === String(page.id));

    const wrapper = document.createElement('div');
    wrapper.className = 'page-tree-item';
    wrapper.dataset.pageId = page.id;

    // Row
    const row = document.createElement('div');
    row.className = 'page-tree-row' + (String(page.id) === String(activeId) ? ' active' : '');
    row.style.paddingLeft = `${0.5 + depth * 0.875}rem`;
    row.dataset.pageId = page.id;

    // Expand toggle
    const toggle = document.createElement('span');
    toggle.className = 'page-toggle' + (children.length === 0 ? ' invisible' : '');
    toggle.innerHTML = '▶';

    // Icon
    const icon = document.createElement('span');
    icon.className = 'page-icon';
    icon.textContent = page.icon || '📄';

    // Title (with optional highlight)
    const titleSpan = document.createElement('span');
    titleSpan.className = 'page-title-text';
    titleSpan.innerHTML = highlight
      ? highlightText(page.title || 'Untitled', highlight)
      : escHtml(page.title || 'Untitled');

    // ── Action buttons (LARGER: 24×24px SVG icons) ─────────────
    const actions = document.createElement('div');
    actions.className = 'page-actions';

    const addSubBtn = document.createElement('button');
    addSubBtn.className = 'page-action-btn';
    addSubBtn.title = 'Add sub-page';
    addSubBtn.innerHTML = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>`;
    // Preserve section_id of parent if any
    addSubBtn.addEventListener('click', e => { e.stopPropagation(); createPage(App.state.workspaceId, page.id, page.section_id); });

    const deleteBtn = document.createElement('button');
    deleteBtn.className = 'page-action-btn delete';
    deleteBtn.title = 'Delete page';
    deleteBtn.innerHTML = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>`;
    deleteBtn.addEventListener('click', e => { e.stopPropagation(); deletePage(page.id); });

    actions.appendChild(addSubBtn);
    actions.appendChild(deleteBtn);

    row.appendChild(toggle);
    row.appendChild(icon);
    row.appendChild(titleSpan);
    row.appendChild(actions);

    // Children container
    const childContainer = document.createElement('div');
    childContainer.className = 'page-children' + (children.length === 0 ? ' hidden' : '');
    childContainer.dataset.parentId = page.id;
    children.forEach(child => childContainer.appendChild(buildTreeNode(child, depth + 1)));

    let expanded = true;
    toggle.addEventListener('click', e => {
      e.stopPropagation();
      expanded = !expanded;
      childContainer.classList.toggle('hidden', !expanded);
      toggle.classList.toggle('open', expanded);
    });
    if (children.length > 0) toggle.classList.add('open');

    row.addEventListener('click', () => openPage(page.id));

    wrapper.appendChild(row);
    wrapper.appendChild(childContainer);
    return wrapper;
  }

  // ============================================================
  // HOME VIEW — render greeting, recent pages, workspace info
  // ============================================================
  function renderHome() {
    renderHomeRecentPages();
  }

  function renderHomeRecentPages() {
    const recList = document.getElementById('home-recent-list');
    const allList = document.getElementById('home-all-list');
    const emptyEl = document.getElementById('home-recent-empty');
    const allSec = document.getElementById('home-all-section');

    if (!recList) return;

    recList.innerHTML = '';
    if (allList) allList.innerHTML = '';

    if (pages.length === 0) {
      if (emptyEl) { emptyEl.style.display = 'block'; recList.appendChild(emptyEl); }
      if (allSec) allSec.style.display = 'none';
      return;
    }

    if (emptyEl) emptyEl.style.display = 'none';

    // Most recent first (reverse array order = newest last from API → reverse gives newest first)
    const sorted = [...pages].reverse();
    const recent = sorted.slice(0, 6);
    recent.forEach(page => recList.appendChild(buildHomePageRow(page)));

    // "All pages" section if > 6
    if (allSec) allSec.style.display = pages.length > 6 ? 'block' : 'none';
    if (allList) sorted.forEach(page => allList.appendChild(buildHomePageRow(page)));
  }

  function buildHomePageRow(page) {
    const wsName = document.getElementById('ws-name-display')?.textContent || 'Workspace';
    const parent = page.parent_id
      ? pages.find(p => String(p.id) === String(page.parent_id))
      : null;
    const pathText = parent
      ? `${wsName} / ${parent.title || 'Untitled'}`
      : wsName;

    const row = document.createElement('div');
    row.className = 'home-page-row' + (String(page.id) === String(activeId) ? ' active-page-row' : '');
    row.innerHTML = `
      <div class="home-page-icon">${page.icon || '📄'}</div>
      <div class="home-page-info">
        <div class="home-page-title">${escHtml(page.title || 'Untitled')}</div>
        <div class="home-page-meta">${escHtml(pathText)}</div>
      </div>
      <svg class="home-page-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
        <path d="M9 18l6-6-6-6"/>
      </svg>`;
    row.addEventListener('click', () => openPage(page.id));
    return row;
  }

  // ============================================================
  // SEARCH — immediate filter as user types
  // ============================================================
  function initSearch() {
    const input = document.getElementById('sidebar-search-input');
    const clearBtn = document.getElementById('sidebar-search-clear');
    const box = document.getElementById('sidebar-search-box');
    const results = document.getElementById('sidebar-search-results');
    const body = document.getElementById('sidebar-body');
    if (!input) return;

    function doSearch(q) {
      const query = q.trim().toLowerCase();
      clearBtn?.classList.toggle('visible', q.length > 0);
      box?.classList.toggle('focused', document.activeElement === input);

      if (!query) {
        // Restore normal sidebar
        if (results) results.classList.remove('active');
        if (body) body.style.display = '';
        return;
      }

      // Hide normal sidebar, show search results pane immediately
      if (body) body.style.display = 'none';
      if (results) { results.classList.add('active'); results.innerHTML = ''; }
      if (!results) return;

      const matched = pages.filter(p =>
        (p.title || 'Untitled').toLowerCase().includes(query)
      );

      if (matched.length === 0) {
        results.innerHTML = `
          <div class="search-no-results">
            <div style="font-size:1.5rem; margin-bottom:0.5rem;">🔍</div>
            No pages found for<br><strong style="color:#71717a;">"${escHtml(q)}"</strong>
          </div>`;
        return;
      }

      matched.forEach(page => {
        const parent = page.parent_id ? pages.find(p => String(p.id) === String(page.parent_id)) : null;
        const wsName = document.getElementById('ws-name-display')?.textContent || '';
        const path = parent ? `${wsName} / ${parent.title || 'Untitled'}` : wsName;
        const isActive = String(page.id) === String(activeId);

        const item = document.createElement('div');
        item.className = 'search-result-item' + (isActive ? ' active' : '');
        item.innerHTML = `
          <span class="search-result-icon">${page.icon || '📄'}</span>
          <div class="search-result-info">
            <div class="search-result-title">${highlightText(page.title || 'Untitled', query)}</div>
            <div class="search-result-path">${escHtml(path)}</div>
          </div>`;
        item.addEventListener('click', () => {
          openPage(page.id);
          clearSearch();
        });
        results.appendChild(item);
      });
    }

    function clearSearch() {
      input.value = '';
      clearBtn?.classList.remove('visible');
      box?.classList.remove('focused');
      if (results) results.classList.remove('active');
      if (body) body.style.display = '';
    }

    // Trigger immediately on input — results appear as user types
    input.addEventListener('input', () => doSearch(input.value));
    input.addEventListener('focus', () => {
      box?.classList.add('focused');
      doSearch(input.value); // show results immediately on focus if query exists
    });
    input.addEventListener('blur', () => {
      setTimeout(() => { if (!input.value) box?.classList.remove('focused'); }, 150);
    });
    clearBtn?.addEventListener('click', () => { clearSearch(); input.focus(); });

    // Keyboard navigation
    input.addEventListener('keydown', e => {
      if (e.key === 'Escape') { clearSearch(); input.blur(); }
      if (e.key === 'Enter') {
        const first = results?.querySelector('.search-result-item');
        if (first) first.click();
      }
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        const first = results?.querySelector('.search-result-item');
        if (first) first.focus();
      }
    });
  }

  // ============================================================
  // TABS management
  // ============================================================
  function addOrActivateTab(page) {
    const tabId = String(page.id);
    const wsName = document.getElementById('ws-name-display')?.textContent || '';
    const parent = page.parent_id
      ? pages.find(p => String(p.id) === String(page.parent_id))
      : null;
    const tabData = { id: tabId, title: page.title || 'Untitled', icon: page.icon || '📄', wsName, parent };

    const existingIdx = openTabs.findIndex(t => t.id === tabId);
    if (existingIdx === -1) {
      openTabs.push(tabData);
    } else {
      openTabs[existingIdx] = tabData;
    }
    renderTabs(tabId);
  }

  function renderTabs(activePgId) {
    const container = document.getElementById('tabs-container');
    if (!container) return;

    // Remove all existing page tabs (keep the home tab)
    container.querySelectorAll('.editor-tab:not(#tab-home)').forEach(t => t.remove());

    // Deactivate home tab since we're on a page
    document.getElementById('tab-home')?.classList.remove('active');

    openTabs.forEach(tab => {
      // Full path tooltip: Workspace / [Parent /] PageTitle
      const pathParts = [tab.wsName];
      if (tab.parent) pathParts.push(tab.parent.title || 'Untitled');
      pathParts.push(tab.title);
      const tooltip = pathParts.join(' / ');

      const el = document.createElement('div');
      el.className = 'editor-tab' + (tab.id === activePgId ? ' active' : '');
      el.dataset.tabId = tab.id;
      el.title = tooltip;

      el.innerHTML = `
        <span class="tab-icon">${tab.icon}</span>
        <div class="tab-text-wrap">
          ${tab.parent ? `<span class="tab-breadcrumb">${escHtml(tab.parent.title || 'Untitled')} /</span>` : ''}
          <span class="tab-title">${escHtml(tab.title)}</span>
        </div>
        <button class="tab-close" data-tab-id="${tab.id}" title="Close tab">
          <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
            <path d="M18 6L6 18M6 6l12 12"/>
          </svg>
        </button>`;

      el.addEventListener('click', e => {
        if (e.target.closest('.tab-close')) return;
        openPage(tab.id);
      });
      el.querySelector('.tab-close').addEventListener('click', e => {
        e.stopPropagation();
        closeTab(tab.id);
      });

      container.appendChild(el);

      // Scroll active tab into view
      if (tab.id === activePgId) {
        setTimeout(() => el.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' }), 60);
      }
    });
  }

  function closeTab(tabId) {
    const idx = openTabs.findIndex(t => t.id === String(tabId));
    if (idx === -1) return;
    openTabs.splice(idx, 1);

    if (String(activeId) === String(tabId)) {
      const next = openTabs[idx] || openTabs[idx - 1];
      if (next) {
        openPage(next.id);
      } else {
        activeId = null;
        showHomeView();
      }
    } else {
      renderTabs(String(activeId));
    }
  }

  // ============================================================
  // SHOW HOME VIEW
  // ============================================================
  function showHomeView() {
    activeId = null;

    // Switch display
    const homeView = document.getElementById('home-view');
    const editorArea = document.getElementById('editor-area');
    if (homeView) homeView.style.display = 'block';
    if (editorArea) editorArea.style.display = 'none';

    // Tab state: only home tab active
    document.querySelectorAll('.editor-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-home')?.classList.add('active');

    // Sidebar: only Home nav item active
    document.querySelectorAll('.page-tree-row').forEach(r => r.classList.remove('active'));
    document.querySelectorAll('.sidebar-nav-item').forEach(b => b.classList.remove('active'));
    document.getElementById('btn-home')?.classList.add('active');

    // Hide save indicator
    document.getElementById('save-indicator')?.classList.remove('visible');

    // Sync tabs properly in DOM
    renderTabs(null);

    // Refresh home content
    renderHomeRecentPages();
  }

  // ============================================================
  // OPEN a page (core navigation function)
  // ============================================================
  async function openPage(pageId) {
    activeId = pageId;
    const pgIdStr = String(pageId);

    // ── Sidebar: set active row ──────────────────────────────
    document.querySelectorAll('.page-tree-row').forEach(r => {
      r.classList.toggle('active', String(r.dataset.pageId) === pgIdStr);
    });
    // Home nav item should NOT be active when on a page
    document.querySelectorAll('.sidebar-nav-item').forEach(b => b.classList.remove('active'));
    document.getElementById('btn-home')?.classList.remove('active');

    // ── Show editor area, hide home ──────────────────────────
    const homeView = document.getElementById('home-view');
    const editorArea = document.getElementById('editor-area');
    if (homeView) homeView.style.display = 'none';
    if (editorArea) editorArea.style.display = 'block';

    // Show skeleton, hide page content until data arrives
    const skeleton = document.getElementById('page-skeleton');
    const pageEditor = document.getElementById('page-editor');
    if (skeleton) skeleton.style.display = 'block';
    if (pageEditor) pageEditor.style.display = 'none';

    // ── Fetch page data ──────────────────────────────────────
    const data = await apiGet(`/noteon/api/page.php?action=get&id=${pageId}`);
    if (skeleton) skeleton.style.display = 'none';

    if (!data || !data.page) {
      showToast('Could not load page.', 'error');
      showHomeView();
      return;
    }

    // ── Register / update tab ────────────────────────────────
    addOrActivateTab(data.page);

    // ── Render content in editor ─────────────────────────────
    Editor.renderPage(data.page);

    // ── Defensive: ensure correct display state after render ─
    if (homeView) homeView.style.display = 'none';
    if (editorArea) editorArea.style.display = 'block';
    if (pageEditor) pageEditor.style.display = 'block';
  }

  // ============================================================
  // CREATE a page
  // ============================================================
  async function createPage(workspaceId, parentId = null, sectionId = null) {
    if (!workspaceId) {
      showToast('No workspace selected.', 'error');
      return;
    }

    const data = await apiPost('/noteon/api/page.php?action=create', {
      workspace_id: workspaceId,
      parent_id: parentId,
      section_id: sectionId,
      title: 'Untitled',
    });

    if (!data) { showToast('Failed to create page (network error).', 'error'); return; }
    if (!data.success) { showToast(data.error || 'Failed to create page.', 'error'); return; }

    pages.push(data.page);
    renderSidebar();
    await openPage(data.page.id);

    setTimeout(() => {
      const titleEl = document.getElementById('page-title-editor');
      if (titleEl) {
        titleEl.focus();
        const range = document.createRange();
        range.selectNodeContents(titleEl); range.collapse(false);
        window.getSelection().removeAllRanges();
        window.getSelection().addRange(range);
      }
    }, 80);
  }

  // ============================================================
  // SECTION CRUD (MODAL HOOKS)
  // ============================================================

  let currentAction = null; // 'create' or 'rename'
  let currentSectionId = null;

  function openSectionModal(action, id = null, name = '', icon = '📁') {
    currentAction = action;
    currentSectionId = id;

    document.getElementById('section-modal-title').textContent = action === 'create' ? 'New Section' : 'Rename Section';
    document.getElementById('btn-section-submit').textContent = action === 'create' ? 'Create section' : 'Save section';

    const input = document.getElementById('section-name-input');
    const iconInput = document.getElementById('section-icon-input');

    input.value = name;
    iconInput.value = icon || '📁';

    document.getElementById('modal-section').classList.add('active');
    input.focus();
  }

  function closeSectionModal() {
    document.getElementById('modal-section').classList.remove('active');
    document.getElementById('section-name-input').value = '';
    document.getElementById('section-icon-input').value = '📁';
    document.getElementById('section-emoji-picker').style.display = 'none';
    currentAction = null;
    currentSectionId = null;
  }

  // Emoji picker logic
  const iconInput = document.getElementById('section-icon-input');
  const emojiPicker = document.getElementById('section-emoji-picker');

  iconInput?.addEventListener('click', (e) => {
    e.stopPropagation();
    if (emojiPicker) {
      if (emojiPicker.style.display === 'none') {
        emojiPicker.style.display = 'grid';
      } else {
        emojiPicker.style.display = 'none';
      }
    }
  });

  document.querySelectorAll('.modal-emoji-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      if (iconInput) iconInput.value = btn.textContent.trim();
      if (emojiPicker) emojiPicker.style.display = 'none';
    });
  });

  document.addEventListener('click', (e) => {
    if (emojiPicker && !emojiPicker.contains(e.target) && e.target !== iconInput) {
      emojiPicker.style.display = 'none';
    }
  });

  // Handle modal submit
  document.getElementById('btn-section-submit')?.addEventListener('click', async () => {
    const name = document.getElementById('section-name-input').value.trim();
    const icon = document.getElementById('section-icon-input').value.trim() || '📁';

    if (!name || !App.state.workspaceId) return;

    if (currentAction === 'create') {
      const data = await apiPost('/noteon/api/section.php?action=create', {
        workspace_id: App.state.workspaceId,
        name: name,
        icon: icon
      });
      if (data && data.success) await loadPages(App.state.workspaceId);
    } else if (currentAction === 'rename' && currentSectionId) {
      const data = await apiPost('/noteon/api/section.php?action=rename', {
        id: currentSectionId,
        name: name,
        icon: icon
      });
      if (data && data.success) await loadPages(App.state.workspaceId);
    }

    closeSectionModal();
  });

  // Modal Cancel/Close buttons
  document.getElementById('btn-close-section-modal')?.addEventListener('click', closeSectionModal);

  // Enter key support for modal
  function handleSectionEnter(e) {
    if (e.key === 'Enter') document.getElementById('btn-section-submit').click();
    if (e.key === 'Escape') closeSectionModal();
  }
  document.getElementById('section-name-input')?.addEventListener('keydown', handleSectionEnter);
  document.getElementById('section-icon-input')?.addEventListener('keydown', handleSectionEnter);

  function createSection() {
    if (!App.state.workspaceId) return;
    openSectionModal('create');
  }

  function renameSection(sectionId) {
    if (!App.state.workspaceId) return;
    const section = sections.find(s => s.id === sectionId);
    if (!section) return;
    openSectionModal('rename', sectionId, section.name || 'New Section', section.icon || '📁');
  }

  async function deleteSection(sectionId) {
    if (!App.state.workspaceId) return;

    App.confirm(
      'Delete Section',
      'Are you sure you want to delete this section?<br>Pages inside will become uncategorized.',
      async () => {
        const data = await apiPost('/noteon/api/section.php?action=delete', {
          id: sectionId
        });
        if (data && data.success) {
          await loadPages(App.state.workspaceId);
        }
      }
    );
  }

  // ============================================================
  // DELETE a page
  // ============================================================
  function deletePage(pageId) {
    App.confirm(
      'Delete Page',
      'Are you sure you want to delete this page and all its sub-pages?<br>This cannot be undone.',
      async () => {
        const data = await apiPost('/noteon/api/page.php?action=delete', { id: pageId });
        if (!data || !data.success) { showToast('Failed to delete page.', 'error'); return; }

        const toRemove = new Set();
        collectDescendants(pageId, toRemove);
        pages = pages.filter(p => !toRemove.has(String(p.id)));
        openTabs = openTabs.filter(t => !toRemove.has(String(t.id)));

        if (toRemove.has(String(activeId))) {
          const next = openTabs[0];
          if (next) { openPage(next.id); } else { showHomeView(); }
        } else {
          renderTabs(String(activeId));
        }

        renderSidebar();
        renderHomeRecentPages();
        showToast('Page deleted.', 'success');
      }
    );
  }

  // ============================================================
  // UPDATE local title (called by Editor after auto-save)
  // ============================================================
  function updateLocalTitle(pageId, title) {
    const page = pages.find(p => String(p.id) === String(pageId));
    if (!page) return;

    page.title = title;

    // Update sidebar row text
    const sidebarEl = document.querySelector(`.page-tree-row[data-page-id="${pageId}"] .page-title-text`);
    if (sidebarEl) sidebarEl.innerHTML = escHtml(title);

    // Update open tab
    const tab = openTabs.find(t => t.id === String(pageId));
    if (tab) {
      tab.title = title;
      const tabTitleEl = document.querySelector(`.editor-tab[data-tab-id="${pageId}"] .tab-title`);
      if (tabTitleEl) tabTitleEl.textContent = title;
      const tabEl = document.querySelector(`.editor-tab[data-tab-id="${pageId}"]`);
      if (tabEl) tabEl.title = [tab.wsName, ...(tab.parent ? [tab.parent.title] : []), title].join(' / ');
    }

    // Refresh home page list
    renderHomeRecentPages();
  }

  // ============================================================
  // HELPERS
  // ============================================================
  function collectDescendants(parentId, set) {
    set.add(String(parentId));
    pages.filter(p => String(p.parent_id) === String(parentId))
      .forEach(p => collectDescendants(p.id, set));
  }

  function highlightText(text, query) {
    if (!query) return escHtml(text);
    const re = new RegExp(`(${escRegex(query)})`, 'gi');
    return escHtml(text).replace(re, '<mark>$1</mark>');
  }

  function escHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;')
      .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function escRegex(str) {
    return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  }

  // ============================================================
  // EVENT BINDINGS (run once at module init)
  // ============================================================

  // New Page button (sidebar footer)
  document.getElementById('btn-add-page')?.addEventListener('click', () => {
    if (!App.state.workspaceId) {
      showToast('No workspace loaded yet.', 'error');
      return;
    }
    createPage(App.state.workspaceId);
  });

  document.getElementById('btn-add-group')?.addEventListener('click', () => {
    if (!App.state.workspaceId) {
      showToast('No workspace loaded yet.', 'error');
      return;
    }
    createSection();
  });

  // Home tab and Home sidebar button — SINGLE source of truth
  document.getElementById('tab-home')?.addEventListener('click', showHomeView);
  document.getElementById('btn-home')?.addEventListener('click', showHomeView);

  // Init search (binds input listeners)
  initSearch();

  // ============================================================
  // PUBLIC API
  // ============================================================
  return {
    loadPages,
    openPage,
    createPage,
    deletePage,
    updateLocalTitle,
    showHomeView,
    renderSidebar,
    renderHomeRecentPages,
    createSection,
    renameSection,
    deleteSection,
  };

})();

window.PageManager = PageManager;
