# Noteon — Pemenuhan Requirement Tugas (Web Application Development)

> **Subject:** Web Application Development — Semester 20252  
> **Requirement CLO-3:** Mampu menerapkan teori dan prinsip pemrograman web sisi server untuk memberikan solusi berdasarkan set requirement tertentu.

---

## ✅ Kesimpulan Cepat

| Requirement | Status | Keterangan |
|---|---|---|
| Minimal 3 tabel terhubung dengan FK | ✅ **TERPENUHI** | 5 tabel, 4 di antaranya memiliki FK |
| Create | ✅ **TERPENUHI** | Di semua entitas (User, Workspace, Section, Page, Block) |
| Read / Select | ✅ **TERPENUHI** | Di semua entitas |
| Update | ✅ **TERPENUHI** | Di semua entitas |
| Delete | ✅ **TERPENUHI** | Di semua entitas dengan cascade yang tepat |
| Aesthetics | ✅ **TERPENUHI** | Premium dark UI, animasi silk, Google Fonts, micro-interactions |
| Business Logic | ✅ **TERPENUHI** | Auth, session guard, auto-save, cascade rules, tab system |

**Project ini memenuhi SELURUH requirement tugas.**

---

## Requirement 1 — Minimal 3 Tabel Terhubung dengan Foreign Key

> *"Minimum of 3 connected tables using foreign keys. Any table that has no foreign key is not counted."*

### Tabel-tabel dalam Noteon

| Tabel | FK ke | FK Column | Behavior |
|-------|-------|-----------|----------|
| `users` | — | — | Tabel induk (tidak dihitung) |
| `workspaces` | `users` | `user_id` | ON DELETE CASCADE |
| `sections` | `workspaces` | `workspace_id` | ON DELETE CASCADE |
| `pages` | `workspaces`, `sections`, `pages` | `workspace_id`, `section_id`, `parent_id` | CASCADE / SET NULL / CASCADE |
| `blocks` | `pages` | `page_id` | ON DELETE CASCADE |
| `checklist_items` | `blocks` | `block_id` | ON DELETE CASCADE |

### Tabel yang Memiliki FK (yang dihitung):

1. **`workspaces`** → FK ke `users`
2. **`sections`** → FK ke `workspaces`
3. **`pages`** → FK ke `workspaces`, `sections`, dan `pages` (self-referencing untuk nesting)
4. **`blocks`** → FK ke `pages`
5. **`checklist_items`** → FK ke `blocks`

**Total: 5 tabel dengan FK** — jauh melampaui minimum 3.

### Cara Menjelaskan ke Dosen

> *"Kami memiliki 6 tabel dalam total, di mana 5 tabel di antaranya dihubungkan menggunakan Foreign Key secara berantai. Relasinya membentuk hirarki: User → Workspace → (Section, Page) → Block → Checklist Item. Setiap relasi FK juga dilengkapi dengan behavioral rule seperti ON DELETE CASCADE dan ON DELETE SET NULL untuk menjaga integritas data."*

---

## Requirement 2 — CRUD Lengkap

> *"Capability to process: Create, Read/Select, Update, Delete"*

---

### A. CREATE

| Entitas | Cara User Melakukan | File API | Method SQL |
|---------|--------------------|-----------|----|
| User (Register) | Isi form Register di landing page | `api/auth.php` → `register` | `INSERT INTO users` |
| Workspace | Klik "+ New Workspace" di dropdown | `api/workspace.php` → `create` | `INSERT INTO workspaces` |
| Section | Klik "+ Add Section" di sidebar | `api/section.php` → `create` | `INSERT INTO sections` |
| Page | Klik "+ New Page" atau "+" di section | `api/page.php` → `create` | `INSERT INTO pages` |
| Block | Tekan Enter di editor untuk tambah blok | `api/block.php` → `save` | `INSERT INTO blocks` |
| Checklist Item | Enter di dalam blok checklist | `api/block.php` → `save` | `INSERT INTO checklist_items` |

---

### B. READ / SELECT

| Entitas | Kapan Dibaca | File API | Method SQL |
|---------|-------------|-----------|------------|
| User | Saat login & cek session | `api/auth.php` → `check` | `SELECT WHERE email` |
| Workspaces | Saat init aplikasi (sidebar) | `api/workspace.php` → `list` | `SELECT WHERE user_id` |
| Sections | Saat loadPages workspace | `api/page.php` → `list` | `SELECT WHERE workspace_id` |
| Pages | Saat loadPages workspace | `api/page.php` → `list` | `SELECT WHERE workspace_id` |
| Blocks | Saat membuka sebuah page | `api/block.php` → `get` | `SELECT WHERE page_id ORDER BY position` |
| Checklist Items | Bersamaan dengan blok | `api/block.php` → `get` | `SELECT WHERE block_id` |

---

### C. UPDATE

| Entitas | Cara User Melakukan | File API | SQL |
|---------|--------------------|-----------|----|
| Workspace | Rename via modal (ikon ✏️) | `api/workspace.php` → `rename` | `UPDATE workspaces SET name` |
| Section | Rename via modal (ikon ✏️) | `api/section.php` → `rename` | `UPDATE sections SET name, icon` |
| Page (judul) | Ketik di field judul → auto-save 800ms | `api/page.php` → `update` | `UPDATE pages SET title` |
| Block (konten) | Ketik di blok → auto-save 800ms | `api/block.php` → `save` | `UPDATE blocks SET content, type` |
| Block (urutan) | Drag & drop blok → reorder | `api/block.php` → `reorder` | `UPDATE blocks SET position` |
| Checklist Item | Klik checkbox → toggle | `api/block.php` → `save` | `UPDATE checklist_items SET is_checked` |

---

### D. DELETE

| Entitas | Cara User Melakukan | File API | SQL + Behavior |
|---------|--------------------|-----------|----|
| Workspace | Klik 🗑️ di dropdown → confirm | `api/workspace.php` → `delete` | `DELETE FROM workspaces` + CASCADE ke semua isi |
| Section | Klik 🗑️ di sidebar → confirm | `api/section.php` → `delete` | `DELETE FROM sections` + pages menjadi NULL |
| Page | Klik 🗑️ di sidebar → confirm | `api/page.php` → `delete` | `DELETE FROM pages` + CASCADE ke sub-pages & blocks |
| Block | Backspace di blok kosong / menu ⋮ | `api/block.php` → `delete` | `DELETE FROM blocks` + CASCADE ke checklist_items |

---

## Requirement 3 — Aesthetics (Estetika)

> *"Note that you must pay attention to aesthetics..."*

### Bukti Implementasi

| Aspek | Implementasi di Noteon |
|-------|------------------------|
| **Landing Page** | Full-screen animasi "Silk" generatif berbasis canvas — background bergerak secara organik |
| **Dark Theme** | Desain dark mode premium — palet `#18181b` dengan aksen amber `#FFAB00` |
| **Typography** | Google Fonts: `Cormorant Garamond` (serif elegan) + `Inter` (UI modern) |
| **Micro-interactions** | Hover effect pada sidebar items, tombol, tabs, dan dropdown workspace |
| **Animasi Panel** | Customize panel muncul dengan `keyframe` fade+scale yang halus |
| **Background Patterns** | 8 pilihan background halaman: Grid, Dots, Lines, Crosshatch, Waves, Warm, Night |
| **Toast Notifications** | Notifikasi non-intrusive di pojok layar untuk feedback aksi |
| **Glassmorphism** | Topbar menggunakan `backdrop-filter: blur(12px)` untuk efek glass |
| **Responsif** | Layout menyesuaikan dengan sidebar yang collapsible di layar kecil |
| **Skeleton Loading** | Animasi loading placeholder saat page sedang dimuat |

### Cara Menjelaskan ke Dosen

> *"Dari sisi estetika, kami membangun Noteon dengan filosofi desain premium dark-mode. Landing page menggunakan animasi canvas generatif yang disebut 'Silk' — setiap refresh menghasilkan tampilan berbeda. Di dalam editor, kami mengimplementasikan sistem Customize Panel yang memungkinkan pengguna memilih font (Serif/Sans/Mono), ukuran teks (Small/Default/Large), dan 8 pilihan background pattern — semua tersimpan di localStorage dan dipertahankan antar sesi."*

---

## Requirement 4 — Business Logic (Logika Bisnis)

> *"...and also the business logic of the application."*

### Bukti Implementasi

| Logika Bisnis | Implementasi |
|---------------|--------------|
| **Autentikasi** | Session PHP — semua halaman editor mengecek `$_SESSION['user_id']`, redirect ke login jika tidak ada |
| **Data Isolation** | Setiap query difilter oleh `user_id` dari session — user A tidak bisa mengakses data user B |
| **Auto-save** | Setiap ketukan di editor → debounce 800ms → simpan ke database. Indikator "Saving..." / "Saved" di topbar |
| **Cascade Rules** | Hapus workspace → semua sections, pages, blocks ikut terhapus. Hapus section → pages tetap ada (status uncategorized) |
| **Default Workspace** | Saat register → workspace pertama dibuat otomatis agar user langsung bisa bekerja |
| **Minimal 1 Workspace** | Jika semua workspace dihapus → sistem memaksa modal create workspace muncul kembali |
| **Nested Pages** | Pages bisa menjadi parent-anak (self-referencing FK). Hapus parent → child pages ikut terhapus (CASCADE) |
| **Tab System** | Setiap page yang dibuka muncul sebagai tab. Tutup tab terakhir → kembali ke Home view |
| **Section-Page Association** | Page dapat di-assign ke section spesifik. Jika section dihapus, page tetap ada dengan `section_id = NULL` |
| **Real-time Search** | Filter sidebar pages secara real-time berdasarkan judul menggunakan JavaScript |
| **Block Reordering** | Drag & drop blocks dalam editor → update kolom `position` di database secara atomik |

### Cara Menjelaskan ke Dosen

> *"Logika bisnis inti dari Noteon adalah sistem workspace personal yang terisolasi per user. Setiap aksi pengguna — mulai dari mengetik, mengganti judul, hingga menghapus page — dilindungi oleh validasi session dan diproses melalui API endpoint yang terpusat. Auto-save dengan debounce 800ms memastikan data tidak pernah hilang tanpa pengguna harus menekan tombol Save. Cascade delete memastikan integritas data — tidak ada 'orphaned records' di database."*

---

## Cara Menjelaskan Project Ini ke Dosen (Script Presentasi)

### Pembuka
> *"Project kami bernama **Noteon** — sebuah aplikasi workspace berbasis web yang terinspirasi dari Notion. Aplikasi ini dibangun menggunakan PHP native, MySQL, dan Vanilla JavaScript tanpa framework tambahan, dan berjalan di atas XAMPP/Apache."*

### Jelaskan Database
> *"Database kami memiliki 6 tabel: users, workspaces, sections, pages, blocks, dan checklist_items. Dari 6 tabel tersebut, 5 tabel memiliki Foreign Key yang menghubungkan satu sama lain secara hirarki. Relasi utamanya adalah: satu user bisa punya banyak workspace, satu workspace punya banyak section dan page, setiap page punya banyak block konten."*

### Jelaskan CRUD
> *"CRUD diimplementasikan di semua entitas. Contohnya: Create — user bisa membuat workspace baru, section baru, page baru, dan blok konten baru. Read — sidebar memuat semua pages dan sections dari database secara real-time. Update — judul page dan isi blok tersimpan otomatis setiap 800ms tanpa perlu tombol save, dan workspace/section bisa di-rename lewat modal. Delete — setiap entitas bisa dihapus dengan konfirmasi, dilengkapi aturan cascade yang tepat."*

### Jelaskan Estetika
> *"Dari sisi estetika, kami membangun landing page dengan animasi canvas generatif, menggunakan Google Fonts premium, dan mengimplementasikan Customize Panel yang memungkinkan user memilih font, ukuran teks, dan background pattern. Semuanya tersimpan di localStorage."*

### Jelaskan Logika Bisnis
> *"Logika bisnis yang paling penting adalah: autentikasi session PHP yang menjaga data user tetap terisolasi, auto-save 800ms yang mencegah kehilangan data, dan aturan cascade delete yang menjaga integritas database — misalnya, menghapus workspace secara otomatis menghapus semua pages dan blocks di dalamnya."*

---

## Poin Bonus yang Bisa Disebutkan

- **Self-referencing FK** pada tabel `pages` (`parent_id`) untuk mendukung nested pages tanpa batas level
- **3 jenis block content type** (text, heading, checklist) dengan penanganan yang berbeda di database dan UI
- **Drag & drop reorder** blok dengan update posisi ke database
- **localStorage persistence** untuk preferensi tampilan user
- **Real-time sidebar search** tanpa reload halaman
- **MutationObserver** untuk mempertahankan preferensi font saat page baru dibuka
- **Emoji picker** custom untuk icon section
- **Toast notification system** sebagai pengganti `alert()`

---

*Dokumen ini dibuat sebagai panduan presentasi tugas Web Application Development — Semester 20252.*
