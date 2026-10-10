# AGENTS.md - Autonomous Coding Agent Instructions

## 1. Project Summary
ParkirTertib is a web-based participatory reporting and monitoring system for motorcycle parking violations at Universitas Negeri Medan (UNIMED). It enables students to report violations via mobile browsers and provides Satpam security officers with a desktop dashboard to enforce rules and track repeat offenders.

## 2. Tech Stack & Environment
* **PHP:** 8.2+ (Native OOP & PDO; verified with 8.3.30)
* **Database:** MySQL 8.0 / MariaDB 10.6
* **Frontend:** HTML5, Vanilla JavaScript (ES6+), Tailwind CSS v3.4.17 (CLI standalone, compiled to `style.css`, no CDN)
* **Testing & Quality:** PHPUnit 12.5, PHPStan 2.x Level 5, PHPCS 4.0 PSR-12 (57 tests, 183 assertions, Okt 2026)

## 3. Mandatory Document Execution Order
Before starting work on any task, agents MUST read project documents in this exact order:
1. `DECISIONS.md` (Single Source of Truth)
2. `PRD.md`
3. `ARCHITECTURE.md`
4. `DATA_MODEL.md`
5. `UI_SPEC.md`
6. `TESTING.md`
7. `TASKS.md`

## 4. Execution Commands
```bash
# Install PHP dependencies
composer install

# Build compiled Tailwind CSS assets
./tailwindcss -i public/assets/css/app.css -o public/assets/css/style.css --minify

# Start local PHP development server
php -S localhost:8000 -t public/

# Run complete PHPUnit test suite
vendor/bin/phpunit

# Run static analysis check
vendor/bin/phpstan analyse src/ --level=5

# Run PSR-12 code style check
vendor/bin/phpcs --standard=PSR12 src/

# Run photo cleanup (CLI or dashboard button POST /admin/cleanup-photos)
php bin/cleanup_photos.php
```

## 5. Coding Standards & Conventions
* **Architecture:** MVC pattern with Front Controller routing via `public/index.php`.
* **Namespaces & PSR-4:** `App\Controllers\*`, `App\Models\*`, `App\Middlewares\*`, `App\Helpers\*`.
* **Database Security:** ALL SQL queries MUST use PDO prepared statements with bound parameters (`$stmt->bindValue()`). Raw query concatenation is strictly prohibited.
* **XSS Prevention:** ALL view output variables MUST be escaped using `Sanitizer::escape()`.
* **File Uploads:** Uploaded photos MUST be validated for MIME type via `finfo_file()`, extension whitelist (`.jpg`, `.jpeg`, `.png`, `.webp`), size (<= 5MB), and saved with randomized UUID/hash filenames.

## 6. Operational Rules

### ALWAYS:
* Check `DECISIONS.md` before making architectural choices or introducing new files.
* Verify user inputs using server-side validation.
* Format git commits using Conventional Commits (`feat:`, `fix:`, `test:`, `docs:`, `refactor:`).
* Ensure every task modifies <= 5 files and ~200 lines of code.

### ASK FIRST:
* Modifying database schema definitions in `schema.sql`.
* Adding composer dependencies not listed in `ARCHITECTURE.md`.
* Altering root folder permissions or `.htaccess` security rules.

### NEVER:
* Store plaintext passwords or use weak hashing algorithms.
* Write dynamic SQL queries with concatenated user variables.
* Bypass CSRF token checks on state-changing POST forms.
* Commit password credentials or `.env` files into git repository.

## 7. Stop Conditions
The agent MUST immediately halt execution and ask the human user for intervention when:
1. A task requirement directly conflicts with another requirement or `DECISIONS.md`.
2. A task requires introducing a new external library/dependency not listed in `ARCHITECTURE.md`.
3. Automated tests fail 3 consecutive times on the exact same error or bug.

## 8. Definition of Done (DoD)
A task is considered complete ONLY when:
- All task steps and acceptance criteria in `TASKS.md` are satisfied.
- Code passes PHPStan level 5 static analysis without warnings.
- Code passes PSR-12 code style check (`phpcs`).
- Unit and integration tests pass cleanly (`phpunit`).
- Git commit message follows Conventional Commits specification.