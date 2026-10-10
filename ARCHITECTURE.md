# System Architecture Document - ParkirTertib

## 1. System Architecture Diagram

```mermaid
graph TD
    subgraph Client ["Client Layer (Browser)"]
        MobileUser["Mobile Browser (Mahasiswa/User)"]
        DesktopAdmin["Desktop Browser (Satpam/Admin)"]
    end

    subgraph Entry ["Web Entry Layer"]
        PublicIndex["public/index.php (Front Controller)"]
        StaticAssets["public/assets/ (Compiled Tailwind CSS, JS)"]
    end

    subgraph CoreApp ["Application Layer (PHP Native MVC Framework)"]
        Router["Router & Middleware Dispatcher"]
        AuthMiddleware["AuthMiddleware (Role & Session Validation)"]
        CSRFMiddleware["CSRFMiddleware (Token Verification)"]
        
        subgraph Controllers ["Controllers"]
            AuthController["AuthController"]
            ReportController["ReportController"]
            AdminController["AdminController"]
        end

        subgraph Helpers ["Helpers & Security Components"]
            CSRFHelper["CSRF Token Helper"]
            UploadHelper["File Upload & MIME Sanitizer"]
            ViewHelper["View Renderer & XSS Escaper"]
        end

        subgraph Models ["Data Access Models"]
            UserModel["UserModel"]
            ReportModel["ReportModel"]
            ReportLogModel["ReportLogModel"]
        end
    end

    subgraph Storage ["Persistence & Storage Layer"]
        PDOConnection["PDO Driver (Prepared Statements)"]
        MySQL DB [("MySQL / MariaDB Database")]
        UploadsFolder["uploads/ (Local Disk File Storage)"]
    end

    subgraph Maintenance ["CLI & Background Maintenance"]
        CleanupCron["bin/cleanup_photos.php (PHP CLI Cleanup Task)"]
    end

    MobileUser -->|"HTTP GET/POST"| PublicIndex
    DesktopAdmin -->|"HTTP GET/POST"| PublicIndex
    PublicIndex --> StaticAssets
    PublicIndex --> Router
    Router --> AuthMiddleware
    AuthMiddleware --> CSRFMiddleware
    CSRFMiddleware --> Controllers
    
    Controllers --> Helpers
    Controllers --> Models
    Models --> PDOConnection
    PDOConnection --> MySQL
    
    UploadHelper -->|"Store Validated Image"| UploadsFolder
    CleanupCron -->|"Unlink Files >7 Days Resolved"| UploadsFolder
    CleanupCron -->|"Clear Photo Column Reference"| PDOConnection
```

## 2. Technical Stack & Justification

| Layer | Technology | Justification |
|---|---|---|
| **Runtime / Language** | PHP 8.2+ | Native language execution without external engine wrappers; wide server compatibility. |
| **Architecture Pattern** | MVC (Model-View-Controller) | Separation of data models, router logic, and HTML presentation views. |
| **Database** | MySQL 8.0 / MariaDB 10.6 | Relational database with full support for ACID transactions and indexed SQL aggregation queries. |
| **Data Access** | Native PHP PDO | Enforces secure parameterized queries to prevent SQL Injection (OWASP A03). |
| **Frontend Styling** | Tailwind CSS v3 (CLI Standalone) | Mobile-first utility styling without heavy Node.js runtime production overhead. |
| **Client Scripting** | Vanilla JavaScript (ES6+) | Modern lightweight client-side DOM interaction and file upload preview handling. |
| **Storage Engine** | Local File System (`/uploads`) | Cost-effective and straightforward binary image storage for MVP requirements. |

## 3. Directory Structure

```
parkir-tertib/
├── bin/
│   └── cleanup_photos.php       # CLI maintenance script for image purge (>7 days)
├── config/
│   ├── database.php             # PDO database connection setup
│   └── env.php                  # Environment variable loader
├── public/
│   ├── .htaccess                # Apache rewrite rules routing to index.php
│   ├── assets/
│   │   ├── css/
│   │   │   └── app.css          # Compiled Tailwind CSS
│   │   └── js/
│   │       └── app.js           # Vanilla JS script
│   ├── uploads/                 # Local directory for uploaded photos
│   └── index.php                # Single entry point (Front Controller)
├── src/
│   ├── Controllers/
│   │   ├── AdminController.php  # Handles Satpam dashboard & repeat offenders
│   │   ├── AuthController.php   # Handles login, registration, logout
│   │   └── ReportController.php  # Handles report creation and tracking
│   ├── Helpers/
│   │   ├── CSRF.php             # Token generation and validation
│   │   ├── FileUpload.php       # MIME validation, size checking, storage
│   │   └── Sanitizer.php        # Input escaping and license plate normalization
│   ├── Middlewares/
│   │   ├── AdminMiddleware.php  # Restricts access to Satpam roles
│   │   └── AuthMiddleware.php   # Enforces authenticated user sessions
│   └── Models/
│       ├── Report.php           # Data access for reports
│       ├── ReportLog.php        # Audit trail for status changes
│       └── User.php             # Data access for user accounts
├── views/
│   ├── admin/
│   │   ├── dashboard.php        # Satpam summary dashboard & report list
│   │   ├── detail.php           # Satpam report detail & status update form
│   │   └── repeat_offenders.php # Repeat offenders aggregation view
│   ├── auth/
│   │   ├── login.php            # Login page
│   │   └── register.php         # Registration page
│   ├── layouts/
│   │   ├── header.php           # Shared header navigation
│   │   └── footer.php           # Shared footer
│   ├── user/
│   │   ├── create.php           # Report submission form (mobile-first)
│   │   ├── history.php          # User submitted reports history
│   │   └── home.php             # Welcome landing page
│   └── errors/
│       ├── 403.php              # Access forbidden error page
│       └── 404.php              # Page not found error page
├── .env.example                 # Environment configuration template
├── schema.sql                   # Database table definitions and initial seed data
└── tailwind.config.js           # Tailwind CSS configuration
```

## 4. Security Architecture (OWASP Top 10 Mitigations)

### OWASP A01: Broken Access Control
* **Session Verification:** Access to `/admin/*` routes executes `AdminMiddleware.php`, checking `$_SESSION['user_role'] === 'admin'`. Non-admin sessions receive HTTP 403.
* **IDOR Prevention:** Users viewing report details can only access reports matching `$_SESSION['user_id']`. Admins can view all reports.

### OWASP A02: Cryptographic Failures
* Passwords stored strictly using `password_hash($password, PASSWORD_BCRYPT)`.
* Authentication checks perform `password_verify($input_password, $hashed_password)`.

### OWASP A03: Injection (SQLi & XSS)
* **SQLi:** All database operations utilize PDO prepared statements with strict parameter binding (`$stmt->bindValue()`).
* **XSS:** All data rendered into views passes through `ViewHelper::escape()` using `htmlspecialchars($str, ENT_QUOTES, 'UTF-8')`.

### OWASP A07: Identification and Authentication Failures
* Session cookies configured with `HttpOnly`, `SameSite=Lax`, and `Secure` (when HTTPS enabled).
* Executing `session_regenerate_id(true)` upon authentication login prevents session fixation.

### OWASP A08: Software and Data Integrity Failures (File Upload Security)
* File validation executed in `FileUpload.php`:
  1. File size verified `<= 5,242,880 bytes` (5MB).
  2. File extension checked against whitelist (`jpg`, `jpeg`, `png`, `webp`).
  3. MIME type verified using `finfo_file(FILEINFO_MIME_TYPE)`.
  4. Uploaded file assigned a random filename (`bin2hex(random_bytes(16)) . '.' . $extension`) before moving to `public/uploads/`.
  5. PHP execution within `public/uploads/` disabled via `.htaccess`.

### OWASP A01: Cross-Site Request Forgery (CSRF)
* All state-changing HTML form submissions include a hidden `csrf_token` input.
* `CSRF::verify()` compares submitted token against `$_SESSION['csrf_token']` using `hash_equals()`.

## 5. Environment Configuration

The application reads configuration parameters from `.env` loaded into PHP system environment variables:

| Variable Name | Description | Default Value | Example Value |
|---|---|---|---|
| `APP_ENV` | Application environment state | `development` | `production` |
| `APP_URL` | Base canonical application URL | `http://localhost:8000` | `https://parkir.unimed.ac.id` |
| `DB_HOST` | Database server hostname | `127.0.0.1` | `localhost` |
| `DB_PORT` | Database server port | `3306` | `3306` |
| `DB_NAME` | Database schema name | `parkir_tertib` | `parkir_tertib` |
| `DB_USER` | Database connection username | `root` | `db_parkir_user` |
| `DB_PASS` | Database connection password | `""` | `SecurePass123!` |
| `UPLOAD_DIR` | Absolute path for file uploads | `public/uploads` | `/var/www/parkir-tertib/public/uploads` |

## 6. Architectural Decision Records (ADR)

### ADR-01: Front Controller Pattern via Single Index Entry Point
* **Context:** Navigating between multiple standalone PHP files causes duplicated header/session initialization code.
* **Decision:** Implement Front Controller pattern via `/public/index.php` using standard URL rewrite (`.htaccess`).
* **Consequence:** All HTTP requests pass through centralized routing, middleware checks, session startup, and error handling.

### ADR-02: License Plate String Normalization
* **Context:** Users input license plates with varying spaces/casing (e.g., `bk 1234 abc` vs `BK1234ABC`).
* **Decision:** `Sanitizer::normalizeLicensePlate()` converts string to uppercase and removes all internal whitespaces before saving.
* **Consequence:** Aggregation queries for repeat offender tracking run accurately on exact string matches (`BK1234ABC`).