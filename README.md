# HRMS Phase 1 — Task Management + PDS System (Glass UI)

PHP (procedural-core, MVC-organized) + MySQL (InnoDB) + vanilla JS, styled with a native-CSS
**Glass Design System** (see [public/assets/css/glass.css](public/assets/css/glass.css)).

## Setup

### Option A — Web installer (recommended)

1. Point your browser at `http://localhost/hrms/public/install.php`.
2. It checks requirements (PHP version, `pdo_mysql`/`gd` extensions, writable `config`/`storage`/`uploads`), then asks for your MySQL credentials and the admin account you want.
3. On submit it creates the database (if missing), runs `database/schema.sql` + `database/seed.sql`, creates your admin user, and writes `config/app.php` for you.
4. **Delete or restrict access to `public/install.php` afterward** — it refuses to re-run once `storage/installed.lock` exists, but removing the file is safer.

### Option B — Manual setup

1. **Database**
   ```
   mysql -u root -p -e "CREATE DATABASE hris CHARACTER SET utf8mb4"
   mysql -u root -p hris < database/schema.sql
   mysql -u root -p hris < database/seed.sql
   php scripts/seed_admin.php
   ```
2. **Config** — edit [config/app.php](config/app.php) with your DB credentials (host/user/pass).
3. **Web server** — point your browser at `http://localhost/hrms/public/` (XAMPP serves `htdocs`, and `/public` is the front-controller root; `/hrms/` 302-redirects there). Requires `mod_rewrite` and `AllowOverride All`.
4. **Login** — username `admin`, password `Admin@12345` (set in `scripts/seed_admin.php`) — **change immediately**.
5. **GD extension** — required for image resizing/thumbnails in `core/Uploader.php`. Enable `extension=gd` in `php.ini` if not already active.

## Folder structure

- `/config` — DB connection, constants, environment settings.
- `/core` — Router, base Controller/Model, Auth (RBAC/session), Validator, Uploader, AuditLogger.
- `/modules/auth` — login/logout.
- `/modules/employees` — 201 file + full PDS (CS Form 212), section-by-section AJAX save, completion tracker, print view, department completion report.
- `/modules/tasks` — task CRUD, assignment, photo+caption attachments, comments, status history, Kanban board view.
- `/modules/accomplishments` — Accomplishment & Evidence: employees document completed work (title, date, related task, description, multiple photos with captions), Draft → Submitted → For Review → Approved/Returned workflow, HR/Supervisor review with comments.
- `/modules/admin` — Users, Departments, Positions management (gated by `user.manage`).
- `/modules/dashboard` — per-user PDS completion + task summary + notifications, stat cards.
- `/modules/shared` — shared glass header/sidebar/footer partials and the protected file-streaming controller.
- `/public` — front controller (`index.php`), `assets/css/glass.css` (design system), `assets/js/app.js`.
- `/uploads`, `/storage` — generated files (photos, task attachments, accomplishment evidence, logs/cache); excluded from git except `.gitkeep`.

## Upgrading an existing install

If you installed before the Accomplishments module existed, run the migration once:
```
mysql -u root -p hris < database/migrations/002_accomplishments.sql
```

## Glass Design System

All UI chrome (sidebar, header, cards, tables, forms, kanban board, dropdowns) is built from CSS
custom properties and reusable classes defined once in `public/assets/css/glass.css`:
`.glass`, `.glass-strong`, `.glass-light`, `.glass-card`, `.glass-sidebar`, `.glass-header`,
`.glass-dropdown`, `.stat-card`, `.kanban-board`/`.task-card`, `.glass-search`, `.btn`, `.badge`,
`.progress-bar`. Dark mode is available via `[data-theme="dark"]` on `<html>`/`<body>` (not yet
wired to a toggle). Respects `prefers-reduced-motion`.

## Known follow-ups (not yet implemented)

- **PDF export**: `pds/print/{id}` currently renders a print-friendly HTML page (browser "Print/Save as PDF") rather than a TCPDF/mPDF-generated, pixel-exact CS Form 212 PDF. Wire up `composer require tecnickcom/tcpdf`, then replace `modules/employees/controllers/PdsController::print()` with PDF generation + caching under `/storage/cache/pds/{employee_id}.pdf`, invalidated on `pds_*` table updates.
- **Dark mode toggle**: variables exist (`[data-theme="dark"]`) but no UI switch wired up yet.
- **OPcache / production hardening**: enable OPcache and set `env` to `prod` in `config/app.php` before deploying.
- **Notifications**: table + dashboard/header read exist; no producer yet (e.g., notify assignees when a task is created/status changes) — hook into `TaskController` when ready.
