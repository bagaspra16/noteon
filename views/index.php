<?php
/**
 * views/index.php — Landing page (Silk Era, v3)
 *
 * Full-screen silk canvas background, Cormorant Garamond typography,
 * login / register modals, and feature strip.
 */
session_start();

// Already logged in — go straight to the editor
if (isset($_SESSION['user_id'])) {
  header('Location: /noteon/views/editor.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Noteon — Your Workspace, Simplified</title>
  <meta name="description"
    content="Noteon is a clean, distraction-free workspace for capturing ideas and organizing everything in one place.">

  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Custom styles -->
  <link rel="stylesheet" href="/noteon/assets/css/style.css?v=6">

  <style>
    /* Ensure html/body carry no extra margin */
    html,
    body {
      margin: 0;
      padding: 0;
      overflow: hidden;
    }

    /* Modal input & buttons back to Inter (UI text) */
    .modal-input,
    .btn-primary,
    .btn-secondary,
    .btn-cta {
      font-family: 'Inter', sans-serif;
    }
  </style>
</head>

<body class="antialiased bg-black">

  <!-- ================================================================
       SILK HERO SECTION (full-screen animated background)
       ================================================================ -->
  <section class="silk-hero" id="silk-hero" aria-label="Hero">

    <!-- Animated silk canvas -->
    <canvas id="silk-canvas" aria-hidden="true"></canvas>

    <!-- Gradient overlay for depth -->
    <div class="silk-overlay-top" aria-hidden="true"></div>

    <!-- ---- NAVBAR ---- -->
    <nav class="silk-hero-nav" id="main-nav">
      <!-- Logo -->
      <div class="silk-logo">
        <span class="silk-logo-name"
          style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 1.25rem; letter-spacing: -0.03em;">Noteon.</span>
      </div>

      <!-- Auth buttons -->
      <div class="flex items-center gap-3">
        <button id="btn-open-login" class="btn-glass">Log in</button>
        <button id="btn-open-register" class="btn-cta"
          style="padding: 0.5rem 1.25rem; font-size: 0.875rem; border-radius: 9px; font-family: 'Inter', sans-serif;">
          Get started
        </button>
      </div>
    </nav>

    <!-- ---- HERO BODY ---- -->
    <div class="silk-hero-body">

      <!-- Badge -->
      <!-- <div class="hero-badge silk-anim-1">
        <span class="hero-badge-dot"></span>
        Fast &nbsp;·&nbsp; Simple &nbsp;·&nbsp; Organized
      </div> -->

      <!-- Main headline -->
      <h1 class="hero-headline silk-anim-2">
        Your workspace,<br>
        being
        <em>simplified.</em>
      </h1>

      <!-- Subline -->
      <p class="hero-subline silk-anim-3" style="max-width: 460px;">
        Noteon is the distraction-free workspace to capture ideas, build docs, and align your thoughts effortlessly.
      </p>

      <!-- CTA buttons -->
      <div class="hero-cta-row silk-anim-4">
        <button id="btn-hero-register" class="btn-cta">
          Start for free
          <svg width="15" height="15" viewBox="0 0 16 16" fill="none" style="margin-left:2px">
            <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
              stroke-linejoin="round" />
          </svg>
        </button>
        <button id="btn-hero-login" class="btn-glass">
          Sign in to your workspace
        </button>
      </div>

      <!-- Accent word row -->
      <div class="hero-accent-row silk-anim-5">
        <span>Flowing</span>
        <span class="hero-accent-sep"></span>
        <span>Thoughtful</span>
        <span class="hero-accent-sep"></span>
        <span>Effortless</span>
      </div>

    </div>

    <!-- Corner mark -->
    <div class="silk-corner-mark silk-anim-6">2026 &nbsp; Noteon.</div>

    <!-- Scroll cue removed as requested -->

  </section>

  <!-- ================================================================
       LOGIN MODAL
       ================================================================ -->
  <div id="modal-login" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="login-title">
    <div class="modal-card" id="modal-login-card">
      <div class="flex items-center justify-between mb-6">
        <h2 id="login-title" class="text-xl font-semibold text-white" style="font-family:'Inter',sans-serif">Welcome
          back</h2>
        <button id="btn-close-login" class="text-zinc-500 hover:text-white text-xl leading-none">&times;</button>
      </div>

      <div id="login-error" class="alert-error mb-4"></div>

      <form id="form-login" novalidate>
        <div class="mb-4">
          <label for="login-email" class="block text-sm font-medium text-zinc-400 mb-1.5"
            style="font-family:'Inter',sans-serif">Email</label>
          <input id="login-email" name="email" type="email" autocomplete="email" class="modal-input"
            placeholder="you@example.com" required>
        </div>
        <div class="mb-6">
          <label for="login-password" class="block text-sm font-medium text-zinc-400 mb-1.5"
            style="font-family:'Inter',sans-serif">Password</label>
          <input id="login-password" name="password" type="password" autocomplete="current-password" class="modal-input"
            placeholder="••••••••" required>
        </div>
        <button id="btn-login-submit" type="submit" class="btn-primary">Log in</button>
      </form>

      <p class="text-center text-sm text-zinc-500 mt-5" style="font-family:'Inter',sans-serif">
        Don't have an account?
        <button id="btn-switch-to-register" class="text-amber-400 hover:text-amber-300 font-medium ml-1">
          Sign up
        </button>
      </p>
    </div>
  </div>

  <!-- ================================================================
       REGISTER MODAL
       ================================================================ -->
  <div id="modal-register" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="register-title">
    <div class="modal-card" id="modal-register-card">
      <div class="flex items-center justify-between mb-6">
        <h2 id="register-title" class="text-xl font-semibold text-white" style="font-family:'Inter',sans-serif">Create
          an account</h2>
        <button id="btn-close-register" class="text-zinc-500 hover:text-white text-xl leading-none">&times;</button>
      </div>

      <div id="register-error" class="alert-error mb-4"></div>

      <form id="form-register" novalidate>
        <div class="mb-4">
          <label for="register-name" class="block text-sm font-medium text-zinc-400 mb-1.5"
            style="font-family:'Inter',sans-serif">Full name</label>
          <input id="register-name" name="name" type="text" autocomplete="name" class="modal-input"
            placeholder="Your name" required>
        </div>
        <div class="mb-4">
          <label for="register-email" class="block text-sm font-medium text-zinc-400 mb-1.5"
            style="font-family:'Inter',sans-serif">Email</label>
          <input id="register-email" name="email" type="email" autocomplete="email" class="modal-input"
            placeholder="you@example.com" required>
        </div>
        <div class="mb-6">
          <label for="register-password" class="block text-sm font-medium text-zinc-400 mb-1.5"
            style="font-family:'Inter',sans-serif">Password</label>
          <input id="register-password" name="password" type="password" autocomplete="new-password" class="modal-input"
            placeholder="Min. 6 characters" required>
        </div>
        <button id="btn-register-submit" type="submit" class="btn-primary">Create account</button>
      </form>

      <p class="text-center text-sm text-zinc-500 mt-5" style="font-family:'Inter',sans-serif">
        Already have an account?
        <button id="btn-switch-to-login" class="text-amber-400 hover:text-amber-300 font-medium ml-1">
          Log in
        </button>
      </p>
    </div>
  </div>

  <!-- ================================================================
       SCRIPTS
       ================================================================ -->
  <script>
    (() => {

      // ================================================================
      // SILK CANVAS ANIMATION (ported from React component to vanilla JS)
      // ================================================================
      const canvas = document.getElementById('silk-canvas');
      const ctx = canvas.getContext('2d');

      let time = 0;
      const speed = 0.02;
      const scale = 2;
      const noiseIntens = 0.8;
      let animId;

      function resizeCanvas() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
      }

      resizeCanvas();
      window.addEventListener('resize', resizeCanvas);

      // Simple deterministic noise (same algorithm as original React component)
      function noise(x, y) {
        const G = 2.71828;
        const rx = G * Math.sin(G * x);
        const ry = G * Math.sin(G * y);
        return (rx * ry * (1 + x)) % 1;
      }

      function animate() {
        const W = canvas.width;
        const H = canvas.height;

        // Background gradient
        const bg = ctx.createLinearGradient(0, 0, W, H);
        bg.addColorStop(0, '#1a1a1a');
        bg.addColorStop(0.5, '#2a2a2a');
        bg.addColorStop(1, '#1a1a1a');
        ctx.fillStyle = bg;
        ctx.fillRect(0, 0, W, H);

        // Silk pixel pattern (step 2 for performance, as in original)
        const imageData = ctx.createImageData(W, H);
        const data = imageData.data;
        const tOffset = speed * time;

        for (let x = 0; x < W; x += 2) {
          for (let y = 0; y < H; y += 2) {
            const u = (x / W) * scale;
            const v = (y / H) * scale;

            let tex_x = u;
            let tex_y = v + 0.03 * Math.sin(8.0 * tex_x - tOffset);

            const pattern =
              0.6 + 0.4 * Math.sin(
                5.0 * (tex_x + tex_y +
                  Math.cos(3.0 * tex_x + 5.0 * tex_y) +
                  0.02 * tOffset) +
                Math.sin(20.0 * (tex_x + tex_y - 0.1 * tOffset))
              );

            const rnd = noise(x, y);
            const intensity = Math.max(0, pattern - (rnd / 15.0) * noiseIntens);

            // Warm neutral silk colour
            const r = Math.floor(123 * intensity);
            const g = Math.floor(116 * intensity);
            const b = Math.floor(129 * intensity);

            const idx = (y * W + x) * 4;
            if (idx < data.length) {
              data[idx] = r;
              data[idx + 1] = g;
              data[idx + 2] = b;
              data[idx + 3] = 255;
            }
          }
        }

        ctx.putImageData(imageData, 0, 0);

        // Radial vignette overlay
        const vignette = ctx.createRadialGradient(
          W / 2, H / 2, 0,
          W / 2, H / 2, Math.max(W, H) / 2
        );
        vignette.addColorStop(0, 'rgba(0,0,0,0.05)');
        vignette.addColorStop(1, 'rgba(0,0,0,0.55)');
        ctx.fillStyle = vignette;
        ctx.fillRect(0, 0, W, H);

        time += 1;
        animId = requestAnimationFrame(animate);
      }

      animate();

      // Pause animation when tab is hidden to save resources
      document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
          cancelAnimationFrame(animId);
        } else {
          animate();
        }
      });

      // ================================================================
      // MODAL HELPERS
      // ================================================================
      function openModal(id) { document.getElementById(id).classList.add('active'); }
      function closeModal(id) { document.getElementById(id).classList.remove('active'); }

      function showError(id, msg) {
        const el = document.getElementById(id);
        el.textContent = msg;
        el.classList.add('show');
      }
      function clearError(id) {
        const el = document.getElementById(id);
        el.textContent = '';
        el.classList.remove('show');
      }

      // Trigger buttons
      document.getElementById('btn-open-login').addEventListener('click', () => openModal('modal-login'));
      document.getElementById('btn-open-register').addEventListener('click', () => openModal('modal-register'));
      document.getElementById('btn-hero-register').addEventListener('click', () => openModal('modal-register'));
      document.getElementById('btn-hero-login').addEventListener('click', () => openModal('modal-login'));
      document.getElementById('btn-close-login').addEventListener('click', () => closeModal('modal-login'));
      document.getElementById('btn-close-register').addEventListener('click', () => closeModal('modal-register'));

      // Switch between modals
      document.getElementById('btn-switch-to-register').addEventListener('click', () => {
        closeModal('modal-login');
        openModal('modal-register');
      });
      document.getElementById('btn-switch-to-login').addEventListener('click', () => {
        closeModal('modal-register');
        openModal('modal-login');
      });

      // Close on overlay click
      document.getElementById('modal-login').addEventListener('click', (e) => {
        if (e.target === e.currentTarget) closeModal('modal-login');
      });
      document.getElementById('modal-register').addEventListener('click', (e) => {
        if (e.target === e.currentTarget) closeModal('modal-register');
      });

      // ================================================================
      // AJAX HELPER
      // ================================================================
      async function apiPost(url, body) {
        const res = await fetch(url, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(body),
        });
        return res.json();
      }

      // ================================================================
      // LOGIN FORM
      // ================================================================
      document.getElementById('form-login').addEventListener('submit', async (e) => {
        e.preventDefault();
        clearError('login-error');

        const btn = document.getElementById('btn-login-submit');
        const email = document.getElementById('login-email').value.trim();
        const password = document.getElementById('login-password').value;

        if (!email || !password) {
          showError('login-error', 'Please fill in all fields.');
          return;
        }

        btn.disabled = true;
        btn.textContent = 'Signing in…';

        try {
          const data = await apiPost('/noteon/api/auth.php?action=login', { email, password });
          if (data.success) {
            window.location.href = data.redirect;
          } else {
            showError('login-error', data.error || 'Login failed.');
          }
        } catch {
          showError('login-error', 'Network error. Please try again.');
        } finally {
          btn.disabled = false;
          btn.textContent = 'Log in';
        }
      });

      // ================================================================
      // REGISTER FORM
      // ================================================================
      document.getElementById('form-register').addEventListener('submit', async (e) => {
        e.preventDefault();
        clearError('register-error');

        const btn = document.getElementById('btn-register-submit');
        const name = document.getElementById('register-name').value.trim();
        const email = document.getElementById('register-email').value.trim();
        const password = document.getElementById('register-password').value;

        if (!name || !email || !password) {
          showError('register-error', 'Please fill in all fields.');
          return;
        }

        btn.disabled = true;
        btn.textContent = 'Creating account…';

        try {
          const data = await apiPost('/noteon/api/auth.php?action=register', { name, email, password });
          if (data.success) {
            window.location.href = data.redirect;
          } else {
            showError('register-error', data.error || 'Registration failed.');
          }
        } catch {
          showError('register-error', 'Network error. Please try again.');
        } finally {
          btn.disabled = false;
          btn.textContent = 'Create account';
        }
      });

    })();
  </script>

</body>

</html>