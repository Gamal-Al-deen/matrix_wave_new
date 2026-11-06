# Environment and Setup Guide

## Target environment

The application uses Laravel 13 with PHP 8.3, Blade, Vite, Tailwind CSS 4, Supabase PostgreSQL, and Supabase Storage. Windows/XAMPP is the local environment, but XAMPP Apache/MySQL does not imply that MySQL is used. PostgreSQL remains the application database.

## Verified local environment (2026-10-29)

| Tool | Verified value | Notes |
|---|---|---|
| PHP (application/test CLI) | `8.3.36` at `C:\xampp\php83\php.exe` | Use this executable for `artisan`, Composer scripts, and tests. |
| Laravel | `13.38.0` | Confirmed with `C:\xampp\php83\php.exe artisan --version`. |
| Composer | `2.10.3` | The `composer` shim may run under Herd Lite PHP 8.5; application commands still need the PHP 8.3 CLI. |
| Node.js | `24.19.0` | Used for Vite and the browser QA scripts. |
| npm | `11.17.0` | Used for dependency installation and builds. |
| Database (runtime) | Supabase PostgreSQL (`DB_CONNECTION=pgsql`) | The two 2026-10-29 notification migrations were applied to the configured database. |
| Database (automated tests) | SQLite in-memory / isolated file by default; isolated PostgreSQL schemas for AT-024 | `RefreshDatabase` must never run against production Supabase `public`. |
| Storage | Supabase Storage (`FILESYSTEM_DISK=supabase`) | Service-role credentials remain server-side only. |

The default `php` command may resolve to Herd Lite PHP 8.5 and report missing `pdo_pgsql`/`pgsql`. Prefix application commands with `C:\xampp\php83\php.exe` when the project must use its verified PHP 8.3 runtime. `C:\xampp\php83\php.exe -m` must list `pdo_pgsql`, `mbstring`, `fileinfo`, `openssl`, and `curl`.

## 1. Inspect before installation

In PowerShell, record:

```powershell
C:\xampp\php83\php.exe -v
C:\xampp\php83\php.exe -m
composer --version
node --version
npm --version
C:\xampp\php83\php.exe artisan --version
```

Confirm the PHP extensions required by the actual Laravel 13 dependency set: `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pcre`, `PDO`, `pdo_pgsql`, `session`, `tokenizer`, and `xml`. Use the XAMPP PHP 8.3 CLI so its `php.ini` and `pdo_pgsql` are the active ones. Do not enable MySQL as a substitute.

## 2. Environment configuration

Copy `.env.example` to `.env`, run `C:\xampp\php83\php.exe artisan key:generate`, and set server-side values for PostgreSQL/Supabase and Storage. Use placeholders in documentation and never commit `.env`.

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=pgsql
DB_HOST=<supabase-host>
DB_PORT=5432
DB_DATABASE=<database-name>
DB_USERNAME=<database-user>
DB_PASSWORD=<secret>
SUPABASE_URL=<project-url>
SUPABASE_SERVICE_ROLE_KEY=<server-only-secret>
FILESYSTEM_DISK=supabase
```

Use the provider's recommended direct or pooler connection settings, SSL requirements, and IPv4/IPv6 guidance. Never paste secrets into Git, JavaScript, Blade, screenshots, or logs. Production uses `APP_DEBUG=false`, HTTPS, secure cookies, managed secrets, backups, and restricted network access.

## 3. Install and run

After verifying the existing project and lockfile:

```powershell
composer install
npm install
C:\xampp\php83\php.exe artisan migrate:status
C:\xampp\php83\php.exe artisan migrate
npm run build
C:\xampp\php83\php.exe artisan storage:link   # only if the chosen local adapter requires it
C:\xampp\php83\php.exe artisan serve
```

Use `npm run dev` during frontend development. Do not run destructive reset commands against shared or production Supabase. Apply migrations in dependency order and back up before production changes.

### Notification migrations (2026-10-29)

The notification system adds two additive migrations:

- `2026_10_29_000001_create_notifications_table.php` creates the UUID `notifications` table used by Laravel database notifications.
- `2026_10_29_000002_add_browser_notifications_enabled_to_users_table.php` adds the per-user browser-pop-up preference.

Run `C:\xampp\php83\php.exe artisan migrate` once against the configured database and confirm both appear in `migrate:status`. The migrations add schema only; they do not modify existing rows. Do not use `migrate:fresh`, `db:wipe`, or `RefreshDatabase` on the production Supabase database.

No queue worker, WebSocket server, or third-party notification provider is required. Notifications are stored in PostgreSQL and displayed in the application. Browser pop-ups use the browser Notification API only after the user enables the site preference and grants permission; they do not work while the browser is closed.

## 4. Storage verification

Create the required public asset structure or buckets according to the chosen adapter. Log in as an administrator, upload a small valid image, confirm its metadata/path in PostgreSQL and public rendering, replace it, and confirm the old object is removed only when unreferenced. Test rejection of a renamed executable, oversized file, invalid MIME type, and unauthenticated upload. Service-role credentials stay server-side.

## 5. Tests, build, and browser checks

Use the project's verified commands:

```powershell
C:\xampp\php83\php.exe artisan test
C:\xampp\php83\php.exe vendor\bin\pint
C:\xampp\php83\php.exe artisan view:cache
npm run build
C:\xampp\php83\php.exe artisan route:list
C:\xampp\php83\php.exe artisan config:clear
C:\xampp\php83\php.exe artisan cache:clear
```

Run `view:cache` and `npm run build` sequentially, not in parallel. Both touch generated files; a parallel run can let Tailwind scan while Blade templates are being replaced and can produce an incomplete CSS bundle.

Expected current totals: **278 tests / 1,507 assertions passing** on the SQLite in-memory configuration. AT-024 additionally passed **278/278 tests / 1,507 assertions** on isolated Supabase PostgreSQL schemas, with zero failures/skips. The notification subset is 13 tests / 97 assertions.

### AT-024 isolated PostgreSQL/Supabase procedure and result

AT-024 must not use the production `public` schema with `RefreshDatabase`. Use a dedicated schema and set the search path explicitly with the PHP 8.3 CLI:

```powershell
$env:DB_CONNECTION='pgsql'
$env:DB_DATABASE='postgres'
$env:DB_URL=''
$env:DB_SEARCH_PATH='matrix_wave_test_at024_1'
C:\xampp\php83\php.exe vendor\phpunit\phpunit\phpunit --colors=never
```

The verified run split the suite into four non-overlapping filters to avoid the remote-pooler latency limit. Each partition used its own schema:

| Partition | Schema | Tests | Assertions | Result |
|---|---|---|---:|---:|---|
| Group 1 | `matrix_wave_test_at024_1` | 74 | 405 | Passed |
| Group 2 | `matrix_wave_test_at024_2` | 66 | 377 | Passed |
| Group 3 | `matrix_wave_test_at024_3` | 64 | 313 | Passed |
| Group 4 | `matrix_wave_test_at024_4` | 74 | 412 | Passed |
| **Total** | **plus setup/probe schema** | **278** | **1,507** | **0 failed, 0 skipped** |

The setup/probe schema is `matrix_wave_test_at024`; it confirmed the `pgsql` connection and `current_schema()` but was not one of the four final partitions. All 17 migrations ran in each test schema. Each isolated schema and `public` had 23 base tables after the run, and no test process or active query remained connected to the isolated schemas.

The real storage smoke test used the `supabase` disk: upload `_at024_smoke/<uuid>.txt`, verify existence, read back the exact content, delete it, and verify `exists_after_delete=no`. No temporary object remains.

PostgreSQL portability corrections were limited to tests: `CatalogTest`, `CheckoutTest`, and `OrderWhatsAppTest` use `forceCreate` for the singleton `site_settings.id`; `NotificationTest` selects the login notification by type instead of relying on an ambiguous same-transaction `created_at` order. No application business logic changed for AT-024. The five isolated schemas are retained pending explicit cleanup approval; the runtime application does not use them. The full suite evidence is partitioned because the uninterrupted single-process attempt exceeded 30 minutes; this is documented in `ACCEPTANCE_TESTS.md`.

For browser QA, use an isolated SQLite database and a dedicated server rather than the configured Supabase database:

```powershell
$env:DB_CONNECTION='sqlite'
$env:DB_DATABASE='C:\Users\<user>\AppData\Local\Temp\opencode\matrix-wave-qa.sqlite'
$env:APP_URL='http://127.0.0.1:8101'
$env:SESSION_DRIVER='file'
$env:CACHE_STORE='file'
C:\xampp\php83\php.exe artisan serve --host=127.0.0.1 --port=8101
```

Create the isolated schema with the normal migrations, seed/register only disposable QA users, and use a separate Chrome profile per run. The production Supabase database must not be populated by visual QA.

For browser notification checks, grant notification permission in the browser context (for Chrome/CDP, set the permission before loading the page), enable the preference on `/account`, keep the page open, and wait for the 60-second poll or trigger a poll. The in-app bell and `/notifications` page remain authoritative even when permission is denied or pop-ups are disabled.

## 6. Troubleshooting

* **`could not find driver` / missing PostgreSQL driver:** run the command with `C:\xampp\php83\php.exe`, confirm `pdo_pgsql` is enabled in the active CLI `php.ini`, and restart the shell. Do not switch to MySQL silently.
* **Commands use the wrong PHP:** `php -v` may resolve to Herd Lite PHP 8.5. Prefix commands with `C:\xampp\php83\php.exe` and confirm `artisan --version` reports Laravel 13.38.0.
* **TLS/connection timeout:** verify Supabase host/port, SSL mode, pooler/direct endpoint, firewall, and provider status; do not switch to MySQL silently.
* **Composer platform error:** compare PHP version/extensions with Laravel 13 constraints; do not downgrade major Laravel without documenting the blocker.
* **Unknown `notifications` table or `browser_notifications_enabled` column:** run the two 2026-10-29 migrations. Login, registration, and order-status notifications require the table; the account page requires the column.
* **No browser pop-up:** confirm the site preference on `/account` is enabled, the browser has granted notification permission, the page is open, and the poll has run. Check the operating system's notification/Do Not Disturb settings. The site cannot deliver a pop-up after the browser is closed.
* **Badges are stale:** the server-rendered count appears on load and is refreshed by `/notifications/poll` every 60 seconds. Confirm the user is authenticated and the endpoint returns JSON rather than a login redirect.
* **Assets not styled:** run `npm install`, then `npm run build` (or `npm run dev`), inspect the Vite/Tailwind configuration, and clear caches. Do not run `npm run build` concurrently with `view:cache`.
* **Blade changes not visible:** run `C:\xampp\php83\php.exe artisan view:clear`, then `view:cache` after the final template edit. A Blade syntax error is reported by `view:cache`.
* **Storage upload denied:** verify server-side credentials, bucket/path policy, MIME/size validation, and admin authorization; never place the service key in browser code.
* **Migration failure:** read the first database error, preserve the failed migration, correct the dependency/schema issue, and rerun only after checking whether partial changes exist. Do not reset a shared database.
* **Arabic display issue:** verify UTF-8, `lang/dir`, font loading, RTL CSS, print font readiness, and browser console errors.
* **WhatsApp link issue:** normalize the configured number, URL-encode Arabic text, and test both desktop and mobile; opening a URL is not delivery.

## 7. Deployment preparation

Set production environment values through a secret manager, disable debug, force HTTPS, configure trusted hosts, run migrations with review, build assets, verify storage permissions, configure backups/monitoring, seed only non-secret defaults, and execute the release acceptance suite. Keep local and production configuration clearly separate.

Run the final sequence in this order:

```powershell
C:\xampp\php83\php.exe artisan migrate --force
C:\xampp\php83\php.exe artisan view:cache
npm run build
```

The notification poller adds a small authenticated GET request per open tab every 60 seconds. Capacity planning should account for that traffic; no long-lived connections or queue workers are used by the notification feature.
