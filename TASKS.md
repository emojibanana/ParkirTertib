# Task Execution List & Dependency Graph - ParkirTertib

## Milestone 0: Project Setup & Core Infrastructure (M0)

### T-001: Environment, Database & Front Controller Scaffolding
* **Depends On:** None
* **Linked FRs:** N/A (Core Scaffolding)
* **Files to Create/Modify:**
  * `.env.example`
  * `config/env.php`
  * `config/database.php`
  * `schema.sql`
  * `public/.htaccess`
  * `public/index.php`
* **Steps:**
  1. Create `.env.example` with DB keys, APP_ENV, and UPLOAD_DIR.
  2. Implement `config/env.php` custom environment loader function.
  3. Implement `config/database.php` returning a configured PDO instance with `ERRMODE_EXCEPTION` and `FETCH_ASSOC`.
  4. Write `schema.sql` containing DDL for `user`, `report`, and `report_log` tables with index constraints.
  5. Configure Apache URL rewrite in `public/.htaccess` routing all non-file requests to `public/index.php`.
  6. Create basic router entry point in `public/index.php`.
* **Acceptance Criteria:** `public/index.php` renders hello message and PDO connects to test database without errors.
* **Verification Command:** `php -S localhost:8000 -t public/` and `curl -s http://localhost:8000`

---

### T-002: Frontend Tooling & Shared Layout Setup
* **Depends On:** T-001
* **Linked FRs:** N/A (UI Layout & Base Views)
* **Files to Create/Modify:**
  * `tailwind.config.js`
  * `public/assets/css/app.css`
  * `src/Helpers/View.php`
  * `views/layouts/header.php`
  * `views/layouts/footer.php`
* **Steps:**
  1. Initialize `tailwind.config.js` configuring view file paths in content array.
  2. Create base CSS file importing Tailwind base, components, and utilities.
  3. Implement `View::render()` helper class in `src/Helpers/View.php` supporting template rendering and data extraction.
  4. Create `views/layouts/header.php` with responsive navbar, brand logo, mobile nav drawer, and role links.
  5. Create `views/layouts/footer.php` with copyright notice and campus meta text.
* **Acceptance Criteria:** Shared layouts compile with Tailwind standalone CLI and render clean responsive navigation.
* **Verification Command:** `./tailwindcss -i public/assets/css/app.css -o public/assets/css/style.css --minify`

---

### T-003: Security & Input Sanitizer Helpers
* **Depends On:** T-001
* **Linked FRs:** N/A (Core Security)
* **Files to Create/Modify:**
  * `src/Helpers/CSRF.php`
  * `src/Helpers/Sanitizer.php`
  * `tests/Unit/SecurityTest.php`
* **Steps:**
  1. Implement `CSRF::generateToken()` and `CSRF::verifyToken()` using `hash_equals()`.
  2. Implement `Sanitizer::escape($string)` using `htmlspecialchars($str, ENT_QUOTES, 'UTF-8')`.
  3. Implement `Sanitizer::normalizeLicensePlate($plate)` converting input to uppercase and stripping inner spaces (`preg_replace('/\\s+/', '', strtoupper($plate))`).
  4. Write unit tests in `tests/Unit/SecurityTest.php` testing CSRF generation/validation and sanitizer output.
* **Acceptance Criteria:** All unit security tests pass cleanly in PHPUnit.
* **Verification Command:** `vendor/bin/phpunit tests/Unit/SecurityTest.php`

---

## Milestone 1: User Authentication & Role Management (M1)

### T-004: User Model & Role Middlewares
* **Depends On:** T-003
* **Linked FRs:** FR-001
* **Files to Create/Modify:**
  * `src/Models/User.php`
  * `src/Middlewares/AuthMiddleware.php`
  * `src/Middlewares/AdminMiddleware.php`
* **Steps:**
  1. Implement `UserModel` with PDO methods: `create()`, `findByEmail()`, `findById()`.
  2. Implement `AuthMiddleware::handle()` checking `$_SESSION['user_id']` exists; redirects to `/login` if missing.
  3. Implement `AdminMiddleware::handle()` checking `$_SESSION['user_role'] === 'admin'`; aborts with 403 HTTP status if user is not an admin.
* **Acceptance Criteria:** UserModel handles CRUD with prepared statements and middlewares properly enforce session checks.
* **Verification Command:** `vendor/bin/phpunit --filter UserTest`

---

### T-005: Authentication Controller & Views
* **Depends On:** T-002, T-004
* **Linked FRs:** FR-001
* **Files to Create/Modify:**
  * `src/Controllers/AuthController.php`
  * `views/auth/login.php`
  * `views/auth/register.php`
  * `tests/Integration/AuthTest.php`
* **Steps:**
  1. Implement `AuthController::showLogin()`, `login()`, `showRegister()`, `register()`, `logout()`.
  2. Implement `password_hash()` password creation and `password_verify()` login check.
  3. Call `session_regenerate_id(true)` upon successful authentication.
  4. Create UI view files `login.php` and `register.php` with CSRF hidden inputs and Tailwind styling.
  5. Write integration tests for registration and login workflows in `AuthTest.php`.
* **Acceptance Criteria:** Users can register and log in; session flags set correctly; integration tests pass.
* **Verification Command:** `vendor/bin/phpunit tests/Integration/AuthTest.php`

---

## Milestone 2: User Report Management (M2)

### T-006: File Upload Helper & Report Model
* **Depends On:** T-004
* **Linked FRs:** FR-002, FR-003, FR-004
* **Files to Create/Modify:**
  * `src/Helpers/FileUpload.php`
  * `src/Models/Report.php`
  * `tests/Unit/FileUploadTest.php`
* **Steps:**
  1. Implement `FileUpload::uploadImage($fileArray)` validating file size (<= 5MB), extension whitelist (`jpg`, `jpeg`, `png`, `webp`), and MIME type using `finfo_file()`.
  2. Store valid images in `public/uploads/` with randomized hash filenames.
  3. Implement `ReportModel` methods: `create()`, `countActiveByUserId()`, `findByUserId()`, `findById()`.
  4. Write unit tests for file upload validation in `FileUploadTest.php`.
* **Acceptance Criteria:** Valid images upload successfully with random filenames; invalid files and oversized images are rejected.
* **Verification Command:** `vendor/bin/phpunit tests/Unit/FileUploadTest.php`

---

### T-007: User Report Submission Controller & Form View
* **Depends On:** T-005, T-006
* **Linked FRs:** FR-002, FR-003
* **Files to Create/Modify:**
  * `src/Controllers/ReportController.php`
  * `views/user/create.php`
  * `views/user/home.php`
  * `tests/Integration/ReportSubmissionTest.php`
* **Steps:**
  1. Implement `ReportController::create()` rendering submission form and `store()` handling POST upload.
  2. Enforce active report limit (max 3 active reports where `status IN ('Menunggu', 'Diproses', 'Ditindaklanjuti')`); reject submission if limit reached.
  3. Normalize license plate input string via `Sanitizer::normalizeLicensePlate()`.
  4. Build responsive mobile-first form view in `create.php` with camera trigger and client-side JS image preview.
  5. Write integration tests covering submission and rate limit enforcement.
* **Acceptance Criteria:** Users can submit reports with photo uploads; 4th active report submission is blocked with alert message.
* **Verification Command:** `vendor/bin/phpunit tests/Integration/ReportSubmissionTest.php`

---

### T-008: User Report History View
* **Depends On:** T-007
* **Linked FRs:** FR-004
* **Files to Create/Modify:**
  * `src/Controllers/ReportController.php` (update)
  * `views/user/history.php`
  * `tests/Integration/ReportHistoryTest.php`
* **Steps:**
  1. Implement `ReportController::history()` fetching reports associated with logged-in `user_id`.
  2. Build mobile-first card list view `views/user/history.php` displaying status badges, location, normalized license plate, timestamp, admin notes, and photo thumbnail.
  3. Add fallback UI placeholder if photo file has been unlinked by automated cleanup.
  4. Write integration test checking history listing output.
* **Acceptance Criteria:** History page displays all user reports with accurate status badges and admin notes.
* **Verification Command:** `vendor/bin/phpunit tests/Integration/ReportHistoryTest.php`

---

## Milestone 3: Satpam Admin Operations & Repeat Offenders (M3)

### T-009: Report Log Model & Admin Report Model Operations
* **Depends On:** T-006
* **Linked FRs:** FR-005, FR-006
* **Files to Create/Modify:**
  * `src/Models/ReportLog.php`
  * `src/Models/Report.php` (update)
* **Steps:**
  1. Implement `ReportLogModel::createLog()` inserting audit record into `report_log`.
  2. Update `ReportModel` with methods: `getSummaryCounts()`, `getFilteredReports($search,$status)`, `updateStatus($id, $newStatus,$adminNotes)`, `getRepeatOffenders()`.
* **Acceptance Criteria:** Model queries return accurate summary counts, filtered search records, and repeat offender groupings.
* **Verification Command:** `vendor/bin/phpunit --filter ReportModelTest`

---

### T-010: Admin Dashboard & Status Update Controller & Views
* **Depends On:** T-005, T-009
* **Linked FRs:** FR-005
* **Files to Create/Modify:**
  * `src/Controllers/AdminController.php`
  * `views/admin/dashboard.php`
  * `views/admin/detail.php`
  * `tests/Integration/AdminReportTest.php`
* **Steps:**
  1. Implement `AdminController::dashboard()` rendering summary KPI cards and searchable reports data table.
  2. Implement `AdminController::showDetail()` and `updateStatus()` handling status transitions (`Menunggu` -> `Diproses` -> `Ditindaklanjuti` -> `Selesai`) and inserting audit entries in `report_log`.
  3. Create desktop-optimized views `dashboard.php` and `detail.php`.
  4. Write integration test verifying admin status updates and log creation.
* **Acceptance Criteria:** Satpam can search reports, update status, add notes, and view historical audit timelines.
* **Verification Command:** `vendor/bin/phpunit tests/Integration/AdminReportTest.php`

---

### T-011: Repeat Offenders Tracking Module
* **Depends On:** T-010
* **Linked FRs:** FR-006
* **Files to Create/Modify:**
  * `src/Controllers/AdminController.php` (update)
  * `views/admin/repeat_offenders.php`
  * `tests/Integration/RepeatOffendersTest.php`
* **Steps:**
  1. Implement `AdminController::repeatOffenders()` fetching aggregated violation counts grouped by license plate.
  2. Create view `views/admin/repeat_offenders.php` rendering table sorted descending by total report count with incident dates and locations.
  3. Write integration test verifying repeat offender aggregation.
* **Acceptance Criteria:** Repeat offenders page presents vehicles grouped by license plate ordered by violation frequency.
* **Verification Command:** `vendor/bin/phpunit tests/Integration/RepeatOffendersTest.php`

---

## Milestone 4: Maintenance & Automated Cleanup (M4)

### T-012: Automated Media Storage Cleanup CLI Script
* **Depends On:** T-009
* **Linked FRs:** FR-007
* **Files to Create/Modify:**
  * `bin/cleanup_photos.php`
  * `tests/Unit/CleanupPhotosTest.php`
* **Steps:**
  1. Implement CLI script `bin/cleanup_photos.php` querying reports with `status = 'Selesai'` and `updated_at < NOW() - INTERVAL 7 DAY`.
  2. For matching records with existing photo files, call `unlink()` on the local file path in `public/uploads/` and update database column `photo = NULL`.
  3. Log execution summary output to `STDOUT`.
  4. Write unit test mocking file creation and verifying cleanup logic.
* **Acceptance Criteria:** Running CLI script unlinks photos older than 7 days for resolved reports while leaving recent or pending report photos intact.
* **Verification Command:** `php bin/cleanup_photos.php` and `vendor/bin/phpunit tests/Unit/CleanupPhotosTest.php`

---

## Milestone 5: Post-MVP Follow-Ups (M5) — COMPLETED

### F-001: Real-Time Notifications via Polling (FR-008, D-016)
* **Files:** `src/Controllers/NotificationController.php`, `public/assets/js/app.js`,
  `views/user/history.php`, `views/admin/dashboard.php`, `public/index.php`,
  `tests/Integration/NotificationTest.php`
* **Done:** `/api/notifications` + `/api/admin/updates` (15s poll, hidden-tab pause),
  toast + beep, live badge/KPI updates. Verified by browser + integration tests.

### F-002: Dashboard Cleanup Button (FR-009, D-018)
* **Files:** `src/Helpers/PhotoCleaner.php`, `bin/cleanup_photos.php`,
  `src/Controllers/AdminController.php`, `views/admin/dashboard.php`, `public/index.php`
* **Done:** extracted shared helper for CLI + web; `POST /admin/cleanup-photos`
  (admin + CSRF) with flash summary. Verified end-to-end in browser.

### F-003: WIB Time Normalization (NFR-010, D-015)
* **Files:** `public/index.php`, `config/database.php`
* **Done:** PHP `Asia/Jakarta` + MySQL session `+07:00`; no data migration.

### F-004: Deployment & Release
* **Done:** Tailwind v3.4.17 build (`style.css`, CDN removed), `public/uploads/.htaccess`
  hardening (verified 403/200 on Apache), daily Task Scheduler cleanup job,
  `schema_hosting.sql` + htdocs shim (verified on real Apache), git repo with
  Conventional Commits, README with demo link.

**Final suite (Okt 2026): 57 tests, 183 assertions — all green. PHPStan level 5 clean. PHPCS PSR-12 clean.**