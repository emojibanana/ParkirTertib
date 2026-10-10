# Testing Strategy & Specification - ParkirTertib

## 1. Testing Overview & Coverage Targets

To ensure high reliability, security, and maintainability for **ParkirTertib**, automated testing covers core business logic, authentication, input validation, file uploads, and data access.

* **Target Code Coverage:** `>= 80%` code coverage across core application components (`/src/Controllers`, `/src/Models`, `/src/Helpers`, `/src/Middlewares`).
* **Test Suite Framework:** PHPUnit 12.5 (`phpunit/phpunit`).
* **Static Analysis:** PHPStan 2.x (`phpstan/phpstan`).
* **Code Style Enforcement:** PHP_CodeSniffer 4.0 with PSR-12 standard (`squizlabs/php_codesniffer`).
* **Current Suite (Okt 2026):** 57 tests, 183 assertions, all green.

---

## 2. Test Types & Scope

### 2.1 Unit Tests (`tests/Unit/`)
Focuses on isolated testing of individual helper functions, models, and sanitizers without requiring HTTP or live network calls.
* **Security Test (`SecurityTest.php`):** Verifies CSRF token generation/verification using `hash_equals()`, plus HTML escaping and license plate normalization (e.g. `bk  1234  abc` -> `BK1234ABC`).
* **File Upload Helper Test (`FileUploadTest.php`):** Verifies MIME type detection via `finfo_file()`, file size limit enforcement (<= 5MB), extension whitelist validation, and randomized filename generation.
* **Photo Cleanup Test (`CleanupPhotosTest.php`):** Verifies `PhotoCleaner::run()` unlinks >7-day resolved photos (NULL-ing the column) while preserving recent/active ones, plus the dashboard cleanup action and its CSRF rejection. Runs against the isolated test schema with rollback.

### 2.2 Integration Tests (`tests/Integration/`)
Tests interaction between Controllers, Models, PDO database queries, and session state using an isolated SQLite/MySQL test database.
* **Authentication Test (`AuthTest.php`):**
  * User registration creates database record with Bcrypt hashed password.
  * Successful login generates secure session and regenerates session ID.
  * Invalid credentials reject login attempt.
* **Report Submission Rate Limit Test (`ReportSubmissionTest.php`):**
  * Submitting valid report creates database record in `report` table.
  * Submitting a 4th active report when 3 active reports exist raises validation error and rejects insertion.
* **Report History Test (`ReportHistoryTest.php`):**
  * History shows own reports with badges, notes, photo thumbnails/fallbacks; hides other users' reports; empty state renders.
* **Report Model Test (`ReportModelTest.php`):**
  * Summary counts, filtered search, atomic `updateStatus()` with audit log, repeat-offender aggregation.
* **Admin Report Management Test (`AdminReportTest.php`):**
  * Satpam updating status creates audit log entry in `report_log`.
  * Status transition enforces allowed states (`Menunggu` -> `Diproses` -> `Ditindaklanjuti` -> `Selesai`).
* **Repeat Offenders Query Test (`RepeatOffendersTest.php`):**
  * Aggregation query groups violation reports correctly by normalized `license_plate` and sorts descending by total count.
* **Notification Polling Test (`NotificationTest.php`):**
  * User endpoint reflects status changes and isolates users; admin endpoint returns counts plus newest-first list.

### 2.3 Security & Static Analysis Checks
* **SQL Injection Safety:** Verify that all model methods execute PDO parameterized queries (`$stmt->bindValue()`).
* **Cross-Site Scripting (XSS) Output Escaping:** Verify that views render dynamic inputs using `htmlspecialchars($str, ENT_QUOTES, 'UTF-8')`.
* **Access Control Check:** Verify that non-admin requests to `/admin/*` routes return HTTP 403 Forbidden.

---

## 3. Mocking Strategy & Database Setup

* **Database Strategy:** Integration tests run against an isolated test schema (`parkir_tertib_test`) reset between test runs using SQL transactions (`PDO::beginTransaction()` / `PDO::rollBack()`).
* **FileSystem Mocking:** File upload tests mock `$_FILES` arrays and write dummy binary files to a dedicated temporary directory (`tests/tmp/uploads/`), which is cleaned up after each test execution.
* **Session Mocking:** Global `$_SESSION` superglobal array is initialized and cleared in PHPUnit `setUp()` and `tearDown()` methods.

---

## 4. Execution Commands

```bash
# Run all automated tests
vendor/bin/phpunit

# Run unit tests only
vendor/bin/phpunit --testsuite Unit

# Run integration tests only
vendor/bin/phpunit --testsuite Integration

# Generate HTML code coverage report
vendor/bin/phpunit --coverage-html coverage/

# Run static analysis
vendor/bin/phpstan analyse src/ --level=5

# Check PSR-12 coding standard style
vendor/bin/phpcs --standard=PSR12 src/
```