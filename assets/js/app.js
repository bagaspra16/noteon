/**
 * assets/js/app.js
 *
 * Bootstrap module — initialises all other modules in order.
 * Also provides shared utilities: apiPost, apiGet, showToast, debounce.
 */

// ====================================================================
// Global application state
// ====================================================================
const App = {
  state: {
    workspaceId: null,   // currently active workspace id
    pageId: null,   // currently open page id
    isDragging: false,
  },

  // Custom dialog
  confirm(title, message, onConfirm, confirmText = 'Delete', type = 'danger') {
    const modal = document.getElementById('modal-confirm');
    if (!modal) return;

    document.getElementById('confirm-title').textContent = title;
    document.getElementById('confirm-msg').innerHTML = message;

    const btnOk = document.getElementById('btn-confirm-ok');
    btnOk.textContent = confirmText;
    if (type === 'danger') {
      btnOk.style.background = '#ef4444';
      btnOk.style.color = '#fff';
    } else {
      btnOk.style.background = '#3f3f46';
      btnOk.style.color = '#f4f4f5';
    }

    modal.classList.add('active');

    const doConfirm = () => { cleanup(); onConfirm(); };
    const doCancel = () => { cleanup(); };

    const cancelBtn = document.getElementById('btn-confirm-cancel');

    // Add fresh listeners
    btnOk.addEventListener('click', doConfirm);
    cancelBtn.addEventListener('click', doCancel);

    const cleanup = () => {
      modal.classList.remove('active');
      btnOk.removeEventListener('click', doConfirm);
      cancelBtn.removeEventListener('click', doCancel);
    };
  }
};

window.App = App;

// ====================================================================
// Utility: fetch wrapper for POST requests (JSON body/response)
// ====================================================================
async function apiPost(url, body = {}) {
  try {
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    });

    if (res.status === 401) {
      window.location.href = '/noteon/views/index.php';
      return null;
    }

    const contentType = res.headers.get('content-type');
    const text = await res.text();

    if (!res.ok) {
      console.error(`apiPost error ${res.status}: ${url}`, text);
      try {
        return JSON.parse(text); // Try to parse error JSON if available
      } catch (e) {
        return { success: false, error: `Server error (${res.status})` };
      }
    }

    if (!text) return null;

    if (contentType && contentType.includes('application/json')) {
      return JSON.parse(text);
    }

    console.error('apiPost received non-JSON response:', text);
    return null;
  } catch (err) {
    console.error('apiPost network/fatal error', url, err);
    return null;
  }
}

// ====================================================================
// Utility: fetch wrapper for GET requests
// ====================================================================
async function apiGet(url) {
  try {
    const res = await fetch(url);

    if (res.status === 401) {
      window.location.href = '/noteon/views/index.php';
      return null;
    }

    const contentType = res.headers.get('content-type');
    const text = await res.text();

    if (!res.ok) {
      console.error(`apiGet error ${res.status}: ${url}`, text);
      return null;
    }

    if (!text) return null;

    if (contentType && contentType.includes('application/json')) {
      return JSON.parse(text);
    }

    console.error('apiGet received non-JSON response:', text);
    return null;
  } catch (err) {
    console.error('apiGet network/fatal error', url, err);
    return null;
  }
}

// ====================================================================
// Utility: show a brief toast notification
// ====================================================================
function showToast(message, type = 'default') {
  const toast = document.getElementById('toast');
  if (!toast) { console.warn('Toast missing:', message); return; }
  toast.textContent = message;
  toast.className = `toast ${type} show`;
  clearTimeout(toast._t);
  toast._t = setTimeout(() => toast.classList.remove('show'), 2800);
}

// ====================================================================
// Utility: debounce — delays fn execution until after `delay` ms
// ====================================================================
function debounce(fn, delay = 800) {
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), delay);
  };
}

// ====================================================================
// Utility: update the saving indicator in the top bar
// ====================================================================
function setSaveState(state) {
  const indicator = document.getElementById('save-indicator');
  const spinner = document.getElementById('save-spinner');
  const text = document.getElementById('save-text');

  indicator.classList.add('visible');
  indicator.classList.remove('saved', 'error');

  if (state === 'saving') {
    spinner.style.display = 'inline';
    spinner.classList.add('spin');
    text.textContent = 'Saving…';
  } else if (state === 'saved') {
    spinner.style.display = 'none';
    spinner.classList.remove('spin');
    text.textContent = 'Saved';
    indicator.classList.add('saved');
    setTimeout(() => indicator.classList.remove('visible'), 2200);
  } else if (state === 'error') {
    spinner.style.display = 'none';
    spinner.classList.remove('spin');
    text.textContent = 'Save failed';
    indicator.classList.add('error');
  }
}

// ====================================================================
// Logout
// ====================================================================
document.getElementById('btn-logout')?.addEventListener('click', () => {
  App.confirm('Log Out', 'Are you sure you want to log out of noteon?', async () => {
    await apiGet('/noteon/api/auth.php?action=logout');
    window.location.href = '/noteon/views/index.php';
  }, 'Log out');
});

// ====================================================================
// Bootstrap on DOMContentLoaded
// ====================================================================
document.addEventListener('DOMContentLoaded', async () => {
  await WorkspaceManager.init();
  // Explicit load of pages after init (in case setActive raced)
  if (App.state.workspaceId) {
    await PageManager.loadPages(App.state.workspaceId);
  }
});
