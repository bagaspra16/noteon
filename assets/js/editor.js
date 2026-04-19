/**
 * assets/js/editor.js  (v2 — Premium Writing Experience)
 *
 * Features:
 *  ✓ Slash command menu  ( / )  to insert/convert blocks inline
 *  ✓ Enter  → create new text block below
 *  ✓ Backspace on empty block → delete & focus block above
 *  ✓ 800ms debounced auto-save per block
 *  ✓ Word count live update in meta bar
 *  ✓ Drag-to-reorder blocks
 *  ✓ Custom checkbox styling for checklist blocks
 *  ✓ Enter in checklist label → new item
 *  ✓ Backspace on empty checklist label → remove item
 *  ✓ Focus mode (sidebar dims when typing)
 *
 * Depends on: App, apiPost, apiGet, showToast, setSaveState, debounce (app.js)
 *             PageManager (page.js)
 */

const Editor = (() => {

  let currentPage  = null;
  let dragSrcBlock = null;
  let slashTarget  = null;   // the block element that triggered slash menu
  let slashActive  = false;

  // per-block debounced save functions
  const saveFns = new Map();

  // ================================================================
  // PUBLIC: Show empty state
  // ================================================================
  function showEmptyState() {
    currentPage      = null;
    App.state.pageId = null;

    // Show home view, hide editor area
    const homeView   = document.getElementById('home-view');
    const editorArea = document.getElementById('editor-area');
    if (homeView)   homeView.style.display   = 'block';
    if (editorArea) editorArea.style.display = 'none';
    document.getElementById('page-editor')?.classList.remove('active');
    // Activate home tab
    document.querySelectorAll('.editor-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-home')?.classList.add('active');
  }

  // ================================================================
  // PUBLIC: Render a page (title + blocks) in the editor
  // ================================================================
  function renderPage(page) {
    currentPage      = page;
    App.state.pageId = page.id;

    // Show editor area, hide home view
    const homeView   = document.getElementById('home-view');
    const editorArea = document.getElementById('editor-area');
    if (homeView)   homeView.style.display   = 'none';
    if (editorArea) editorArea.style.display = 'block';
    document.getElementById('page-editor').style.display = 'block';

    // ---- Title
    const titleEl = document.getElementById('page-title-editor');
    titleEl.textContent = (page.title && page.title !== 'Untitled') ? page.title : '';

    // ---- Meta
    if (page.created_at) {
      const d = new Date(page.created_at);
      document.getElementById('page-meta-date').textContent =
        d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
    }

    updateWordCount();

    // ---- Blocks
    const container = document.getElementById('blocks-container');
    container.innerHTML = '';
    saveFns.clear();

    (page.blocks || []).forEach(block => {
      container.appendChild(buildBlockEl(block));
    });

    // Close any open pickers
    closeBlockTypePicker();
    closeSlashMenu();
    if (typeof closeBlockOptionsMenu === 'function') closeBlockOptionsMenu();
    
    updateAddBlockVisibility();

    // Scroll to top
    const scroll = document.getElementById('editor-scroll');
    if (scroll) scroll.scrollTop = 0;
  }

  // ================================================================
  // Build a block DOM element
  // ================================================================
  function buildBlockEl(block) {
    const wrapper = document.createElement('div');
    wrapper.className       = 'block-item';
    wrapper.dataset.blockId = block.id;
    wrapper.dataset.type    = block.type;
    wrapper.draggable       = true;

    // ---- Gutter (handle only)
    const gutter = document.createElement('div');
    gutter.className = 'block-gutter';

    const handle = document.createElement('span');
    handle.className   = 'block-handle';
    handle.title       = 'Click for options, drag to reorder';
    handle.innerHTML   = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M8 6h.01M16 6h.01M8 12h.01M16 12h.01M8 18h.01M16 18h.01"/></svg>`;
    
    handle.addEventListener('click', (e) => {
      e.stopPropagation();
      openBlockOptionsMenu(wrapper, handle);
    });

    gutter.appendChild(handle);

    // ---- Body
    const body = document.createElement('div');
    body.className = 'block-body';

    if (block.type === 'text')      body.appendChild(buildTextEl(block));
    if (block.type === 'heading')   body.appendChild(buildHeadingEl(block));
    if (block.type === 'checklist') body.appendChild(buildChecklistEl(block));

    wrapper.appendChild(gutter);
    wrapper.appendChild(body);

    bindDragEvents(wrapper);

    return wrapper;
  }

  // ================================================================
  // TEXT block
  // ================================================================
  function buildTextEl(block) {
    const el = document.createElement('div');
    el.className       = 'block-text';
    el.contentEditable = 'true';
    el.spellcheck      = false;
    el.dataset.blockId = block.id;
    el.dataset.ph      = 'Type something… (or / for commands)';
    el.setAttribute('data-ph', 'Type something…  (or / for commands)');

    if (block.content) el.textContent = block.content;

    bindEditableEvents(el, block.id, 'text');
    return el;
  }

  // ================================================================
  // HEADING block
  // ================================================================
  function buildHeadingEl(block) {
    const el = document.createElement('div');
    el.className       = 'block-heading';
    el.contentEditable = 'true';
    el.spellcheck      = false;
    el.dataset.blockId = block.id;
    el.setAttribute('data-ph', 'Heading…');

    if (block.content) el.textContent = block.content;

    bindEditableEvents(el, block.id, 'heading');
    return el;
  }

  // ================================================================
  // CHECKLIST block
  // ================================================================
  function buildChecklistEl(block) {
    const container = document.createElement('div');
    container.className       = 'checklist-block';
    container.dataset.blockId = block.id;

    (block.items || []).forEach(item => {
      container.appendChild(buildChecklistRow(item, block.id));
    });

    const addBtn = document.createElement('button');
    addBtn.className   = 'btn-add-checklist-item';
    addBtn.innerHTML   = `<span style="font-size:0.8125rem;">+</span> Add item`;
    addBtn.addEventListener('click', () =>
      addChecklistItem(block.id, container, addBtn)
    );

    container.appendChild(addBtn);
    return container;
  }

  function buildChecklistRow(item, blockId) {
    const row = document.createElement('div');
    row.className      = 'checklist-row';
    row.dataset.itemId = item.id;

    const cbx = document.createElement('input');
    cbx.type      = 'checkbox';
    cbx.className = 'checklist-checkbox';
    cbx.id        = `ci-${item.id}`;
    cbx.checked   = item.is_checked == 1 || item.is_checked === true;

    const lbl = document.createElement('div');
    lbl.className       = 'checklist-label' + (cbx.checked ? ' checked' : '');
    lbl.contentEditable = 'true';
    lbl.spellcheck      = false;
    lbl.dataset.itemId  = item.id;
    if (item.content) lbl.textContent = item.content;

    const delBtn = document.createElement('button');
    delBtn.className   = 'btn-delete-item';
    delBtn.title       = 'Remove';
    delBtn.textContent = '×';
    delBtn.addEventListener('click', () => removeChecklistItem(item.id, row));

    // Toggle checkbox
    cbx.addEventListener('change', async () => {
      lbl.classList.toggle('checked', cbx.checked);
      await apiPost('/hagglenote/api/block.php?action=toggle_item', {
        item_id: item.id, is_checked: cbx.checked,
      });
    });

    // Save label on input
    const saveLabel = debounce(async () => {
      setSaveState('saving');
      await apiPost('/hagglenote/api/block.php?action=update_checklist_item', {
        item_id: item.id, content: lbl.textContent,
      });
      setSaveState('saved');
      updateWordCount();
    }, 800);
    lbl.addEventListener('input', saveLabel);

    // Enter → new item below
    lbl.addEventListener('keydown', async (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        const container = document.querySelector(`.checklist-block[data-block-id="${blockId}"]`);
        const addBtn    = container?.querySelector('.btn-add-checklist-item');
        if (addBtn) await addChecklistItem(blockId, container, addBtn);
      }
      // Backspace on empty → remove item
      if (e.key === 'Backspace' && lbl.textContent === '') {
        e.preventDefault();
        removeChecklistItem(item.id, row);
      }
    });

    row.appendChild(cbx);
    row.appendChild(lbl);
    row.appendChild(delBtn);
    return row;
  }

  // ================================================================
  // Shared events for text / heading contenteditable elements
  // ================================================================
  function bindEditableEvents(el, blockId, type) {

    // ---- Debounced auto-save
    const saveFn = debounce(async () => {
      // Strip slash prefix if left by accident
      const raw = el.textContent;
      setSaveState('saving');
      try {
        await apiPost('/hagglenote/api/block.php?action=update', {
          id: blockId, content: raw,
        });
        setSaveState('saved');
        updateWordCount();
      } catch { setSaveState('error'); }
    }, 800);

    saveFns.set(blockId, saveFn);
    el.addEventListener('input', saveFn);

    // ---- Keyboard shortcuts
    el.addEventListener('keydown', async (e) => {

      // Slash command
      if (e.key === '/' && el.textContent === '') {
        e.preventDefault();
        openSlashMenu(el);
        return;
      }

      // Enter → new text block below
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        closeSlashMenu();
        if (currentPage) {
          const wrapper  = el.closest('.block-item');
          const allBlocks = Array.from(document.querySelectorAll('#blocks-container .block-item'));
          const idx      = allBlocks.indexOf(wrapper);
          await createBlockAtIndex(currentPage.id, 'text', idx + 1);
        }
        return;
      }

      // Backspace on empty block → delete & focus previous
      if (e.key === 'Backspace' && el.textContent === '') {
        e.preventDefault();
        closeSlashMenu();
        const wrapper    = el.closest('.block-item');
        const allBlocks  = Array.from(document.querySelectorAll('#blocks-container .block-item'));
        const idx        = allBlocks.indexOf(wrapper);
        const prevBlock  = allBlocks[idx - 1];

        await deleteBlock(blockId, wrapper, false);

        if (prevBlock) {
          const prevEditable = prevBlock.querySelector('[contenteditable="true"]');
          if (prevEditable) {
            prevEditable.focus();
            moveCursorToEnd(prevEditable);
          }
        } else if (currentPage) {
          // No block above — focus title
          document.getElementById('page-title-editor').focus();
        }
        return;
      }

      // Escape → close slash menu
      if (e.key === 'Escape') {
        closeSlashMenu();
        return;
      }

      // Arrow keys in slash menu
      if (slashActive) {
        const items = Array.from(document.querySelectorAll('#slash-menu .slash-item'));
        const active = document.querySelector('#slash-menu .slash-item.active');
        let idx = items.indexOf(active);

        if (e.key === 'ArrowDown') {
          e.preventDefault();
          idx = (idx + 1) % items.length;
          items.forEach(i => i.classList.remove('active'));
          items[idx].classList.add('active');
          return;
        }
        if (e.key === 'ArrowUp') {
          e.preventDefault();
          idx = (idx - 1 + items.length) % items.length;
          items.forEach(i => i.classList.remove('active'));
          items[idx].classList.add('active');
          return;
        }
        if (e.key === 'Enter' || e.key === 'Tab') {
          e.preventDefault();
          const chosen = document.querySelector('#slash-menu .slash-item.active') || items[0];
          if (chosen) await convertOrCreateBlock(el, chosen.dataset.type);
          return;
        }
      }
    });

    // Track '/' input to open slash menu even mid-sentence
    el.addEventListener('input', () => {
      const text = el.textContent;
      if (text === '/') {
        openSlashMenu(el);
      } else if (!text.startsWith('/') && slashActive) {
        closeSlashMenu();
      }
    });
  }

  // ================================================================
  // Slash command menu: open / close / position
  // ================================================================
  const slashMenu = document.getElementById('slash-menu');

  function openSlashMenu(targetEl) {
    slashTarget = targetEl;
    slashActive = true;

    // Position right below the element
    const rect = targetEl.getBoundingClientRect();
    slashMenu.style.left = `${rect.left}px`;
    slashMenu.style.top  = `${rect.bottom + 4}px`;
    slashMenu.classList.add('show');

    // Reset active item
    document.querySelectorAll('#slash-menu .slash-item').forEach((btn, i) => {
      btn.classList.toggle('active', i === 0);
    });
  }

  function closeSlashMenu() {
    if (!slashActive) return;
    slashActive = false;
    slashTarget = null;
    slashMenu.classList.remove('show');
    document.querySelectorAll('#slash-menu .slash-item').forEach(b => b.classList.remove('active'));
  }

  // Bind slash menu item clicks
  slashMenu.querySelectorAll('.slash-item').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.stopPropagation();
      if (slashTarget) await convertOrCreateBlock(slashTarget, btn.dataset.type);
    });
  });

  // Close slash menu on outside click
  document.addEventListener('click', (e) => {
    if (!slashMenu.contains(e.target)) closeSlashMenu();
  });

  // ================================================================
  // Block Options Menu
  // ================================================================
  const blockOptionsMenu = document.getElementById('block-options-menu');
  let blockOptActive = false;
  let blockOptTargetWrapper = null;

  function openBlockOptionsMenu(wrapper, handleEl) {
    blockOptTargetWrapper = wrapper;
    blockOptActive = true;

    const rect = handleEl.getBoundingClientRect();
    blockOptionsMenu.style.left = `${rect.left}px`;
    blockOptionsMenu.style.top  = `${rect.bottom + 4}px`;
    blockOptionsMenu.classList.add('show');
  }

  function closeBlockOptionsMenu() {
    if (!blockOptActive) return;
    blockOptActive = false;
    blockOptTargetWrapper = null;
    blockOptionsMenu.classList.remove('show');
  }

  document.getElementById('btn-block-opt-add')?.addEventListener('click', async (e) => {
    e.stopPropagation();
    if (!blockOptTargetWrapper || !currentPage) return;
    const allBlocks = Array.from(document.querySelectorAll('#blocks-container .block-item'));
    const pos = allBlocks.indexOf(blockOptTargetWrapper) + 1;
    closeBlockOptionsMenu();
    await createBlockAtIndex(currentPage.id, 'text', pos, true);
  });

  document.getElementById('btn-block-opt-delete')?.addEventListener('click', (e) => {
    e.stopPropagation();
    if (!blockOptTargetWrapper) return;
    const blockId = blockOptTargetWrapper.dataset.blockId;
    const wrapper = blockOptTargetWrapper;
    closeBlockOptionsMenu();
    deleteBlock(blockId, wrapper);
  });

  document.addEventListener('click', (e) => {
    if (blockOptionsMenu && !blockOptionsMenu.contains(e.target) && !e.target.closest('.block-handle')) {
      closeBlockOptionsMenu();
    }
  });
  
  // ================================================================
  // Add Block Area Visibility
  // ================================================================
  function updateAddBlockVisibility() {
    const container = document.getElementById('blocks-container');
    const area = document.getElementById('add-block-area');
    if (area && container) {
      if (container.children.length === 0) {
        area.style.display = 'block';
      } else {
        area.style.display = 'none';
      }
    }
  }

  // ================================================================
  // Convert current block type OR create new block
  // ================================================================
  async function convertOrCreateBlock(targetEl, newType) {
    closeSlashMenu();

    // Clear the slash character from the element
    targetEl.textContent = '';

    const wrapper = targetEl.closest('.block-item');
    if (!wrapper) return;

    const blockId = wrapper.dataset.blockId;
    const oldType = wrapper.dataset.type;

    if (oldType === newType) {
      // Already the right type — just focus
      targetEl.focus();
      return;
    }

    // Ask the server to update the type: we'll delete + recreate at same position
    const allBlocks = Array.from(document.querySelectorAll('#blocks-container .block-item'));
    const pos       = allBlocks.indexOf(wrapper) + 1; // 1-based

    // Delete old block
    await apiPost('/hagglenote/api/block.php?action=delete', { id: blockId });
    wrapper.remove();

    // Create new block of desired type at same position
    if (currentPage) {
      await createBlockAtIndex(currentPage.id, newType, pos - 1, true);
    }
  }

  // ================================================================
  // Create a block at a specific index (0-based) in the container
  // ================================================================
  async function createBlockAtIndex(pageId, type, index, focus = true) {
    const data = await apiPost('/hagglenote/api/block.php?action=create', {
      page_id: pageId, type, content: '',
    });

    if (!data || !data.success) { showToast('Failed to create block.', 'error'); return null; }

    const block = data.block;
    if (type === 'checklist') block.items = [];

    const el  = buildBlockEl(block);
    const container = document.getElementById('blocks-container');
    const siblings  = Array.from(container.children);

    if (index >= siblings.length) {
      container.appendChild(el);
    } else {
      container.insertBefore(el, siblings[index]);
    }

    if (focus) {
      setTimeout(() => {
        const editable = el.querySelector('[contenteditable="true"]');
        if (editable) { editable.focus(); moveCursorToEnd(editable); }
      }, 30);
    }

    updateAddBlockVisibility();
    // Persist new order to server
    await saveBlockOrder();
    return el;
  }

  // ================================================================
  // PUBLIC: Create a block at end (called from Add Block picker)
  // ================================================================
  async function createBlock(pageId, type) {
    const container = document.getElementById('blocks-container');
    await createBlockAtIndex(pageId, type, container.children.length);
  }

  // ================================================================
  // Add checklist item
  // ================================================================
  async function addChecklistItem(blockId, container, addBtn) {
    const data = await apiPost('/hagglenote/api/block.php?action=add_checklist_item', {
      block_id: blockId, content: '',
    });
    if (!data?.success) { showToast('Failed to add item.', 'error'); return; }

    const newRow = buildChecklistRow({ id: data.item_id, content: '', is_checked: false }, blockId);
    container.insertBefore(newRow, addBtn);

    const lbl = newRow.querySelector('.checklist-label');
    if (lbl) lbl.focus();
  }

  // ================================================================
  // Remove a checklist item
  // ================================================================
  async function removeChecklistItem(itemId, rowEl) {
    await apiPost('/hagglenote/api/block.php?action=delete_checklist_item', { item_id: itemId });
    rowEl.remove();
  }

  // ================================================================
  // Delete a block
  // ================================================================
  async function deleteBlock(blockId, wrapperEl, animate = true) {
    await apiPost('/hagglenote/api/block.php?action=delete', { id: blockId });

    if (animate) {
      wrapperEl.style.opacity    = '0';
      wrapperEl.style.transform  = 'translateX(-8px)';
      wrapperEl.style.transition = 'opacity 0.15s ease, transform 0.15s ease';
      await new Promise(r => setTimeout(r, 150));
    }

    wrapperEl.remove();
    updateAddBlockVisibility();
    updateWordCount();
  }

  // ================================================================
  // Persist block order after drag-to-reorder
  // ================================================================
  async function saveBlockOrder() {
    if (!currentPage) return;
    const order = Array.from(
      document.querySelectorAll('#blocks-container .block-item')
    ).map(el => el.dataset.blockId);

    await apiPost('/hagglenote/api/block.php?action=reorder', {
      page_id: currentPage.id, order,
    });
  }

  // ================================================================
  // Word count (runs after any content change)
  // ================================================================
  function updateWordCount() {
    const container = document.getElementById('blocks-container');
    const titleEl   = document.getElementById('page-title-editor');
    if (!container) return;

    let text = (titleEl?.textContent || '') + ' ';
    container.querySelectorAll('[contenteditable]').forEach(el => {
      text += el.textContent + ' ';
    });

    const words = text.trim().split(/\s+/).filter(w => w.length > 0).length;
    const el    = document.getElementById('page-meta-words');
    const sep   = document.getElementById('page-meta-sep');

    if (el && words > 0) {
      el.textContent    = `${words} word${words !== 1 ? 's' : ''}`;
      el.style.display  = 'inline';
      if (sep) sep.style.display = 'inline';
    } else if (el) {
      el.style.display  = 'none';
      if (sep) sep.style.display = 'none';
    }
  }

  // ================================================================
  // Drag-to-reorder
  // ================================================================
  function bindDragEvents(el) {
    el.addEventListener('dragstart', (e) => {
      dragSrcBlock         = el;
      App.state.isDragging = true;
      e.dataTransfer.effectAllowed = 'move';
      setTimeout(() => (el.style.opacity = '0.35'), 0);
    });

    el.addEventListener('dragend', () => {
      App.state.isDragging = false;
      el.style.opacity     = '';
      document.querySelectorAll('.block-item').forEach(b => b.classList.remove('drag-over'));
      saveBlockOrder();
    });

    el.addEventListener('dragover', (e) => {
      e.preventDefault();
      e.dataTransfer.dropEffect = 'move';
    });

    el.addEventListener('dragenter', () => {
      if (dragSrcBlock && dragSrcBlock !== el) {
        document.querySelectorAll('.block-item').forEach(b => b.classList.remove('drag-over'));
        el.classList.add('drag-over');
      }
    });

    el.addEventListener('drop', (e) => {
      e.preventDefault();
      if (!dragSrcBlock || dragSrcBlock === el) return;
      const container  = document.getElementById('blocks-container');
      const children   = Array.from(container.children);
      const srcIdx     = children.indexOf(dragSrcBlock);
      const tgtIdx     = children.indexOf(el);
      if (srcIdx < tgtIdx) el.after(dragSrcBlock);
      else                  el.before(dragSrcBlock);
      el.classList.remove('drag-over');
    });
  }

  // ================================================================
  // Utility: move cursor to end of a contenteditable
  // ================================================================
  function moveCursorToEnd(el) {
    const range = document.createRange();
    const sel   = window.getSelection();
    range.selectNodeContents(el);
    range.collapse(false);
    sel.removeAllRanges();
    sel.addRange(range);
  }

  // ================================================================
  // Page title events
  // ================================================================
  const titleEl = document.getElementById('page-title-editor');

  const saveTitle = debounce(async (title) => {
    if (!currentPage) return;
    setSaveState('saving');
    try {
      await apiPost('/hagglenote/api/page.php?action=update', {
        id: currentPage.id, title: title || 'Untitled',
      });
      setSaveState('saved');
      PageManager.updateLocalTitle(currentPage.id, title || 'Untitled');
      updateWordCount();
    } catch { setSaveState('error'); }
  }, 800);

  titleEl.addEventListener('input', () => saveTitle(titleEl.textContent.trim()));

  titleEl.addEventListener('keydown', async (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      // Jump to first block or create one
      const firstEditable = document.querySelector('#blocks-container [contenteditable="true"]');
      if (firstEditable) {
        firstEditable.focus();
        moveCursorToEnd(firstEditable);
      } else if (currentPage) {
        await createBlock(currentPage.id, 'text');
      }
    }
  });

  // Focus mode: dim sidebar when user is actively typing in editor
  document.getElementById('editor-scroll')?.addEventListener('focusin', () => {
    document.body.classList.add('focus-mode');
  });
  document.getElementById('editor-scroll')?.addEventListener('focusout', () => {
    setTimeout(() => {
      if (!document.getElementById('editor-scroll').contains(document.activeElement)) {
        document.body.classList.remove('focus-mode');
      }
    }, 100);
  });

  // ================================================================
  // "Add block" button and block type picker
  // ================================================================
  const btnAddBlock  = document.getElementById('btn-add-block');
  const typePicker   = document.getElementById('block-type-picker');

  function closeBlockTypePicker() { typePicker.classList.remove('show'); }

  btnAddBlock.addEventListener('click', (e) => {
    e.stopPropagation();
    typePicker.classList.toggle('show');
  });

  typePicker.querySelectorAll('.block-type-option').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.stopPropagation();
      if (!currentPage) return;
      closeBlockTypePicker();
      const container = document.getElementById('blocks-container');
      await createBlockAtIndex(currentPage.id, btn.dataset.type, container.children.length);
    });
  });

  document.addEventListener('click', () => closeBlockTypePicker());

  // "New page" from home view button
  document.getElementById('btn-empty-new-page')?.addEventListener('click', () => {
    if (App.state.workspaceId) PageManager.createPage(App.state.workspaceId);
  });

  // ================================================================
  // Public API
  // ================================================================
  return {
    renderPage,
    showEmptyState,
    createBlock,
  };

})();
