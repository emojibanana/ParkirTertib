# Product Requirements Document (PRD) - ParkirTertib

## 1. Overview
**ParkirTertib** is a web-based participatory reporting and monitoring system for illegal and disorderly motor vehicle parking at Universitas Negeri Medan (UNIMED). The platform empowers students and campus community members to report parking violations via mobile devices, while providing campus security officers (Satpam) with a desktop dashboard to manage reports, record repeat offenders, and enforce rules through educational warnings.

## 2. Problem Statement
Rapid growth in motor vehicle usage at UNIMED leads to severe parking congestion, improper vehicle positioning (e.g., blocking access roads or locking other motorcycles in), and reduced mobility. Satpam officers face manual patrol constraints across expansive campus areas. An accessible, low-friction digital platform is needed to collect evidence-backed reports from users and streamline Satpam enforcement without costly IoT infrastructure.

## 3. Goals & Success Metrics
* **Goal 1:** Provide a simple, mobile-friendly reporting interface for campus users.
* **Goal 2:** Enable rapid Satpam response by organizing reports by location, urgency, and status.
* **Goal 3:** Track repeat traffic offenders by license plate aggregation.
* **Goal 4:** Maintain low server storage overhead through automated cleanup of resolved report media.
* **Success Metrics:**
  * Average time from report submission to Satpam status update < 30 minutes during campus hours.
  * Zero security breaches related to OWASP Top 10 vulnerabilities (SQLi, XSS, CSRF, Unrestricted File Upload).
  * Storage overhead maintained within bounds by automatically purging report photos 7 days after resolution.

## 4. Non-Goals (Out of Scope for MVP)
* IoT/sensor-based real-time parking space capacity mapping.
* Integration with official UNIMED SSO / NIM / NIP database authentication.
* Financial fine management or electronic payment processing.
* Native iOS/Android mobile applications (web responsive only).

## 5. Personas
* **Mahasiswa / Campus User (`user`):** Accesses system via smartphone browser. Wants to report improperly parked vehicles obstructing access without getting into direct confrontation.
* **Satpam / Security Officer (`admin`):** Accesses system via desktop browser. Responsible for verifying reports, physically applying warning stickers to offending vehicles, updating report statuses, and identifying repeat offenders.

## 6. Functional Requirements

### FR-001: User Authentication & Role Management
* **Description:** System shall support user registration and authentication with session handling for two distinct roles: `user` and `admin`.
* **Acceptance Criteria:**
  * **Given** an unauthenticated visitor on the registration page, **When** valid registration details (name, email, password) are submitted, **Then** an account with role `user` is created and password stored using Bcrypt hashing.
  * **Given** valid credentials on the login page, **When** submitted, **Then** a secure PHP session is initiated with appropriate role flags.
  * **Given** an authenticated user with role `user`, **When** attempting to access `/admin/*` routes, **Then** access is denied with a 403 HTTP status code.

### FR-002: Active Report Submission Rate Limiting
* **Description:** System shall enforce a maximum limit of 3 active reports per user to prevent spamming. An active report has a status of `Menunggu`, `Diproses`, or `Ditindaklanjuti`.
* **Acceptance Criteria:**
  * **Given** an authenticated `user` with 2 active reports, **When** submitting a new report, **Then** the submission is accepted.
  * **Given** an authenticated `user` with 3 active reports, **When** attempting to submit a 4th report, **Then** submission is rejected with an explicit error message: "Anda telah mencapai batas maksimum 3 laporan aktif."

### FR-003: Parking Violation Report Creation
* **Description:** System shall allow users to file a parking violation report with location, category, license plate number, description, and an uploaded photo.
* **Acceptance Criteria:**
  * **Given** a user on the report creation form, **When** selecting a location, category, inputting a license plate string (e.g., `BK 1234 ABC`), entering description text, and attaching a valid image (JPG/PNG/WebP, <= 5MB), **Then** the system saves the record with status `Menunggu` and stores the uploaded file in local disk storage with a randomized filename.
  * **Given** an uploaded file exceeding 5MB or with an invalid MIME type (e.g., PDF/EXE), **When** submitted, **Then** the upload is rejected with a validation error.

### FR-004: User Report Tracking & History
* **Description:** System shall provide users with a history list of their submitted reports and status updates.
* **Acceptance Criteria:**
  * **Given** an authenticated `user`, **When** navigating to the dashboard/history page, **Then** the system displays all reports filed by the user showing current status (`Menunggu`, `Diproses`, `Ditindaklanjuti`, `Selesai`), admin response notes, timestamp, and photo thumbnail.

### FR-005: Satpam Admin Dashboard & Report Management
* **Description:** System shall provide Satpam officers with a desktop-optimized dashboard to view, search, filter, and update status on reports.
* **Acceptance Criteria:**
  * **Given** an authenticated `admin`, **When** viewing the admin dashboard, **Then** the system presents summary cards (Total, Pending, In Progress, Resolved) and a searchable report table.
  * **Given** an `admin` viewing a specific report detail, **When** updating status (`Menunggu` -> `Diproses` -> `Ditindaklanjuti` -> `Selesai`) and adding response notes, **Then** the database is updated, timestamped, and the changes reflect on the reporter's history view.

### FR-006: Repeat Offenders Tracking
* **Description:** System shall aggregate report data by vehicle license plate to identify repeat traffic offenders.
* **Acceptance Criteria:**
  * **Given** an `admin` navigating to `/admin/repeat-offenders`, **When** the page renders, **Then** it presents a table of license plates sorted descending by total report count, including incident dates, locations, and violation categories.

### FR-007: Automated Media Storage Cleanup
* **Description:** System shall provide a CLI command script to delete local report photo files whose parent report status is `Selesai` and updated date is older than 7 days.
* **Acceptance Criteria:**
  * **Given** a report with status `Selesai` updated 8 days ago and a corresponding image file in `uploads/`, **When** the CLI cleanup script (`bin/cleanup_photos.php`) is executed, **Then** the image file is unlinked from disk, and the report `photo` column reference is cleared/set to NULL or marked deleted, while preserving report text metadata.

## 7. Non-Functional Requirements (NFR)

* **NFR-001 (OWASP A03: SQL Injection):** All database interactions must use PDO prepared statements with parameter binding. Dynamic string concatenation in SQL queries is strictly prohibited.
* **NFR-002 (OWASP A01: Broken Access Control):** Role checking (`user` vs `admin`) must occur server-side on every request handling restricted routes or endpoints.
* **NFR-003 (OWASP A02: Cryptographic Failures):** User passwords must be hashed using `password_hash()` with `PASSWORD_BCRYPT`. Plaintext passwords must never be stored or logged.
* **NFR-004 (OWASP A07: Identification/Authentication Failures):** Session identifiers must be regenerated upon login using `session_regenerate_id(true)`. Cookies must set `HttpOnly` and `SameSite=Lax` flags.
* **NFR-005 (OWASP A08: Software & Data Integrity / Upload Safety):** Image uploads must validate actual MIME content types via `finfo_file()`, enforce a strict extension whitelist (`.jpg`, `.jpeg`, `.png`, `.webp`), enforce maximum size of 5MB, and store files outside public script execution paths with randomly generated UUID/hash filenames.
* **NFR-006 (OWASP A03: Cross-Site Scripting - XSS):** All dynamic user input rendered into HTML views must be escaped using `htmlspecialchars($data, ENT_QUOTES, 'UTF-8')`.
* **NFR-007 (OWASP A01: CSRF Protection):** All state-changing POST forms must include a cryptographically secure CSRF token validated server-side.
* **NFR-008 (Performance):** Page load response times must be under 2.0 seconds on standard 3G connection conditions.
* **NFR-009 (Responsiveness):** Public and user pages must be fully responsive down to 320px screen width (Mobile-first layout). Admin interfaces must be optimized for standard desktop viewports (>= 1024px).

## 8. User Flows

### Flow 1: Student Files Violation Report
1. User logs into application on smartphone.
2. System checks active report count. If >= 3, user sees warning notice.
3. User clicks "+ Buat Laporan".
4. User uploads photo directly from smartphone camera/gallery.
5. User enters license plate, selects violation location and category from dropdowns, enters description.
6. User clicks "Kirim Laporan".
7. System validates input, checks file size/MIME type, moves file to `/uploads/`, inserts DB record (`status = 'Menunggu'`), and redirects to History with success message.

### Flow 2: Satpam Processes & Resolves Report
1. Satpam logs into Admin Dashboard on desktop PC.
2. Filter dashboard by `status = 'Menunggu'`.
3. Clicks report detail, views photo and license plate.
4. Changes status to `Diproses` while dispatching officer to site.
5. Officer attaches warning sticker to motorcycle on site.
6. Satpam updates status to `Ditindaklanjuti` and inputs response note: "Stiker teguran telah ditempelkan di lokasi."
7. Satpam marks report as `Selesai`.

## 9. Edge Cases & Handling
* **Duplicate License Plate Entry:** Formatting inconsistencies (e.g., `BK 1234 ABC` vs `BK1234ABC`). *Handling:* License plate input strings are sanitized by converting to uppercase and stripping inner spaces during storage to ensure uniform grouping in repeat offender queries.
* **File Cleanup Execution Failure:** Disk permissions block deletion. *Handling:* CLI cleanup script logs error message to system log without throwing fatal crash, continuing execution for remaining files.
* **Session Expiry During Submission:** User spends prolonged time filling form. *Handling:* Form submission detects missing session, preserves input data in session flash data if possible, and redirects to login with return path.

## 10. Glossary
* **Satpam:** Unimed Campus Security Personnel acting as system Administrators.
* **Active Report:** Any submitted report that has not yet reached `Selesai` status.
* **Repeat Offender:** A vehicle identified by license plate that appears in multiple violation reports.