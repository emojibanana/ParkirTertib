# UI Specification Document - ParkirTertib

## 1. Design Tokens & Styling Architecture

The application uses Tailwind CSS v3 (CLI standalone v3.4.17) utility classes compiled
to `public/assets/css/style.css`. No CDN at runtime.

### 1.1 Color Palette
* **Primary (Brand / Unimed Green):** `emerald-700` (`#047857`), hover `emerald-800` (`#065f46`), light background `emerald-50` (`#ecfdf5`).
* **Secondary / Action:** `slate-900` (`#0f172a`), hover `slate-800` (`#1e293b`).
* **Warning / Pending State:** `amber-500` (`#f59e0b`), badge background `amber-100` (`#fef3c7`), text `amber-800` (`#92400e`).
* **Processing State (`Diproses`):** `sky-600` (`#0284c7`), badge background `sky-100` (`#e0f2fe`), text `sky-800` (`#075985`).
* **Actioned State (`Ditindaklanjuti`):** `indigo-600` (`#4f46e5`), badge background `indigo-100` (`#e0e7ff`), text `indigo-800` (`#3730a3`).
* **Resolved State (`Selesai`):** `emerald-600` (`#059669`), badge background `emerald-100` (`#d1fae5`), text `emerald-800` (`#065f46`).
* **Danger / Error:** `rose-600` (`#e11d48`), alert background `rose-50` (`#fff1f2`), text `rose-800` (`#9f1239`).
* **Neutral Background:** `slate-50` (`#f8fafc`), card background `white` (`#ffffff`), borders `slate-200` (`#e2e8f0`).

### 1.2 Typography
* **Font Family:** Inter, system-ui, -apple-system, sans-serif.
* **Heading 1:** `text-2xl font-bold text-slate-900 sm:text-3xl`
* **Heading 2:** `text-xl font-semibold text-slate-800`
* **Body Text:** `text-sm sm:text-base text-slate-600 leading-relaxed`
* **Form Label:** `block text-sm font-medium text-slate-700 mb-1`
* **Caption / Meta:** `text-xs text-slate-500`

### 1.3 Form Elements & Interactivity
* **Input Field:** `w-full px-3 py-2.5 bg-white border border-slate-300 rounded-lg text-slate-900 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors`
* **Primary Button:** `w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-medium text-sm rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-all cursor-pointer`
* **Secondary Button:** `inline-flex items-center justify-center px-4 py-2 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 font-medium text-sm rounded-lg shadow-sm transition-all`

---

## 2. Page & Screen Specifications

### 2.1 Screen: Login Page (`/views/auth/login.php`)
* **Target Layout:** Centered single-column card layout (`max-w-md mx-auto min-h-screen flex items-center`).
* **Components:**
  * Header logo/title: "ParkirTertib UNIMED" with campus emblem badge.
  * Flash Alert container (XSS-escaped error/success notifications).
  * Form inputs: Email (`type="email"`), Password (`type="password"`), CSRF hidden input.
  * Submit button: "Masuk".
  * Link to register page.
* **States:**
  * *Error:* Displays banner "Email atau password salah." when authentication fails.

### 2.2 Screen: Register Page (`/views/auth/register.php`)
* **Target Layout:** Centered card layout (`max-w-md mx-auto`).
* **Components:**
  * Form inputs: Nama Lengkap (`text`), Email (`email`), Password (`password`), CSRF token.
  * Submit button: "Daftar Akun".
  * Link to login page.

### 2.3 Screen: Report Submission Form (`/views/user/create.php`) - Mobile-First
* **Target Layout:** Single column container (`max-w-lg mx-auto px-4 py-6`).
* **Components:**
  * Rate-limit status banner: Displays remaining allowed active reports (e.g., "Laporan Aktif: 2 / 3").
  * Form fields:
    1. **Foto Bukti Pelanggaran:** Custom file dropzone/camera trigger (`accept="image/jpeg,image/png,image/webp"`). Includes client-side JS image preview card (`aspect-video rounded-lg object-cover`).
    2. **Nomor Pelat Kendaraan:** Text input with uppercase formatting hint (e.g., `BK 1234 ABC`).
    3. **Lokasi Parkir:** Dropdown select options (`Auditorium`, `Gedung Digital Library`, `Fakultas MIPA`, `Fakultas Teknik`, `Fakultas Ilmu Keolahragaan`, `Lainnya`).
    4. **Kategori Pelanggaran:** Dropdown select (`Parkir Sembarangan`, `Menghalangi Akses Jalan`, `Parkir di Area Terlarang`, `Lainnya`).
    5. **Deskripsi Kejadian:** Textarea (`rows="3"`).
  * Submit CTA: Fixed bottom sticky bar on mobile screens or standard block button on desktop.
* **States:**
  * *Disabled / Limit Reached:* Submit button disabled (`bg-slate-300 cursor-not-allowed`) with alert banner: "Anda telah mencapai batas 3 laporan aktif."
  * *Image Preview:* Displays thumbnail image once selected with a "Ganti Foto" button.

### 2.4 Screen: User Report History (`/views/user/history.php`) - Mobile-First
* **Target Layout:** Vertical feed container (`max-w-xl mx-auto px-4 py-6`).
* **Components:**
  * Header with "+ Buat Laporan" button.
  * Card item list representing submitted reports:
    * Image thumbnail (left) or fallback placeholder if image cleaned up.
    * Violation badge (Category & Location).
    * License Plate string (`font-mono font-bold text-slate-900`).
    * Status Badge (`Menunggu`, `Diproses`, `Ditindaklanjuti`, `Selesai`).
    * Timestamp (`formatted e.g., 10 Okt 2026, 14:30 WIB`).
    * Admin note response box (if present).
* **States:**
  * *Empty State:* Display vector illustration card with text: "Belum Ada Laporan. Anda belum pernah mengirimkan laporan pelanggaran parkir."

### 2.5 Screen: Satpam Admin Dashboard (`/views/admin/dashboard.php`) - Desktop-Optimized
* **Target Layout:** Desktop Sidebar + Main Content Grid (`lg:grid lg:grid-cols-12 gap-6 min-h-screen bg-slate-100`).
* **Components:**
  * **Sidebar Navigation (`w-64 bg-slate-900 text-white`):** Links to Dashboard, Repeat Offenders, Logout.
  * **Summary KPI Cards Grid (4 columns):**
    * Card 1: Total Laporan (`bg-white p-4 rounded-xl shadow-sm border border-slate-200`)
    * Card 2: Menunggu (`border-l-4 border-amber-500`)
    * Card 3: Diproses (`border-l-4 border-sky-500`)
    * Card 4: Selesai (`border-l-4 border-emerald-500`)
  * **Filter & Search Bar:** Input search (`Plat / Lokasi`), Dropdown filter (`Semua Status`, `Menunggu`, `Diproses`, `Ditindaklanjuti`, `Selesai`).
  * **Responsive Data Table:**
    * Columns: ID, Tanggal, Pelat Nomor, Lokasi, Kategori, Status, Aksi.
    * Action button: "Tindak Lanjut" links to `/admin/report/detail?id={id}`.
  * **Maintenance Card:** POST form to `/admin/cleanup-photos` with CSRF token, confirm dialog,
    and button "Bersihkan Foto Lama" (FR-009). Result shown via flash summary.

### 2.6 Screen: Satpam Report Detail & Action (`/views/admin/detail.php`)
* **Target Layout:** Two-column split layout (`lg:grid lg:grid-cols-2 gap-8`).
* **Components:**
  * **Left Column:** Full-resolution report photo viewer, reporter details, violation meta, location, and description.
  * **Right Column:** Status Update Action Box.
    * Dropdown select: New status (`Menunggu`, `Diproses`, `Ditindaklanjuti`, `Selesai`).
    * Textarea: Catatan Satpam (e.g., "Stiker teguran telah ditempelkan di sepeda motor.").
    * Submit button: "Simpan Perubahan Status".
  * **Audit Log Timeline:** Vertical timeline showing historical status transitions and notes logged in `report_log`.

### 2.7 Screen: Repeat Offenders View (`/views/admin/repeat-offenders.php`)
* **Target Layout:** Desktop Data Table.
* **Components:**
  * Search bar for license plate lookup.
  * Data Table columns:
    * Nomor Pelat (`font-mono text-base font-bold bg-slate-100 px-2 py-1 rounded w-max`).
    * Jumlah Pelanggaran (`badge count e.g., 4x Pelanggaran`).
    * Lokasi Sering Melanggar.
    * Tanggal Terakhir Pelanggaran.
    * Action button: "Lihat Semua Laporan Motor Ini".

### 2.8 Real-Time Behaviors (FR-008)
* **Toast Component:** Fixed top-center stack (`#toast-container`, area fokus mata),
  slide-down + auto-dismiss 9 detik + tombol tutup, aksen kiri per tipe, teks via
  `textContent` (XSS-safe), lonceng arpeggio lembut opsional.
* **User History:** Polls `/api/notifications` every 15s; on status change shows toast
  (`Laporan #ID: Lama menjadi Baru`), beeps, and updates the card badge in place.
* **Admin Dashboard:** Polls `/api/admin/updates` every 15s; on new report shows toast
  (`Laporan baru #ID (pelat)`), beeps, updates KPI numbers live, and reveals a reload banner.
* Polling pauses when the tab is hidden (`document.hidden`).

---

## 3. Responsive Layout Rules & Breakpoints

| Viewport | Range | Design Rule |
|---|---|---|
| **Mobile (`sm`)** | `< 640px` | Single column, full-width buttons (min touch target `48px`), collapse tables into card lists on user screens. |
| **Tablet (`md`)** | `640px - 1023px` | 2-column grid forms, horizontal filter bars, responsive tables with `overflow-x-auto`. |
| **Desktop (`lg`)** | `>= 1024px` | Fixed sidebar navigation (`w-64`) for Admin dashboard, multi-column KPI grids, split-screen detail views. |

---

## 4. UI Component State Guidelines

```
+-----------------------------------------------------------------------+
| FLASH ALERT COMPONENT STATE EXAMPLES                                  |
+-----------------------------------------------------------------------+
| Success: [✓] Laporan berhasil dikirim. Terima kasih atas laporan Anda.|
| (Classes: bg-emerald-50 text-emerald-800 border border-emerald-200)   |
|                                                                       |
| Danger:  [✕] Ukuran file foto melebihi batas maksimal 5MB.            |
| (Classes: bg-rose-50 text-rose-800 border border-rose-200)             |
|                                                                       |
| Warning: [!] Anda memiliki 3 laporan aktif. Selesaikan laporan lalu. |
| (Classes: bg-amber-50 text-amber-800 border border-amber-200)         |
+-----------------------------------------------------------------------+
```