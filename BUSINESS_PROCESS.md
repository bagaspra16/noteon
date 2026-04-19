# Noteon — Business Process Documentation

> Dokumen ini menjelaskan **alur proses bisnis** dari aplikasi Noteon secara menyeluruh.  
> Ditujukan untuk siapa saja yang ingin memahami bagaimana aplikasi ini bekerja dari sudut pandang pengguna dan logika produk — bukan dari sisi teknis implementasinya.

---

## Daftar Isi

1. [Apa Itu Noteon?](#apa-itu-noteon)
2. [Aktor & Peran](#aktor--peran)
3. [Proses 1 — Registrasi & Login](#proses-1--registrasi--login)
4. [Proses 2 — Workspace Management](#proses-2--workspace-management)
5. [Proses 3 — Section Management](#proses-3--section-management)
6. [Proses 4 — Page Management](#proses-4--page-management)
7. [Proses 5 — Penulisan Konten (Block Editor)](#proses-5--penulisan-konten-block-editor)
8. [Proses 6 — Navigasi & Tab System](#proses-6--navigasi--tab-system)
9. [Proses 7 — Customize Tampilan](#proses-7--customize-tampilan)
10. [Proses 8 — Pencarian Pages](#proses-8--pencarian-pages)
11. [Diagram Alur Bisnis Keseluruhan](#diagram-alur-bisnis-keseluruhan)
12. [Aturan Bisnis Kritis](#aturan-bisnis-kritis)

---

## Apa Itu Noteon?

**Noteon** adalah aplikasi workspace digital untuk mencatat, mendokumentasikan, dan mengorganisasi ide secara terstruktur. Terinspirasi dari Notion, Noteon memungkinkan pengguna untuk:

- Membuat **banyak workspace** yang terpisah (misalnya: Personal, Kuliah, Kerjaan)
- Mengelompokkan catatan ke dalam **Sections** (seperti folder)
- Membuat **Pages** bersarang (pages di dalam pages)
- Menulis konten menggunakan **blok-blok konten** yang fleksibel (teks, judul, to-do list)
- Menyesuaikan tampilan editor sesuai preferensi pribadi

---

## Aktor & Peran

| Aktor       | Deskripsi                                                          |
|-------------|-------------------------------------------------------------------|
| **User**    | Pengguna terdaftar yang memiliki akun, workspace, sections, dan pages |
| **System**  | Aplikasi Noteon yang memproses logika, menyimpan data, dan mengelola sesi |

Noteon adalah aplikasi **single-user per akun** — setiap akun hanya memiliki data miliknya sendiri dan tidak ada fitur kolaborasi multi-user saat ini.

---

## Proses 1 — Registrasi & Login

### 1.1 Registrasi Akun Baru

```
User membuka Noteon (halaman landing)
    │
    ├── Klik "Start for free"
    │       │
    │       ├── Isi form: Nama, Email, Password
    │       │
    │       └── Submit
    │               │
    │               ├── [GAGAL] Email sudah terdaftar → tampilkan error
    │               ├── [GAGAL] Password < 6 karakter → tampilkan error
    │               └── [SUKSES]
    │                       ├── Akun disimpan ke database (password di-hash)
    │                       ├── Workspace default otomatis dibuat: "[Nama]'s Workspace"
    │                       ├── Sesi login dimulai (session PHP)
    │                       └── Redirect ke halaman Editor
```

**Aturan bisnis:**
- Email harus unik — satu email hanya bisa terdaftar satu kali
- Password disimpan dalam bentuk hash (tidak pernah plaintext)
- Workspace pertama dibuat **otomatis** agar pengguna baru langsung bisa mulai bekerja tanpa setup tambahan

---

### 1.2 Login Akun yang Sudah Ada

```
User membuka Noteon (halaman landing)
    │
    ├── Klik "Sign in to your workspace"
    │       │
    │       ├── Isi form: Email, Password
    │       │
    │       └── Submit
    │               │
    │               ├── [GAGAL] Email tidak ditemukan → error
    │               ├── [GAGAL] Password salah → error
    │               └── [SUKSES]
    │                       ├── Verifikasi hash password
    │                       ├── Sesi login dimulai
    │                       └── Redirect ke Editor
```

---

### 1.3 Logout

```
User klik tombol Logout di sidebar bawah
    │
    └── Muncul dialog konfirmasi "Are you sure you want to log out?"
            │
            ├── [Batal] → tidak terjadi apa-apa
            └── [Konfirmasi]
                    ├── Sesi PHP dihancurkan
                    └── Redirect ke halaman Landing
```

---

## Proses 2 — Workspace Management

Workspace adalah **wadah utama** untuk semua catatan seorang user. Satu user bisa punya banyak workspace yang terpisah sepenuhnya.

### 2.1 Membuat Workspace Baru

```
Di halaman Editor → klik nama workspace di pojok kiri atas
    │
    └── Dropdown muncul → klik "+ New workspace"
            │
            └── Modal "New Workspace" terbuka
                    │
                    ├── Isi nama workspace
                    └── Submit
                            │
                            ├── [GAGAL] Nama kosong → error inline
                            └── [SUKSES]
                                    ├── Workspace disimpan ke database
                                    ├── Workspace langsung menjadi aktif
                                    └── Sidebar dikosongkan (workspace baru = kosong)
```

---

### 2.2 Mengganti Workspace Aktif

```
Klik nama workspace di sidebar kiri atas
    │
    └── Dropdown menampilkan semua workspace milik user
            │
            └── Klik workspace yang diinginkan
                    │
                    ├── Workspace aktif berganti
                    ├── Sidebar dimuat ulang dengan pages & sections workspace baru
                    └── Tab editor di-reset ke Home
```

---

### 2.3 Mengganti Nama Workspace (Rename)

```
Hover salah satu workspace di dropdown
    │
    └── Muncul ikon ✏️ di sebelah kanan
            │
            └── Klik ikon ✏️
                    │
                    └── Modal "Rename Workspace" terbuka (sudah terisi nama lama)
                            │
                            ├── Edit nama → klik Save (atau tekan Enter)
                            │       │
                            │       └── [SUKSES] Nama workspace diperbarui
                            │                   (sidebar langsung update jika workspace aktif)
                            └── [Batal] → tidak ada perubahan
```

---

### 2.4 Menghapus Workspace

```
Hover workspace di dropdown → klik ikon 🗑️
    │
    └── Dialog konfirmasi merah muncul:
        "Delete [nama]? All pages and content inside will be permanently deleted."
            │
            ├── [Batal] → tidak ada perubahan
            └── [Delete]
                    │
                    ├── Workspace dihapus dari database
                    ├── SEMUA sections, pages, dan blok di dalamnya ikut terhapus (CASCADE)
                    └── Jika workspace yang dihapus adalah yang aktif:
                            ├── Jika masih ada workspace lain → pindah ke workspace pertama
                            └── Jika tidak ada workspace tersisa → buka modal "New Workspace"
```

**Aturan bisnis kritis:**
- Menghapus workspace bersifat **permanen dan tidak dapat diurungkan**
- Pengguna HARUS selalu memiliki minimal satu workspace — jika semua terhapus, sistem memaksa membuat baru

---

## Proses 3 — Section Management

Section adalah **pengelompokan pages** di dalam sidebar — mirip folder. Section membantu mengorganisir catatan berdasarkan topik atau kategori.

### 3.1 Membuat Section Baru

```
Di sidebar → klik tombol "+ Add Section" (di bagian bawah sidebar)
    │
    └── Modal "New Section" terbuka
            │
            ├── Pilih emoji icon (klik input icon → dropdown emoji muncul)
            ├── Isi nama section
            └── Klik "Create section"
                    │
                    ├── [GAGAL] Nama kosong → tidak diproses
                    └── [SUKSES]
                            ├── Section disimpan ke database dengan icon & nama
                            ├── Section langsung muncul di sidebar dengan icon yang dipilih
                            └── Section awalnya kosong (belum ada pages)
```

---

### 3.2 Menambah Page ke Dalam Section

```
Hover nama section di sidebar
    │
    └── Muncul tombol "+" di sebelah kanan header section
            │
            └── Klik "+"
                    │
                    └── Page baru "Untitled" dibuat dan langsung terbuka
                            ├── Halaman tersebut di-assign ke section tersebut
                            └── Cursor langsung fokus ke field judul
```

---

### 3.3 Mengganti Nama & Icon Section (Rename)

```
Hover nama section di sidebar
    │
    └── Muncul tombol ✏️ (rename)
            │
            └── Klik ✏️
                    │
                    └── Modal "Rename Section" terbuka (terisi nama & icon lama)
                            │
                            ├── Ubah icon dan/atau nama
                            └── Klik "Save section"
                                    │
                                    └── [SUKSES] Section diperbarui di sidebar secara langsung
```

---

### 3.4 Menghapus Section

```
Hover nama section di sidebar → klik ikon 🗑️
    │
    └── Dialog konfirmasi muncul:
        "Are you sure? Pages inside will become uncategorized."
            │
            ├── [Batal] → tidak ada perubahan
            └── [Delete]
                    │
                    ├── Section dihapus dari database
                    ├── Pages yang ada di dalam section TIDAK ikut terhapus
                    │       → section_id pages tersebut diset NULL (menjadi "uncategorized")
                    └── Pages tersebut muncul kembali di bawah group "Pages" (tanpa section)
```

**Aturan bisnis kritis:**
- Menghapus section **tidak menghapus pages di dalamnya** — pages menjadi uncategorized
- Ini berbeda dengan menghapus workspace yang menghapus segalanya secara permanen

---

## Proses 4 — Page Management

Page adalah **unit catatan utama** di Noteon. Setiap page bisa berisi banyak blok konten dan bisa memiliki sub-pages bersarang di dalamnya.

### 4.1 Membuat Page Baru (Top-level)

```
Di sidebar → klik tombol "+ New Page" (di bagian paling bawah)
    │
    └── Page baru "Untitled" dibuat
            ├── Muncul sebagai tab baru di topbar
            ├── Ditampilkan di sidebar tanpa section (uncategorized)
            └── Cursor langsung fokus ke field judul page
```

---

### 4.2 Membuat Sub-Page (Nested)

```
Hover salah satu page di sidebar
    │
    └── Muncul tombol "+" di sebelah kanan
            │
            └── Klik "+"
                    │
                    └── Sub-page baru dibuat
                            ├── parent_id = id page induk
                            ├── Muncul sebagai child di bawah page induk di sidebar
                            └── Dibuka sebagai tab baru
```

**Kedalaman nesting:** Tidak ada batasan level nesting secara teknis, namun secara UX disarankan maksimal 3-4 level untuk keterbacaan sidebar.

---

### 4.3 Membuka Page

```
Klik nama page di sidebar
    │
    └── Cek apakah page sudah ada di tab bar:
            │
            ├── [Sudah ada] → aktifkan tab yang ada, scroll ke atas konten
            └── [Belum ada]
                    ├── Tab baru ditambahkan ke topbar
                    ├── Konten page dimuat dari database (blocks)
                    └── Area editor menampilkan page tersebut
```

---

### 4.4 Mengganti Judul Page

```
User klik atau mengetik di field judul page (atas area editor)
    │
    └── Setiap perubahan → memulai timer debounce 800ms
            │
            └── Setelah 800ms tidak ada ketukan baru:
                    ├── Judul disimpan otomatis ke database
                    ├── Indikator "Saving..." muncul di topbar kanan
                    ├── Berubah menjadi "Saved" setelah berhasil
                    └── Judul di sidebar dan tab langsung diperbarui
```

---

### 4.5 Menghapus Page

```
Hover page di sidebar → klik ikon 🗑️
    │
    └── Dialog konfirmasi muncul:
        "Delete this page and all its sub-pages? This cannot be undone."
            │
            ├── [Batal] → tidak ada perubahan
            └── [Delete]
                    ├── Page & SEMUA sub-pages-nya dihapus dari database
                    ├── Tab page tersebut (dan tab sub-pages) ditutup dari topbar
                    └── Jika halaman yang sedang aktif adalah yang dihapus:
                            ├── Jika masih ada tab lain → pindah ke tab tersebut
                            └── Jika tidak ada tab → kembali ke Home view
```

---

## Proses 5 — Penulisan Konten (Block Editor)

Setiap page memiliki konten yang terdiri dari **blok-blok** yang bisa diatur secara bebas.

### Tipe Blok yang Tersedia

| Tipe       | Fungsi                               | Cara Membuat           |
|------------|--------------------------------------|------------------------|
| **Text**   | Paragraf teks biasa                  | Default saat mengetik  |
| **Heading**| Judul besar (H1) untuk seksi         | Ketik `/` → pilih Heading |
| **Checklist** | To-do list dengan checkbox interaktif | Ketik `/` → pilih To-do |

---

### 5.1 Alur Pengetikan & Auto-Save

```
User membuka page → area editor tampil (kosong atau berisi blok)
    │
    └── User mulai mengetik di blok:
            │
            ├── Setiap karakter ditik → timer debounce 800ms dimulai ulang
            │
            └── Setelah berhenti 800ms:
                    ├── Konten blok dikirim ke server
                    ├── Indikator "Saving..." aktif
                    └── Berubah "Saved" setelah server merespons
```

**Prinsip:** Pengguna tidak perlu menekan tombol Save — semua tersimpan otomatis.

---

### 5.2 Membuat Blok Baru

```
Saat kursor di dalam blok → tekan Enter
    │
    └── Blok baru kosong (type: text) dibuat di bawah blok saat ini
            └── Kursor otomatis pindah ke blok baru
```

---

### 5.3 Menghapus Blok

```
Kursor di blok yang KOSONG → tekan Backspace
    │
    └── Blok tersebut dihapus
            └── Kursor pindah ke blok di atasnya

ATAU

Hover blok → klik ikon ⋮ (options)
    │
    └── Menu opsi muncul → pilih "Delete"
            └── Blok dihapus
```

---

### 5.4 Mengubah Tipe Blok (Slash Command)

```
Di blok kosong → ketik "/" (slash)
    │
    └── Menu Slash Command muncul dengan opsi:
            ├── Text → paragraf biasa
            ├── Heading → judul besar
            └── To-do list → checklist interaktif
                    │
                    └── Pilih salah satu → tipe blok berubah sesuai pilihan
```

---

### 5.5 Mengubah Urutan Blok (Drag & Drop)

```
Hover blok → muncul handle drag (⠿) di kiri blok
    │
    └── Klik & tahan handle → seret ke posisi baru
            │
            └── Lepas → urutan blok diperbarui
                    └── Posisi baru disimpan ke database (kolom `position`)
```

---

### 5.6 Checklist — Centang Item

```
Klik checkbox di blok checklist
    │
    ├── Item ditandai selesai (tercoret, warna berubah)
    └── Status tersimpan otomatis ke database
```

---

## Proses 6 — Navigasi & Tab System

Noteon menggunakan sistem **tab** di topbar — mirip browser — agar pengguna bisa bekerja di beberapa page sekaligus.

### 6.1 Membuka Tab

```
Setiap kali page dibuka (dari sidebar / sub-page / home)
    │
    └── Cek apakah tab page itu sudah ada:
            ├── [Sudah ada] → klik tab tersebut (aktifkan)
            └── [Belum ada] → tab baru ditambahkan di sebelah kanan tab yang ada
```

**Tab Home** selalu ada di posisi paling kiri dan tidak bisa ditutup.

---

### 6.2 Menutup Tab

```
Klik ikon × di pojok kanan tab yang aktif
    │
    ├── Tab tersebut ditutup
    └── Navigasi kembali ke:
            ├── Tab sebelumnya (jika ada)
            └── Home view (jika tidak ada tab lain tersisa)
```

---

### 6.3 Home View

```
Klik tab Home atau ikon rumah di sidebar
    │
    └── Area editor berganti ke Home view yang menampilkan:
            ├── Greeting personal: "Good morning/afternoon/evening, [Nama]."
            ├── Tanggal hari ini
            ├── Quick action cards:
            │     ├── New Page → langsung buat page baru
            │     ├── New Workspace → buka modal create workspace
            │     └── Search → fokus ke search bar di sidebar
            └── Daftar "Recent Pages" → klik langsung buka page tersebut
```

---

## Proses 7 — Customize Tampilan

Pengguna dapat menyesuaikan tampilan area editor sesuai selera melalui panel Customize yang diakses via tombol **⋮** di kanan atas topbar.

### 7.1 Mengubah Ukuran Font

```
Klik ⋮ → Panel Customize terbuka
    │
    └── Seksi "Font Size" → pilih salah satu:
            ├── Small  → teks lebih kecil dan rapat (0.875rem)
            ├── Default → ukuran standar (1rem)
            └── Large  → teks besar untuk kemudahan baca (1.2rem)
                    │
                    └── Perubahan langsung terlihat di area editor
                            └── Preferensi disimpan (tetap saat halaman di-refresh)
```

---

### 7.2 Mengubah Jenis Font

```
Klik ⋮ → Panel Customize terbuka
    │
    └── Seksi "Font" → pilih:
            ├── Serif (Cormorant Garamond) → elegan, cocok untuk tulisan naratif
            ├── Sans-serif (Inter)         → bersih, cocok untuk dokumentasi/teknis
            └── Mono (JetBrains Mono)      → presisi, cocok untuk catatan kode
                    │
                    └── Font langsung berubah di judul dan semua blok konten
```

---

### 7.3 Mengubah Background Area Tulis

```
Klik ⋮ → Panel Customize terbuka
    │
    └── Seksi "Page Background" → pilih dari 8 opsi:
            ├── Clean      → latar polos gelap (default)
            ├── Grid       → kotak-kotak halus
            ├── Dots       → titik-titik merata
            ├── Lines      → garis horizontal (seperti buku tulis)
            ├── Crosshatch → garis silang diagonal
            ├── Waves      → grid persegi halus
            ├── Warm       → cahaya amber/kuning hangat di sudut kiri atas
            └── Night      → cahaya ungu/indigo di sudut kanan bawah
                    │
                    └── Background langsung berubah di seluruh area scroll
                            └── Preferensi disimpan di localStorage
```

---

## Proses 8 — Pencarian Pages

```
User klik atau mengetik di search bar di sidebar
    │
    └── Setiap karakter yang diketik:
            │
            ├── Filter real-time semua page di workspace saat ini
            ├── Hanya pages yang judulnya mengandung kata kunci yang ditampilkan
            └── Sections yang memiliki hasil tetap tampil, sections yang kosong disembunyikan

User klik salah satu hasil pencarian
    │
    └── Page tersebut dibuka (proses sama seperti membuka page dari sidebar)

User menghapus teks pencarian / klik X
    │
    └── Sidebar kembali ke tampilan normal (semua pages & sections)
```

---

## Diagram Alur Bisnis Keseluruhan

```
┌─────────────────────────────────────────────────────────────────┐
│                        NOTEON — ALUR BISNIS                     │
└─────────────────────────────────────────────────────────────────┘

[Landing Page]
      │
      ├─── [Register] ──► Buat akun + Workspace default ──► [Editor]
      └─── [Login]    ──► Verifikasi sesi             ──► [Editor]

[Editor]
  │
  ├── [Workspace]
  │     ├── Pilih workspace aktif (dari dropdown)
  │     ├── Buat workspace baru
  │     ├── Rename workspace
  │     └── Hapus workspace (cascade hapus semua isi)
  │
  ├── [Section] (per workspace)
  │     ├── Buat section baru (dengan emoji icon)
  │     ├── Rename section & icon
  │     ├── Hapus section (pages menjadi uncategorized)
  │     └── Lihat pages dalam section
  │
  ├── [Page] (per workspace / section)
  │     ├── Buat page baru (top-level atau dalam section)
  │     ├── Buat sub-page (nested di bawah page lain)
  │     ├── Buka page → tampil di tab editor
  │     ├── Edit judul (auto-save 800ms)
  │     └── Hapus page (cascade hapus sub-pages & blok)
  │
  ├── [Block Editor] (per page)
  │     ├── Tambah blok (Enter / slash command)
  │     ├── Edit konten blok (auto-save 800ms)
  │     ├── Ubah tipe blok (text / heading / checklist)
  │     ├── Hapus blok (Backspace atau menu ⋮)
  │     ├── Reorder blok (drag & drop)
  │     └── Centang/uncentang checklist item
  │
  ├── [Tab System]
  │     ├── Tab Home (selalu ada, tidak bisa ditutup)
  │     ├── Buka tab page baru
  │     ├── Switch antar tab
  │     └── Tutup tab → kembali ke tab sebelumnya / Home
  │
  ├── [Search]
  │     └── Filter real-time judul page di workspace aktif
  │
  └── [Customize Panel] (⋮)
        ├── Ukuran font (Small / Default / Large)
        ├── Jenis font (Serif / Sans / Mono)
        └── Background area tulis (8 pilihan pola/gradien)
```

---

## Aturan Bisnis Kritis

| # | Aturan | Dampak jika dilanggar |
|---|--------|-----------------------|
| 1 | Email unik per pengguna | Duplikasi akun → error registrasi |
| 2 | Password minimal 6 karakter | Error validasi |
| 3 | User harus login untuk mengakses editor | Redirect paksa ke landing page |
| 4 | User hanya bisa mengakses data miliknya | Semua query difilter berdasarkan `user_id` dari sesi |
| 5 | Menghapus workspace menghapus SEMUA isinya | Data permanen hilang → konfirmasi wajib |
| 6 | Menghapus section TIDAK menghapus pages | Pages menjadi uncategorized (section_id = NULL) |
| 7 | Menghapus page menghapus semua sub-pages & blok | Data permanen hilang → konfirmasi wajib |
| 8 | User harus memiliki minimal 1 workspace | Jika 0 workspace → modal create wajib muncul |
| 9 | Konten block disimpan otomatis (debounce 800ms) | Tidak ada tombol Save manual |
| 10 | Preferensi tampilan disimpan per browser (localStorage) | Berpindah browser = preferensi reset ke default |

---

*Dokumen ini menjelaskan proses bisnis Noteon versi 1.0 — April 2026.*
