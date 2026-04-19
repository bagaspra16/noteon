/**
 * assets/js/workspace.js
 *
 * WorkspaceManager — handles workspace loading, creation,
 * deletion, and the workspace-switcher dropdown.
 * Depends on: App (app.js)
 */

const WorkspaceManager = (() => {

  let workspaces = [];

  // ------------------------------------------------------------------
  // Init: load all workspaces and set the active one
  // ------------------------------------------------------------------
  async function init() {
    const data = await apiGet('/noteon/api/workspace.php?action=list');
    const nameEl = document.getElementById('ws-name-display');
    
    if (!data) {
      console.error('WorkspaceManager.init: API error');
      if (nameEl) nameEl.textContent = 'Offline?';
      return;
    }
    if (!data.workspaces) {
      if (nameEl) nameEl.textContent = 'Error';
      return;
    }

    workspaces = data.workspaces;

    if (workspaces.length === 0) {
      if (nameEl) nameEl.textContent = 'No Workspace';
      openModal();
      return;
    }

    // Use the first workspace as default (or previously selected)
    const active = workspaces[0];
    setActive(active);
  }

  // ------------------------------------------------------------------
  // Set the currently active workspace and update UI
  // ------------------------------------------------------------------
  function setActive(ws) {
    App.state.workspaceId = ws.id;

    const nameEl = document.getElementById('ws-name-display');
    const avatarEl = document.getElementById('ws-avatar');
    if (nameEl) nameEl.textContent = ws.name;
    if (avatarEl) avatarEl.textContent = ws.name.charAt(0).toUpperCase();

    // PageManager is defined in page.js which loads after workspace.js,
    // so we guard the call to be safe at IIFE execution time.
    if (typeof PageManager !== 'undefined') {
      PageManager.loadPages(ws.id);
    }
  }

  // ------------------------------------------------------------------
  // Open "create workspace" modal
  // ------------------------------------------------------------------
  function openModal() {
    document.getElementById('ws-name-input').value = '';
    clearError();
    document.getElementById('modal-workspace').classList.add('active');
    document.getElementById('ws-name-input').focus();
  }

  function closeModal() {
    document.getElementById('modal-workspace').classList.remove('active');
  }

  function showError(msg) {
    const el = document.getElementById('ws-modal-error');
    el.textContent = msg;
    el.classList.add('show');
  }

  function clearError() {
    const el = document.getElementById('ws-modal-error');
    el.textContent = '';
    el.classList.remove('show');
  }

  // ------------------------------------------------------------------
  // Create a new workspace
  // ------------------------------------------------------------------
  async function create(name) {
    const data = await apiPost('/noteon/api/workspace.php?action=create', { name });

    if (!data || !data.success) {
      showError(data?.error || 'Failed to create workspace.');
      return;
    }

    workspaces.unshift(data.workspace);
    closeModal();
    setActive(data.workspace);
    showToast('Workspace created!', 'success');
  }

  // ------------------------------------------------------------------
  // Render the dropdown list of workspaces
  // ------------------------------------------------------------------
  function renderDropdown() {
    const list = document.getElementById('ws-dropdown-list');
    list.innerHTML = '';

    workspaces.forEach(ws => {
      const isActive = ws.id === App.state.workspaceId;
      const item = document.createElement('div');
      item.style.cssText = [
        'display:flex; align-items:center; gap:4px;',
        'padding:2px 4px; border-radius:8px;',
        'min-width:0; overflow:hidden;',
        isActive ? 'background:rgba(255,171,0,0.08);' : '',
        'transition:background 0.12s ease;',
      ].join('');

      // Main click area (switch to this ws)
      const btn = document.createElement('button');
      btn.style.cssText = [
        'display:flex; align-items:center; gap:8px;',
        'flex:1; min-width:0; overflow:hidden;',
        'padding:6px 4px; border-radius:6px;',
        'border:none; cursor:pointer; text-align:left;',
        'font-family:Inter,sans-serif; font-size:0.8125rem;',
        isActive ? 'background:transparent; color:#FFAB00;' : 'background:transparent; color:#a1a1aa;',
        'transition:background 0.12s ease;',
      ].join('');
      btn.onmouseenter = () => { if (!isActive) btn.style.background = 'rgba(255,255,255,0.05)'; };
      btn.onmouseleave = () => { btn.style.background = 'transparent'; };
      btn.innerHTML = `
        <span style="width:20px;height:20px;background:linear-gradient(135deg,#FFAB00,#FF8C00);border-radius:5px;
              display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;flex-shrink:0;">
          ${ws.name.charAt(0).toUpperCase()}
        </span>
        <span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${ws.name}</span>
        ${isActive ? '<span style="color:#FFAB00;font-size:10px;flex-shrink:0;">✓</span>' : ''}
      `;
      btn.addEventListener('click', () => { setActive(ws); closeDropdown(); });
      item.appendChild(btn);

      // Action buttons (rename + delete)
      const actions = document.createElement('div');
      actions.style.cssText = 'display:flex; gap:2px; flex-shrink:0; opacity:0; transition:opacity 0.1s ease;';
      item.onmouseenter = () => { actions.style.opacity = '1'; };
      item.onmouseleave = () => { actions.style.opacity = '0'; };

      const editBtn = document.createElement('button');
      editBtn.title = 'Rename';
      editBtn.style.cssText = 'width:24px;height:24px;display:flex;align-items:center;justify-content:center;border-radius:5px;border:none;background:transparent;color:#71717a;cursor:pointer;transition:background 0.1s,color 0.1s;';
      editBtn.onmouseenter = () => { editBtn.style.background = 'rgba(255,255,255,0.08)'; editBtn.style.color = '#f4f4f5'; };
      editBtn.onmouseleave = () => { editBtn.style.background = 'transparent'; editBtn.style.color = '#71717a'; };
      editBtn.innerHTML = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>`;
      editBtn.addEventListener('click', (e) => { e.stopPropagation(); closeDropdown(); openRenameModal(ws); });

      const delBtn = document.createElement('button');
      delBtn.title = 'Delete';
      delBtn.style.cssText = 'width:24px;height:24px;display:flex;align-items:center;justify-content:center;border-radius:5px;border:none;background:transparent;color:#71717a;cursor:pointer;transition:background 0.1s,color 0.1s;';
      delBtn.onmouseenter = () => { delBtn.style.background = 'rgba(220,38,38,0.12)'; delBtn.style.color = '#f87171'; };
      delBtn.onmouseleave = () => { delBtn.style.background = 'transparent'; delBtn.style.color = '#71717a'; };
      delBtn.innerHTML = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>`;
      delBtn.addEventListener('click', (e) => { e.stopPropagation(); closeDropdown(); confirmDeleteWorkspace(ws); });

      actions.appendChild(editBtn);
      actions.appendChild(delBtn);
      item.appendChild(actions);
      list.appendChild(item);
    });

    // Separator
    const sep = document.createElement('div');
    sep.style.cssText = 'height:1px; background:rgba(255,255,255,0.06); margin:4px 0;';
    list.appendChild(sep);

    // Add workspace button inside dropdown
    const addBtn = document.createElement('button');
    addBtn.style.cssText = [
      'display:flex; align-items:center; gap:8px;',
      'width:100%; padding:7px 10px; border-radius:7px;',
      'border:none; background:transparent; cursor:pointer;',
      'color:#52525b; font-size:0.75rem; font-family:Inter,sans-serif;',
      'transition: background 0.12s ease, color 0.12s ease;',
    ].join('');
    addBtn.onmouseenter = () => { addBtn.style.background = 'rgba(255,255,255,0.05)'; addBtn.style.color = '#a1a1aa'; };
    addBtn.onmouseleave = () => { addBtn.style.background = 'transparent'; addBtn.style.color = '#52525b'; };
    addBtn.innerHTML = `
      <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
        <path d="M6 1v10M1 6h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
      New workspace
    `;
    addBtn.addEventListener('click', () => { closeDropdown(); openModal(); });
    list.appendChild(addBtn);
  }

  // ------------------------------------------------------------------
  // Dropdown open / close
  // ------------------------------------------------------------------
  let dropdownOpen = false;

  function openDropdown() {
    renderDropdown();
    const dropdown = document.getElementById('ws-dropdown');
    const switcher = document.getElementById('btn-workspace-switcher');
    
    // Safety check
    if (!dropdown || !switcher) return;

    const rect = switcher.getBoundingClientRect();
    const mobileMenuBtn = document.getElementById('btn-mobile-menu');
    const isMobile = (mobileMenuBtn && getComputedStyle(mobileMenuBtn).display !== 'none') || window.innerWidth <= 768;
    
    if (isMobile) {
      // Pin to top of screen with safe margin
      dropdown.style.position = 'fixed';
      dropdown.style.left = '50%';
      dropdown.style.top = '10px';
      dropdown.style.transform = 'translateX(-50%)';
      dropdown.style.width = 'calc(100% - 20px)';
      dropdown.style.maxWidth = '320px';
    } else {
      dropdown.style.position = 'fixed';
      dropdown.style.left = `${rect.left}px`;
      dropdown.style.top = `${rect.bottom + 6}px`;
      dropdown.style.transform = 'none';
      dropdown.style.width = '250px';
    }
    
    dropdown.style.display = 'block';
    dropdownOpen = true;
  }

  function closeDropdown() {
    document.getElementById('ws-dropdown').style.display = 'none';
    dropdownOpen = false;
  }

  // ---- Event bindings -------------------------------------------

  const switcherBtn = document.getElementById('btn-workspace-switcher');
  switcherBtn?.addEventListener('click', (e) => {
    e.stopPropagation();
    // Visual feedback for mobile debugging: flash the button background
    if (window.innerWidth <= 768) {
      switcherBtn.style.background = 'rgba(255, 171, 0, 0.2)';
      setTimeout(() => { switcherBtn.style.background = ''; }, 200);
    }
    dropdownOpen ? closeDropdown() : openDropdown();
  });

  document.getElementById('btn-new-workspace').addEventListener('click', openModal);

  document.getElementById('btn-close-ws-modal').addEventListener('click', closeModal);

  document.getElementById('modal-workspace').addEventListener('click', (e) => {
    if (e.target === e.currentTarget) closeModal();
  });

  document.getElementById('btn-ws-create-submit').addEventListener('click', async () => {
    const name = document.getElementById('ws-name-input').value.trim();
    if (!name) { showError('Please enter a workspace name.'); return; }
    await create(name);
  });

  document.getElementById('ws-name-input').addEventListener('keydown', async (e) => {
    if (e.key === 'Enter') {
      const name = e.target.value.trim();
      if (name) await create(name);
    }
  });

  // ------------------------------------------------------------------
  // Rename workspace modal
  // ------------------------------------------------------------------
  let renameTargetWs = null;

  function openRenameModal(ws) {
    renameTargetWs = ws;
    const input = document.getElementById('ws-rename-input');
    const errEl = document.getElementById('ws-rename-error');
    input.value = ws.name;
    errEl.textContent = '';
    errEl.classList.remove('show');
    document.getElementById('modal-ws-rename').classList.add('active');
    input.focus();
    input.select();
  }

  function closeRenameModal() {
    document.getElementById('modal-ws-rename').classList.remove('active');
    renameTargetWs = null;
  }

  async function submitRename() {
    const name = document.getElementById('ws-rename-input').value.trim();
    const errEl = document.getElementById('ws-rename-error');
    if (!name) { errEl.textContent = 'Please enter a name.'; errEl.classList.add('show'); return; }
    if (!renameTargetWs) return;

    const data = await apiPost('/noteon/api/workspace.php?action=rename', { id: renameTargetWs.id, name });
    if (!data || !data.success) {
      errEl.textContent = data?.error || 'Failed to rename.';
      errEl.classList.add('show');
      return;
    }
    // Update local list
    const ws = workspaces.find(w => w.id === renameTargetWs.id);
    if (ws) ws.name = name;
    // If active, update the header display
    if (App.state.workspaceId === renameTargetWs.id) {
      document.getElementById('ws-name-display').textContent = name;
      document.getElementById('ws-avatar').textContent = name.charAt(0).toUpperCase();
    }
    closeRenameModal();
    showToast('Workspace renamed!', 'success');
  }

  // ------------------------------------------------------------------
  // Delete workspace with confirmation
  // ------------------------------------------------------------------
  function confirmDeleteWorkspace(ws) {
    App.confirm(
      'Delete Workspace',
      `Delete <strong>${ws.name}</strong>?<br>All pages and content inside will be permanently deleted.`,
      async () => {
        const data = await apiPost('/noteon/api/workspace.php?action=delete', { id: ws.id });
        if (!data || !data.success) { showToast('Failed to delete workspace.', 'error'); return; }

        workspaces = workspaces.filter(w => w.id !== ws.id);

        if (App.state.workspaceId === ws.id) {
          if (workspaces.length > 0) {
            setActive(workspaces[0]);
          } else {
            App.state.workspaceId = null;
            openModal();
          }
        }
        showToast('Workspace deleted.', 'success');
      }
    );
  }

  document.getElementById('btn-close-ws-rename')?.addEventListener('click', closeRenameModal);
  document.getElementById('modal-ws-rename')?.addEventListener('click', (e) => { if (e.target === e.currentTarget) closeRenameModal(); });
  document.getElementById('btn-ws-rename-submit')?.addEventListener('click', submitRename);
  document.getElementById('ws-rename-input')?.addEventListener('keydown', (e) => { if (e.key === 'Enter') submitRename(); if (e.key === 'Escape') closeRenameModal(); });

  // Close dropdown when clicking outside
  document.addEventListener('click', () => {
    if (dropdownOpen) closeDropdown();
  });

  // ---- Public API -----------------------------------------------
  return { init, setActive, openModal };

})();
