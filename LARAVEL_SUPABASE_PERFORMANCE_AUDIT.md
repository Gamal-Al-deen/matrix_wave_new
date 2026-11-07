# Laravel + Supabase Performance Audit — Matrix Wave

**Project:** Matrix Wave (Arabic RTL e-commerce storefront)
**Repository root:** `d:\Recovered\matrix_wave_new`
**Audit type:** Read-only performance audit (no code, schema, configuration, or dependency changes were made)
**Report language:** English
**Status of deliverable:** Final

> **Audit-only declaration.** No source file, configuration file, database schema, index, record, migration, or dependency was created, modified, or deleted during this audit. No optimization was implemented. No load or stress test was executed. No credential, key, token, or secret is reproduced in this document. All findings are derived from static inspection of the repository and safe read-only shell commands (directory listing and build-asset size listing).

---

## 1. Executive Summary

Matrix Wave is a Laravel 13 e-commerce application with an Arabic (RTL) storefront, an administrator back-office, a session/database shopping cart, an orders/checkout flow, and product imagery stored on **Supabase Storage** (S3-compatible disk) while relational data lives in **Supabase PostgreSQL**.

### Overall architectural assessment

| Area | Status | Headline finding |
|---|---|---|
| Laravel configuration | NEEDS IMPROVEMENT | Correct framework defaults, but no production config/route/view caching artifacts are present and the local environment runs with `APP_DEBUG=true`. |
| Supabase PostgreSQL connection | NEEDS IMPROVEMENT | Direct PDO connection; no evidence of Supavisor/pooling, persistent connections, or connection timeouts configured. Region proximity unverifiable. |
| Database query design | GOOD | Consistent eager loading; dashboard uses single-round-trip aggregates; cart/checkout use transactions with `lockForUpdate`. |
| Database indexes | NEEDS IMPROVEMENT | Strong coverage on `products`, `orders`, `cart_items`; weakest on `notifications` (no `read_at`/`created_at` index) and search cannot use the existing `lower(name)` index. |
| Product retrieval & pagination | GOOD | All public and admin listings are paginated and eager-loaded; no `Product::all()` usage found. |
| Image storage & optimization | PROBLEM DETECTED | Images are stored and served at original resolution with **no resizing, compression, format conversion, thumbnails, or Supabase transformation URLs**. |
| Caching | NOT IMPLEMENTED | Zero `Cache::` usage in application code; homepage/categories/banners/settings are re-queried on every request. |
| Shopping cart | GOOD (with duplication) | Correct transactions and authoritative pricing; `CartService::count()` is executed twice per page (header + bottom nav). |
| Frontend | GOOD (with gaps) | Small bundles (CSS ≈117.7 KB, JS ≈22.1 KB), self-hosted fonts, lazy images; no responsive images and no long-lived cache headers for static assets. |
| Server / hosting | UNABLE TO VERIFY | Production web server, PHP-FPM, OPcache, HTTP/2+, CDN and Supabase region distance cannot be inspected from the repository. |
| Queues & background processing | NOT IMPLEMENTED | Queue driver is `sync` locally / `database` in `.env.example`; no job classes exist; all notifications are written synchronously. |
| Security ↔ performance | NEEDS IMPROVEMENT | Debug mode on, verbose logging, no rate limiting on cart/notification endpoints, but otherwise sound. |

### High-level architectural risk score

**Moderate (≈ 5 / 10).** This is an **architectural assessment, not a measured benchmark.** The application is structurally sound — pagination, eager loading, transactions, and query aggregation are implemented well. The principal risks are (a) the **complete absence of image resizing/delivery optimization**, which degrades bandwidth and LCP as the catalog grows, (b) the **complete absence of application caching** for data that changes rarely, and (c) **synchronous notification writes** plus **redundant per-page queries**. None of these are runtime-confirmed degradations; they are code-level findings with the confidence levels stated per issue.

### Top 5 issues by expected impact

1. **PERF-01 (HIGH)** — No server-side image resizing/compression/thumbnails; full-resolution originals are delivered to cards, thumbnails, and detail pages.
2. **PERF-02 (HIGH)** — No application-level caching anywhere; homepage, category list, banners, footer social links and settings are re-queried on every request.
3. **PERF-03 (MEDIUM)** — Notification writes run synchronously inside HTTP requests (registration, login, order placement, status change) because the queue driver is `sync`/`database` with no worker and no `ShouldQueue` jobs.
4. **PERF-04 (MEDIUM)** — Redundant per-page global queries: `CartService::count()` runs twice per page; the header adds an unread-notifications count; the footer runs a `SocialLink` query on every page.
5. **PERF-05 (MEDIUM)** — Product name search uses a leading-wildcard `LIKE`/`ILIKE`, so the existing `products_name_lower_index` cannot be used, producing sequential scans on `products`.

---

## 2. Project Architecture Overview

### 2.1 Technology stack (verified)

| Layer | Technology | Evidence |
|---|---|---|
| Framework | Laravel `^13.17` | `composer.json` → `require.laravel/framework` |
| PHP | `^8.3` | `composer.json` → `require.php` |
| Templating | Blade (server-rendered) | `resources/views/**/*.blade.php` |
| CSS | Tailwind CSS `^4` (via `@tailwindcss/vite`) | `package.json`, `resources/css/app.css` (`@import 'tailwindcss'`) |
| Build tool | Vite `^8` + `laravel-vite-plugin ^3.1` | `package.json`, `vite.config.js` |
| Frontend JS | Vanilla JavaScript (no framework) | `resources/js/app.js`, zero JS framework in `package.json` |
| Fonts | Self-hosted Tajawal (Bunny source) | `vite.config.js` (`bunny('Tajawal', …)`), build output contains `tajawal-*.woff2` |
| Database | PostgreSQL (Supabase) via `pgsql` PDO driver | `.env` `DB_CONNECTION=pgsql`; `config/database.php` `pgsql` block |
| File storage | Supabase Storage via S3-compatible disk `supabase` | `config/filesystems.php`, `config/media.php` (`disk = supabase`) |
| Auth | Laravel session guards (customer + admin role) | `routes/web.php`, `EnsureUserIsAdmin.php`, `UserRole` enum |
| Session | File driver locally / Database in `.env.example` | `.env` `SESSION_DRIVER=file`; `config/session.php` |
| Cache | File driver locally / Database in `.env.example` | `.env` `CACHE_STORE=file`; `config/cache.php` |
| Queue | `sync` locally / `database` in `.env.example` | `.env` `QUEUE_CONNECTION=sync`; `config/queue.php` |
| Redis | Configured but **not used** | `config/database.php`, `config/cache.php`, `config/queue.php` define Redis; active drivers are file/database |

### 2.2 Runtime environment (from `.env`, non-secret keys only)

- `APP_ENV=local`, `APP_DEBUG=true`, `APP_URL=http://localhost:8000`, `APP_LOCALE=ar`
- `LOG_CHANNEL=stack`, `LOG_LEVEL=debug`
- `BROADCAST_CONNECTION=log`
- `FILESYSTEM_DISK=supabase`
- `DB_CONNECTION=pgsql`, `DB_SSLMODE=require`

> **Important caveat:** `.env` reflects a **local development** environment. Production values for `APP_ENV`, `APP_DEBUG`, session/cache/queue drivers, and asset caching are **not present in the repository** and therefore cannot be verified. `APP_DEBUG=true` is normal locally but must be `false` in production (see PERF-16).

### 2.3 Application structure

- **Controllers** (`app/Http/Controllers`): 28 controllers including `HomeController`, `CatalogController`, `CartController`, `CheckoutController`, `OrderController`, `InvoiceController`, `NotificationController`, and an `Admin/` sub-namespace (products, categories, customers, orders, wallets, banners, settings, social links, about).
- **Services** (`app/Services`): `Cart\CartService`, `Orders\OrderService`, `Orders\OrderStatusService`, `Orders\OrderWhatsAppService`, `Orders\AdminOrderIndicator`, `Dashboard\DashboardMetricsService`, `Notifications\{LoginNotifier, RegistrationNotifier}`, `Storage\ImageStorageService`, `Invoices\InvoiceSvgService`.
- **Models** (`app/Models`): `Product` (UUID, `SoftDeletes`), `Category`, `Cart`, `CartItem`, `Order`, `OrderItem`, `ProductImage`, `Wallet`, `Banner`, `SiteSetting`, `SocialLink`, `User`.
- **Policies** (`app/Policies`): 9 policies for authorization.
- **Notifications** (`app/Notifications`): 5 database-channel notifications.
- **Console commands** (`app/Console/Commands`): `CreateAdminCommand`, `StorageCheckCommand`.
- **Migrations** (`database/migrations`): 17 migrations (users, cache, jobs, categories, products, product_images, carts/cart_items, wallets, orders, order_items, site_settings, banners, social_links, about_page_content, order_status_history, notifications, plus a `browser_notifications_enabled` column).
- **Provider**: a single application provider (`app/Providers/AppServiceProvider.php`), registered in `bootstrap/providers.php`.

---

## 3. Current Application Request Lifecycle

Trace of a public page request (e.g. `GET /` or `GET /products`):

1. **Web server → front controller.** Requests are routed by `public/.htaccess` (Apache) to `public/index.php`, which boots the framework from `bootstrap/app.php`.
2. **Global middleware.** `bootstrap/app.php` appends `App\Http\Middleware\SecurityHeaders` to every response (sets `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, and HSTS in production). Standard `web` middleware group (session, CSRF, cookies) applies.
3. **Route resolution** (`routes/web.php`): `home` → `HomeController@index`; `products.index` → `CatalogController@index`; `products.show` → `CatalogController@show`.
4. **Controller queries** (see §6). Public product listings use `Product::query()->publiclyVisible()->with(['category','images'])->paginate(…)`.
5. **View rendering** (Blade). Every public page extends `resources/views/layouts/app.blade.php`, which renders:
   - `<x-header>` → calls `SiteSetting::current()` and `app(CartService::class)->count()`; for authenticated users also `auth()->user()->unreadNotifications()->count()` unless a value is passed.
   - `<x-footer>` → calls `SiteSetting::current()` and executes `SocialLink::query()->active()->orderBy(...)->get()` on **every** page.
   - `<x-mobile-bottom-nav>` → calls `app(CartService::class)->count()` **again**.
   - `<x-toast-stack>` → reads flash messages from the session.
6. **Asset delivery.** In layout: `@vite(['resources/css/app.css','resources/js/app.js'])` when a manifest or hot file exists; fonts injected via `@fonts`.
7. **Response.** HTML is returned; static assets under `public/build` are served directly by the web server.
8. **Client-side JS** (`resources/js/app.js`) attaches behaviour: mobile menu, admin sidebar, modals, carousel, add-to-cart via `fetch` (returns JSON with `cart_count`), draft image upload, notification polling every 60 s (`[data-notification-poller]` → `notifications.poll`), and the toast system.

**Image retrieval path.** Product images are **not** proxied by Laravel. `ProductImage.public_url` is stored at upload time and rendered directly in `<img src>`, pointing at the Supabase public object URL built by `ImageStorageService::publicUrl()`:

```
{SUPABASE_URL}/storage/v1/object/public/{bucket}/{path}
```

So image bytes are delivered by Supabase's storage/CDN endpoint, and Laravel only serves HTML/JSON.

---

## 4. Laravel Configuration Analysis

| Item | Finding | Status |
|---|---|---|
| Locale / timezone | `config/app.php` → locale `ar`, timezone `UTC` | GOOD |
| Debug mode | `.env` → `APP_DEBUG=true` (local). Production value unverifiable. | NEEDS IMPROVEMENT |
| Logging | `config/logging.php` default `stack` → `single`; `.env` `LOG_LEVEL=debug` | NEEDS IMPROVEMENT |
| Cache store | `config/cache.php` default `database`; `.env` `file`; `serializable_classes=false` | GOOD (config) / NOT USED (code) |
| Session store | `config/session.php` driver `database` in config; `.env` `file`; lifetime 120 min; `encrypt=false` | NEEDS IMPROVEMENT (see PERF-11) |
| Queue | `config/queue.php` default `database`; `.env` `sync`; `retry_after=90`; `after_commit=false` | NEEDS IMPROVEMENT |
| Filesystem | default `local`; `supabase` S3 disk with `throw=true`, `use_path_style_endpoint=true` | GOOD |
| Maintenance driver | `file` | GOOD |
| Providers | single `AppServiceProvider` registering two event listeners | GOOD (lean) |
| Pagination views | default Tailwind pagination view present (`resources/views/vendor/pagination/tailwind.blade.php`) | GOOD |
| Composer autoload | `optimize-autoloader: true`, `preferred-install: dist` | GOOD |
| Build artifacts | `bootstrap/cache/` contains only `.gitignore` → **no** `config.php`, `routes-v7.php`, `events.php`, or `packages.php` present in this working copy | NEEDS IMPROVEMENT |

**Observation.** `bootstrap/cache/` holds only `.gitignore`, i.e. no cached configuration/routes/events are present in the repository working copy. Whether the production deployment runs `php artisan optimize` (config + route + event + view caching) **cannot be confirmed from the repository**, but the absence of any artifacts is a signal worth verifying (see PERF-08 and §14).

**Dev-only tooling observed.** `composer.json` dev dependencies include `laravel/pail` (log tailing), `laravel/pint` (formatting), and `laravel/pao`. These are `require-dev` only and carry no production runtime cost. `phpunit ^12.5` is the test runner (not Pest).

---

## 5. Supabase PostgreSQL Connection Analysis

### 5.1 Connection configuration

`config/database.php` → `connections.pgsql`:

```php
'pgsql' => [
    'driver' => 'pgsql',
    'url' => env('DB_URL'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '5432'),
    'database' => env('DB_DATABASE', 'laravel'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => env('DB_CHARSET', 'utf8'),
    'prefix' => '',
    'prefix_indexes' => true,
    'search_path' => env('DB_SEARCH_PATH', 'public'),
    'sslmode' => env('DB_SSLMODE', 'prefer'),
],
```

- **PDO PostgreSQL:** confirmed — Laravel's `pgsql` driver uses PDO.
- **Direct connection:** confirmed — no custom connector, no read/write split, no proxy.
- **SSL:** `.env` sets `DB_SSLMODE=require`, which is correct for Supabase.
- **Search path:** defaults to `public`.

### 5.2 Pooling, persistence, timeouts

| Question | Finding | Evidence |
|---|---|---|
| Supavisor connection pooling configured? | **No evidence.** No `DB_URL` pointing at a pooler port (e.g. `:6543`) is present in `.env` (only `DB_CONNECTION`, `DB_SSLMODE` are visible for the DB block); the `pgsql` config has no pooling keys. | `config/database.php`; `.env` |
| Session pooling vs transaction pooling? | **UNABLE TO VERIFY** — depends on the Supabase connection string/port used at runtime, which is not in the repository. | — |
| Persistent connections enabled? | **No.** `PDO::ATTR_PERSISTENT` is not set anywhere; Laravel defaults to non-persistent. | grep of `app/` and `config/` |
| Connection timeouts / retries? | **Not configured.** No `options`/`PDO::ATTR_TIMEOUT` or retry wrapper for the DB connection. | `config/database.php` |
| Excessive connections? | **POTENTIAL** — under PHP-FPM with one process per request, each request opens a fresh TCP+TLS connection to Supabase unless a pooler is used. This is the classic Laravel→Supabase latency contributor. Requires runtime verification. | Inference from config; **UNVERIFIED** |

**Implication.** When Laravel and Supabase are in different regions, every request pays TLS handshake + round-trip cost to establish the connection. Supabase recommends using the **Supavisor pooler** (transaction mode, port `6543` for IPv4 pooler) for serverless/short-lived PHP connections. Whether Matrix Wave uses it is **unverified**.

### 5.3 Redis

Redis is fully configured in `config/database.php` (`client = phpredis`, retries/backoff), `config/cache.php` (a `redis` store), and `config/queue.php` (a `redis` connection) — but **none of these are active**: the environment uses `file`/`database` for cache and session and `sync`/`database` for queue. Redis is therefore **configured but unused** (see PERF-09).

---

## 6. Database Query Audit

Queries were inspected per operation. The application uses Eloquent throughout with no raw SQL except two `whereRaw('1 = 0')` guards and the dashboard aggregates.

### 6.1 Public listing — `CatalogController@index` (`/products`)

```php
$categories = Category::query()
    ->active()
    ->withCount(['products' => fn (Builder $q) => $q->publiclyVisible()])
    ->orderBy('name')
    ->get();

$products = Product::query()
    ->publiclyVisible()
    ->with(['category', 'images'])
    ->when($query !== '', fn (Builder $b) => $b->whereLike('name', '%'.$escaped.'%'))
    ->when($categorySlug !== '', /* where category_id, or whereRaw('1 = 0') */)
    ->when($sort === 'price_asc',  fn ($b) => $b->orderBy('price')->orderByDesc('created_at'))
    ->when($sort === 'price_desc', fn ($b) => $b->orderByDesc('price')->orderByDesc('created_at'))
    ->when($sort === 'newest',     fn ($b) => $b->latest())
    ->paginate(12)
    ->withQueryString();
```

- Eager loads `category` and `images` → **no N+1** on the listing. Status: **GOOD**.
- `withCount` on categories produces a correlated `EXISTS`/`COUNT` subquery per category in a single statement → **no N+1**. Status: **GOOD**.
- Unknown category slug short-circuits with `whereRaw('1 = 0')` → cheap empty result. Status: **GOOD**.
- `whereLike` with a leading `%` → sequential scan (see §7 and PERF-05). Status: **NEEDS IMPROVEMENT**.

**Round trips for `/products`:** 1 (categories with count) + 1 (paginated products) + 1 (`with(['category','images'])` eager-load batches) + 1 count for pagination + global layout queries (§6.7) ≈ **4–6 queries** plus global overhead.

### 6.2 Home — `HomeController@index` (`/`)

Same pattern as the catalog (`active()->withCount(...)`, `publiclyVisible()->with(['category','images'])->latest()->paginate(12)`) plus `Banner::publiclyVisible()->orderBy('sort_order')->orderBy('created_at')->get()` and `SiteSetting::current()`. Status: **GOOD**.

### 6.3 Product detail — `CatalogController@show`

```php
$product = Product::query()->publiclyVisible()->with(['category','images'])->where('slug',$slug)->firstOrFail();
$related = Product::query()->publiclyVisible()->where('category_id',$product->category_id)
    ->whereKeyNot($product->id)->with(['category','images'])->latest()->limit(4)->get();
```

- `slug` lookup uses the unique slug index → fast. **GOOD**.
- Related products: one bounded query with eager loading. **GOOD**.
- **Note:** `related` loads `category` which is identical to `$product->category` — a minor redundant eager-load (each related row re-selects its category). **LOW / INFORMATIONAL**.

### 6.4 Admin product listing — `Admin\ProductController@index`

`Product::query()->with(['category','images'])->withCount('images')->latest()->paginate(15)` — good pagination and eager loading. **GOOD**.

### 6.5 Dashboard — `DashboardMetricsService`

- `counts()` builds a single row with three `selectSub(... 'count(*)')` subqueries (orders, customers, products) → **1 round trip**. **GOOD**.
- `amountsByCurrency()` performs one `DB::table('orders')->selectRaw('currency_code,status,payment_status,SUM(total)')->groupBy(...)` scan → **1 round trip**, then all dashboard metrics are composed in PHP. **GOOD**. See PERF-06 for a scale note.

### 6.6 Orders, cart, checkout

- **Order placement** (`OrderService::place`) runs one transaction that locks `Cart` → `CartItem`s → `Product`s → `Category`s → optional `Wallet`, then `createMany` items, one status-history insert, and deletes cart items. Round trips scale with items but are bounded and use row locks appropriately. **GOOD**.
- **`notifyAdmins()`** runs **outside** the transaction and iterates admins with `notify(...)` (synchronous — see §15). **NEEDS IMPROVEMENT**.
- **Customer orders index** (`OrderController@index`): `orders()->withCount('items')->latest()->paginate(12)` **plus** a second query for the 6 latest order notifications. Two queries, both bounded. **GOOD**.
- **Admin orders index** (`Admin\OrderController@index`): filters + `paginate(15)`; `show()` eager-loads `items/user/wallet/statusHistory.changedBy`. **GOOD**.
- **Cart** — see §11.

### 6.7 Repeated/global queries per page (cross-cutting)

Every public page renders `layouts/app.blade.php`, which unconditionally resolves:

| Source | Query | Occurrences per page |
|---|---|---|
| `components/header.blade.php` | `SiteSetting::current()` (memoized per request) | 1 (first caller) |
| `components/header.blade.php` | `app(CartService::class)->count()` | **1** |
| `components/mobile-bottom-nav.blade.php` | `app(CartService::class)->count()` | **1** (duplicate) |
| `components/header.blade.php` | `auth()->user()->unreadNotifications()->count()` (when `$unreadCount` not passed) | 1 (authenticated only) |
| `components/footer.blade.php` | `SiteSetting::current()` (memoized) | 0 extra |
| `components/footer.blade.php` | `SocialLink::query()->active()->orderBy()->get()` | **1** |

Because `CartService` is resolved via `app(CartService::class)` and is **not** bound as a singleton, the container returns a **new instance each call**; `count()` therefore issues the aggregate query **twice** per page for authenticated users (guests read the session, which is cheap). The footer `SocialLink` query runs on **every** public page. These are confirmed by code inspection — **CONFIRMED** (see PERF-04).

### 6.8 Query-responsible observations summary

| Pattern | Present? | Status |
|---|---|---|
| N+1 on listings | No | GOOD |
| Missing eager loading | No (listings, cart, orders all eager-load) | GOOD |
| Over-selection of columns | No significant cases | GOOD |
| Repeated queries in loops | Yes — `CartService::mergeSessionIntoDatabase` per-item `exists()` | LOW–MEDIUM |
| Full-table scans | Search with leading wildcard; dashboard SUM scan at scale | NEEDS IMPROVEMENT |
| Lock contention | Bounded, correctly-scoped row locks in cart/order transactions | GOOD |
| Inefficient aggregation | Aggregations are pushed to SQL | GOOD |

---

## 7. Database Index Audit

Indexes were read directly from the migration files (PostgreSQL-specific statements included).

### 7.1 Confirmed indexes

| Table | Indexes | Assessment |
|---|---|---|
| `products` | `index(['category_id','status'])`, `index(['status','created_at'])`, unique `slug`, PG functional index `products_name_lower_index ON products(lower(name))` | Strong. Covers public listing (`status` + `created_at`, `category_id`+`status`) and slug lookup. |
| `product_images` | `index(['product_id','sort_order'])`, unique `storage_path` | Strong. |
| `categories` | `index('is_active')`, `index('name')`, unique `slug` | Adequate. |
| `carts` | unique `user_id` | Adequate. |
| `cart_items` | unique `['cart_id','product_id']` | Strong (also enforces dedup). |
| `orders` | `index(['user_id','created_at'])`, `index('status')`, `index('payment_status')`, `index('created_at')`, unique `order_number` | Strong. |
| `order_items` | `index('order_id')` | Adequate. |
| `order_status_history` | `index(['order_id','created_at'])` | Strong. |
| `users` | unique `email`, `index('role')` | Adequate. |
| `sessions` | `index('user_id')`, `index('last_activity')` | Adequate (Laravel default). |
| `notifications` | `uuidMorphs('notifiable')` → index on `(notifiable_type, notifiable_id)` | **Weak** (see below). |
| `cache` / `cache_locks` | PK on `key`, index on `expiration` | Adequate. |
| `jobs` | `index('queue')`; `failed_jobs` `index(['connection','queue','failed_at'])` | Adequate. |

### 7.2 Gaps and mismatches

1. **`notifications` table (PERF-07).** The table has only the morphs index. The application queries it with `unreadNotifications()->latest('created_at')`, `unreadNotifications()->count()`, and `where('type', OrderStatusChangedNotification::class)->latest('created_at')` (`OrderController@index`). There is **no index on `read_at`** and **no composite `(notifiable_type, notifiable_id, read_at)` or `(notifiable_type, notifiable_id, created_at)` index**. As notification volume grows these filters/sorts degrade toward scan-then-sort within a user's rows. **MEDIUM / HIGH CONFIDENCE.**

2. **Search cannot use the existing index (PERF-05).** `products_name_lower_index ON products(lower(name))` is a B-tree functional index. B-tree indexes only support **prefix** matching; `CatalogController` issues `whereLike('name', '%…%')`, which on PostgreSQL becomes a case-insensitive `ILIKE '%…%'`. A leading wildcard makes the functional index unusable → **sequential scan** of `products`. The index is effectively dead for the only search query in the app. **MEDIUM / CONFIRMED (code-level).**

3. **No index for `products.price` ordering (INFORMATIONAL).** `price_asc`/`price_desc` sorts are applied over already-filtered result sets; ordering by `price` over `status ∈ {available,out_of_stock}` cannot be fully served by the existing `(['status','created_at'])` index. At large catalog sizes this may cause sorts; whether a `(status, price)` index is warranted **cannot be determined without an `EXPLAIN`** (see §21).

4. **`AdminOrderIndicator` uses `created_at`** against the `orders` `created_at` index — covered. **GOOD**.

5. **Foreign keys.** All `foreignUuid(...)->constrained(...)` columns create indexes automatically; combined with the explicit composite indexes above, FK lookup coverage is good. **GOOD**.

> **Unverifiable:** index **usage statistics**, sequential-scan counts, dead tuples, and table bloat require database access. Because Supabase credentials are not available to this audit, those metrics **could not be inspected** (see §21).

---

## 8. Product Retrieval and Pagination Analysis

Answering the audit questions explicitly:

| # | Question | Answer | Evidence |
|---|---|---|---|
| 1 | Retrieve all products in one request? | No | `HomeController`, `CatalogController` use `paginate(12)` |
| 2 | Uses `Product::all()` / unrestricted retrieval? | **No.** Grep for `->all()`/`get()` in product paths shows bounded queries only. | `app/Http/Controllers` |
| 3 | Uses pagination? | Yes, everywhere (public 12, admin 15, orders 12/15, notifications 20/10, customers 10) | controllers |
| 4 | `paginate()` / `simplePaginate()` / cursor? | `paginate()` (offset) with `withQueryString()`; no cursor pagination | controllers |
| 5 | Infinite scrolling? | No | `resources/js/app.js` |
| 6 | AJAX-loaded products? | No for listings; AJAX is used only for add-to-cart and draft image upload | `app.js` |
| 7 | Repeated retrieval? | No repeated full retrievals; general layout queries repeat (see §6.7) | layouts |
| 8 | Full page reload on filter/sort? | Yes — filters/sort are plain `GET` forms that reload the page | `public/products/index.blade.php` |
| 9 | Unnecessary relationships loaded? | Minor: `related` products re-load `category` identical to the parent product's category | `CatalogController@show` |
| 10 | Load every product image immediately? | Listing images use `loading="lazy"`, but each card loads the **original-resolution** image (no thumbnails) | `components/product-card.blade.php` |
| 11 | Return unnecessary columns? | No significant over-selection | migrations/controllers |
| 12 | Filtering/sorting in PHP? | No — filtering/sorting/fuzzy-match pushed to PostgreSQL | `CatalogController` |
| 13 | Efficient search? | **No** — leading-wildcard `LIKE` (PERF-05) | `CatalogController:48` |
| 14 | Caching for listings? | **None** (PERF-02) | grep `Cache::` = 0 |

### Behavior as the catalog grows (qualitative, no invented benchmarks)

- **100 products:** current design is comfortable. Offset pagination over a small table with an index on `(status, created_at)` returns instantly.
- **1,000 products:** still fine for listings. The **search** path begins to matter because leading-wildcard `LIKE` scales linearly with table size.
- **10,000 products:** search without a supporting index becomes a visible cost; deep offset pagination (`page=500`) forces PostgreSQL to skip many rows; the `SocialLink`/cart-count/global per-page queries remain constant but are pure overhead.
- **100,000 products:** search and deep pagination are the primary risks; image bandwidth (original-resolution images) dominates user-perceived cost regardless of query speed; the dashboard `SUM(total)` group-by scan grows with order volume.

**Status:** Product retrieval and pagination — **GOOD** (search excepted).

---

## 9. Product Image Storage and Optimization Audit

### 9.1 Upload path

Uploads flow through `ImageStorageService::store()`:

```php
$path = $folder.'/'.Str::uuid().'.'.$extension;
$stream = fopen($file->getRealPath(), 'rb');
$written = $this->disk()->writeStream($path, $stream);   // raw bytes, no processing
```

Validation is enforced by `ImageRules::make()` (`config('media.*')`): MIME `jpeg/png/webp`, extensions `jpg/jpeg/png/webp`, `max_kilobytes = 5120` (5 MB), `dimensions max 4000×4000`.

### 9.2 Findings per question

| Question | Answer | Status |
|---|---|---|
| Originals uploaded without compression? | **Yes.** The file is streamed verbatim; no library intervenes. | PROBLEM DETECTED |
| Image dimensions restricted? | Yes — `max 4000×4000` (validation only, not resizing). | GOOD |
| MIME validation? | Yes. | GOOD |
| File size restricted? | Yes — 5 MB. | GOOD |
| Server-side compression? | **No.** | NOT IMPLEMENTED |
| Server-side resizing? | **No.** | NOT IMPLEMENTED |
| Format conversion? | **No.** No WebP/AVIF conversion. | NOT IMPLEMENTED |
| Multiple image sizes generated? | **No.** A single object per uploaded image. | NOT IMPLEMENTED |
| Image processing blocks HTTP response? | N/A — there is no processing; the upload is a straight stream write. | GOOD (no blocking) |
| Uploads parallel or sequential? | **Sequential** — the admin "draft" uploader (`resources/js/app.js` `uploadFile`) issues one `fetch` per file in sequence within a `files.forEach`-style loop; the multi-image `store()` loops `foreach ($files …)`. | LOW |
| Metadata unnecessarily retained? | Only `storage_path, public_url, original_name, mime_type, size_bytes` — reasonable. | GOOD |

### 9.3 Formats

Only **JPEG, PNG, WebP** are accepted. There is **no AVIF** support and **no conversion** of uploaded JPEG/PNG to WebP. Consequently, a 300 KB PNG uploaded by an administrator is delivered as a 300 KB PNG to every product card.

### 9.4 Delivery

| Question | Answer | Status |
|---|---|---|
| Supabase public URLs used? | Yes — `{SUPABASE_URL}/storage/v1/object/public/{bucket}/{path}` | GOOD |
| Signed URLs? | No (public bucket). | GOOD |
| **Image transformation URLs used?** | **No.** Supabase `/render/image/...` is not used. | NOT IMPLEMENTED |
| Supabase CDN utilized? | Implicitly, via the storage endpoint; no explicit transformation/CDN config. | UNABLE TO VERIFY |
| Appropriate cache headers on images? | Controlled by Supabase; **not** verifiable from the repo. | UNABLE TO VERIFY |
| Images requested repeatedly? | Yes — the **same full-resolution** image is used for the card, the gallery main image and the thumbnail. | PROBLEM DETECTED |
| Lazy loading? | Yes — `loading="lazy"` on card images and gallery thumbnails; the first banner is eager + `fetchpriority="high"`. | GOOD |
| Responsive image attributes (`srcset`/`sizes`)? | **No.** | NOT IMPLEMENTED |
| Explicit image dimensions (`width`/`height`)? | **No**, but containers use fixed aspect ratios (`aspect-[4/3]`, `aspect-square`), which mitigates CLS. | NEEDS IMPROVEMENT |
| Thumbnails in listings? | **No** — listings render the original file. | PROBLEM DETECTED |
| Full-resolution loaded unnecessarily? | **Yes** — gallery thumbnails (`<img src="{{ $image->public_url }}">`) load originals into ~64 px tiles. | PROBLEM DETECTED |

### 9.5 Supabase Image Transformations — assessment

Supabase Storage exposes an image transformation endpoint (`/storage/v1/render/image/public/{bucket}/{path}?width=…&quality=…`) that resizes and converts on the fly, and it is backed by caching. Because `ImageStorageService::publicUrl()` already assembles a predictable URL, adopting transformations would be a **delivery-only change** requiring **no re-upload and no schema change**:

- **Small thumbnail** (e.g. `width=400&quality=70`) for `product-card` and gallery thumbnails.
- **Medium** (e.g. `width=800–1200`) for the product detail main image.
- **Original / high-resolution** only for download or zoom.

Transformation is billed per origin image (Supabase metering), so it should be compared against a build-time thumbnail pipeline. **The recommended strategy from the brief (small/medium/original tiers) is appropriate and directly addresses PERF-01.** No change was implemented.

**Status:** Image storage & optimization — **PROBLEM DETECTED**.

---

## 10. Caching Audit

### 10.1 Explicit answers

1. **Is caching implemented?** **No.** A repository-wide grep for `Cache::`, `cache(`, `remember(` and `rememberForever` returns **zero matches** in `app/`.
2. **Which cache driver is configured?** `config/cache.php` default is `database`; `.env` sets `CACHE_STORE=file`; `.env.example` uses `database`.
3. **Is caching actually used in application code?** **No.**
4. **Which pages/queries benefit?** None at present.
5. **Important repeated queries uncached?** **Yes:** homepage categories, category counts, banners, `SiteSetting` (cross-request), footer `SocialLink`, product listings, and dashboard aggregates.
6. **Are expiration times appropriate?** N/A (no caching).
7. **Cache invalidation implemented?** N/A.
8. **Could stale product information appear?** N/A today; must be considered before caching product prices/inventory.
9. **Is Redis configured or absent?** **Configured but unused** (see §5.3).
10. **Would Redis meaningfully benefit this project?** **Marginal at current scale.** The only request-scoped memoization is `SiteSetting::current()` (container-level). Redis would help mainly for **shared session/cache across multiple app servers**, **queues**, and **atomic locks** — meaningful only when the app scales beyond a single server or once queuing is introduced.

### 10.2 Existing memoization

`SiteSetting::current()` memoizes the singleton row in the service container for the duration of a request and invalidates it on `saved`/`deleted`. This is correct request-level memoization, **not** cross-request caching. **GOOD (limited scope).**

### 10.3 Caching opportunities (evaluated, not implemented)

| Target | Change frequency | Cache suitability | Note |
|---|---|---|---|
| `SiteSetting` (singleton) | Rare | **High** | Safe to cache with `forgetCurrent()`-style invalidation on save. |
| `Category` list + `products_count` | Low | **High** | Invalidate on category/product write. |
| Banners / Social links | Low | **High** | Same pattern. |
| Product listing pages | Medium (new products) | **Medium** | Cache by query-string key with a short TTL + tag invalidation. |
| Product detail | Low–medium | **Medium** | Cache content, never the authenticated cart state. |
| Dashboard aggregates | Continuous writes | **Low** | Would go stale quickly; better to keep live. |
| **Cart, prices, inventory, orders** | Real-time | **Do NOT cache** | Must remain authoritative (see §11 and the brief's warning). |

**Status:** Caching — **NOT IMPLEMENTED**.

---

## 11. Shopping Cart Performance Audit

`CartService` implements a dual model: **guests** → session array under key `cart`; **authenticated customers** → database (`carts`, `cart_items`), always with authoritative product prices (prices are never persisted in the cart).

### 11.1 Per-operation analysis

| Operation | Implementation | Queries (approx.) | Assessment |
|---|---|---|---|
| **Add** (`add`) | `DB::transaction` → `lockedDatabaseCart()` (`firstOrCreate` + `lockForUpdate`) → `items()->firstOrCreate(...)` / `increment` | 2–4 | Correct; race-safe. |
| **Update** (`update`) | `DB::transaction` → `lockExistingDatabaseCart()` → `where('product_id')->update()` | 2–3 | Correct. |
| **Remove** (`remove`) | transaction → lock cart → delete item | 2–3 | Correct. |
| **Clear** (`clear`) | transaction → lock cart → delete items | 2–3 | Correct. |
| **Count** (`count`) | `CartItem::whereHas('cart', user_id)->sum('quantity')` | 1 | Correct, but see duplication below. |
| **Summary** (`summary`) | `Cart::where(user_id)->first()` then `items()->with(['product.category','product.images'])->get()` | 2 + eager batches | Good eager loading; no N+1. |
| **Merge on login** (`mergeSessionIntoDatabase`) | transaction → `pluck('quantity','product_id')` → loop with per-item `Product::whereKey(...)->exists()` → `updateOrCreate` | 1 + **N (exists)** + N (upsert) | The per-item `exists()` inside the loop is a repeated-query pattern. |

### 11.2 Specific findings

- **Transactions used appropriately:** every mutating operation is wrapped in `DB::transaction` with `lockForUpdate`, and `OrderService` locks the cart before creating the order so a double-submit can't create two orders. **GOOD / CONFIRMED.**
- **Unique constraints prevent duplicates:** `cart_items` has `unique(['cart_id','product_id'])`. **GOOD.**
- **AJAX used:** Add-to-cart is AJAX (`fetch` → JSON `{success, message, cart_count}`); update/remove use normal form posts with `back()`. **GOOD (partial).**
- **Full page reload after operations:** update/remove/clear reload via redirect; only *add* avoids a reload (JSON). This is acceptable but not uniform. **LOW.**
- **Duplicate count query (PERF-04):** `CartService::count()` is invoked once in `components/header.blade.php` and again in `components/mobile-bottom-nav.blade.php`. Because the service is not a container singleton, the aggregate runs **twice per page**. **CONFIRMED.**
- **`mergeSessionIntoDatabase` repeated `exists()` (PERF-13):** one `SELECT 1` per merged product inside the loop instead of a single `whereIn` fetch. **LOW–MEDIUM / CONFIRMED.**
- **`count()` uses `whereHas` + `sum`:** a two-level query where a join on `carts.user_id` (unique) would be a single join — a minor optimization. **LOW / INFORMATIONAL.**
- **Upsert opportunity:** `updateOrCreate` inside a loop could be replaced by a single upsert; a minor round-trip reduction. **LOW.**
- **Concurrency/consistency:** sound — the design deliberately avoids caching prices and locks rows during cart/order mutations, matching the brief's requirement not to cache authoritative inventory/prices. **GOOD.**

**Status:** Shopping cart — **GOOD** (with the duplication in PERF-04 and the loop query in PERF-13).

---

## 12. Laravel Backend Performance Audit

### 12.1 Eloquent ORM
- **N+1:** none found in listings, cart, orders, or admin pages (consistent `with(...)`). **GOOD.**
- **Excessive relationship loading:** minor (`related` reloads `category` in `CatalogController@show`). **LOW.**
- **Model hydration / accessors:** no heavy accessors or `$appends`. **GOOD.**
- **Model events / global scopes:** `Product` uses `SoftDeletes` and a `scopePubliclyVisible()` (an explicit scope, not a global scope). **GOOD.**
- **Repeated queries inside loops:** `CartService::mergeSessionIntoDatabase` (PERF-13); dashboard/count loops are in-PHP over already-fetched data. **LOW–MEDIUM.**

### 12.2 Controllers and services
- Business logic sits largely in services (`CartService`, `OrderService`, `DashboardMetricsService`) → controllers are thin. **GOOD.**
- **Synchronous external work in HTTP requests:** notifications (registration, login, order, status change) are **not** queued (§15). **NEEDS IMPROVEMENT.**
- **Unnecessary service initialization:** `OrderWhatsAppService::contactUrl()` is invoked in `HomeController@index` on every homepage render — cheap, but a `SiteSetting` read occurs per call (memoized). **INFORMATIONAL.**

### 12.3 Middleware
- One global custom middleware (`SecurityHeaders`) — negligible cost. **GOOD.**
- Standard `web` group (session, CSRF). `EnsureUserIsAdmin` performs one `isAdmin()` check. **GOOD.**
- **Repeated authentication checks:** `auth()->check()`/`isAdmin()` are evaluated in several Blade partials (`header`, `mobile-bottom-nav`); these read the already-resolved guard — cheap. **GOOD.**

### 12.4 Validation and serialization
- Dedicated form-request classes exist (`AddToCartRequest`, `UpdateCartRequest`, `ProductRequest`, `ProductImagesRequest`, `ProductImageReplaceRequest`, `CategoryRequest`, …). Validation is Laravel-native. **GOOD.**
- No API Resource layer — JSON is built inline (`cart.store`, `notifications.poll`), returning only the needed fields. **GOOD.**

### 12.5 PHP runtime
- PHP `^8.3`. **OPcache configuration, memory limits, and max execution time are not present in the repository** and depend on the hosting environment. **UNABLE TO VERIFY.**
- Composer autoload optimization: `optimize-autoloader: true` set in `composer.json`. **GOOD.**
- **Debug/verbose settings:** `.env` `APP_DEBUG=true` and `LOG_LEVEL=debug` (dev). **NEEDS IMPROVEMENT for production** (PERF-16).

**Status:** Laravel backend — **GOOD** overall, with the queuing and production-config exceptions noted.

---

## 13. Frontend Performance Audit

### 13.1 Measured build assets (evidence: directory listing of `public/build/assets`)

| Asset | Size |
|---|---|
| `app-*.css` | **117.7 KB** (Tailwind v4 output, minified) |
| `app-*.js` | **22.1 KB** (vanilla JS, minified) |
| Tajawal font files | 8 × `woff2` (≈8.7–10.3 KB each) + 8 × `woff` (≈11.6–13.4 KB each) |
| `fonts-*.css` | 9.0 KB |

- **JS bundle:** small (≈22 KB) — there is no JS framework and no third-party runtime. **GOOD.**
- **CSS bundle:** ≈118 KB is typical for a utility-first Tailwind build; the impact depends on gzip/Brotli (see §14) which is a server setting. **GOOD / UNABLE TO VERIFY (compression).**
- **Fonts:** self-hosted (downloaded from Bunny at build time) with `preload` for weights 400 and 700 (`vite.config.js`). This avoids a runtime third-party request — a genuine improvement over external font CDNs. **GOOD.**
- **Duplicate libraries:** none. **GOOD.**
- **Asset minification:** Vite production build produces hashed, minified files. **GOOD.**

### 13.2 Client-side behavior
- **Add-to-cart** performs a single `fetch`, updates all badges via `querySelectorAll('[data-cart-count]')`, and animates — no page reload. **GOOD.**
- **Notification polling:** `setInterval(pollNotifications, 60000)` per authenticated user. Each poll issues one request that runs an unread-count query and (for admins) the `AdminOrderIndicator` count. **LOW–MEDIUM** (constant per-user background load).
- **Search inputs have no debouncing** — but the search triggers a full-page `GET` on submit, so debouncing is not applicable; there is no live/AJAX search. **N/A.**
- **Repeated AJAX:** only add-to-cart and draft uploads; no polling of product lists. **GOOD.**
- **Event listeners:** `document.addEventListener('visibilitychange', …)` is registered both once globally (toasts) and once per carousel instance — harmless but slightly redundant. **INFORMATIONAL.**
- **Render-blocking:** CSS is loaded via `@vite` (hashed `<link>`); fonts use `preload`. No blocking third-party scripts. **GOOD.**

### 13.3 Images & Core Web Vitals (code-level observations only)

- **LCP:** on the homepage, if banners exist the first slide is `loading="eager"` + `fetchpriority="high"` — good. However, product cards deliver **full-resolution** images, and on `/products` the first row's images are the LCP candidates (lazy by default). Because originals can be large (up to 5 MB), LCP is **at risk** → **PERF-01**.
- **CLS:** containers use fixed aspect ratios (`aspect-[4/3]`, `aspect-square`), so layout shift from images is limited; but images lack explicit `width`/`height`. **GOOD (mitigated).**
- **INP:** no evidence of long tasks; add-to-cart and toasts are lightweight. **GOOD / UNABLE TO VERIFY at runtime.**

> Actual CWV values require a browser/field measurement and **were not performed** (audit-only, no runtime access). These are code-level observations.

**Status:** Frontend — **GOOD** (image delivery and static-asset cache headers excepted).

---

## 14. Server and Hosting Analysis

- **Hosting indicators:** `public/.htaccess` (Apache-style rewrite rules) is present and there is no `nginx.conf` in the repo, suggesting an **Apache-compatible** deployment. The actual host is **UNVERIFIED**.
- **Static caching:** `.htaccess` contains only rewrite rules — **no `mod_expires`/`mod_headers`** block. Therefore `public/build/*` assets are served without long-lived `Cache-Control: max-age=31536000, immutable` headers (Vite filenames are content-hashed, so this is a missed, safe win). **PERF-10.**
- **Compression (gzip/Brotli):** not configured in the repository; depends on the server. **UNABLE TO VERIFY.**
- **HTTP/2 or HTTP/3, TLS config, CDN:** not observable from the repository. **UNABLE TO VERIFY.**
- **PHP-FPM / memory / CPU / disk I/O:** not observable. **UNABLE TO VERIFY.**
- **Region proximity:** The distance between the Laravel host and the Supabase project region **cannot be determined** (no region identifiers are visible without credentials). If they differ, every request pays cross-region PDO/TLS latency. **PERF-15 / UNVERIFIED** — but Supabase offers regional poolers, and matching the app region to the DB region is a standard, high-impact fix.

**Status:** Server & hosting — **UNABLE TO VERIFY.**

---

## 15. Queue and Background Processing Audit

- **Queue driver:** `.env` → `sync`; `.env.example` → `database`; `config/queue.php` default `database`.
- **Jobs:** `app/Jobs` **does not exist** — there are **no job classes** in the application. `jobs`/`job_batches`/`failed_jobs` tables exist (Laravel default migration) but are unused because the driver is `sync` locally.
- **Job dispatching:** none — no `dispatch(...)` calls, no `ShouldQueue` implementations.
- **Notifications are synchronous.** All five notification classes (`NewOrderPlacedNotification`, `OrderStatusChangedNotification`, `NewCustomerRegisteredNotification`, `LoginNotification`, `WelcomeNotification`) implement only `via()` returning `['database']` and **do not implement `ShouldQueue`**. With a `sync` driver the database insert happens **inside the HTTP request**:
  - **Registration** (`RegistrationNotifier`) notifies **every administrator** in a loop **and** the new customer → N+1 inserts in the request (PERF-03).
  - **Login** (`LoginNotifier`) inserts one notification per first login per session.
  - **Order placement** (`OrderService::notifyAdmins`) iterates all admins **after** commit, still synchronously (PERF-03).
  - **Order status change** (`OrderStatusService@update`) notifies the customer inside the transaction closure.
- **Image processing jobs:** none (no processing exists).
- **Email jobs:** none — notifications use the `database` channel only; no `Mail` is sent (`MAIL_MAILER=array` in tests). No mail is dispatched in the audited flows.

### Operations that currently block the HTTP response

| Operation | Blocks request? | Should be async? | Risk if moved |
|---|---|---|---|
| Admin notification fan-out on order/registration | **Yes** | Yes | Low — the durable order/registration is already committed; notification failure is already caught/logged. |
| Customer order-status notification | **Yes** (inside transaction) | Yes | **Care:** it runs inside the DB transaction; moving it out must preserve the "notify only after commit" rule. |
| Image compression/resizing/conversion | N/A (feature absent) | Yes (if added) | Must run after the record exists; the resumed draft-upload UX needs a "processing" state. |
| Non-critical analytics | None exist | Yes (if added) | Low. |

**Status:** Queues & background processing — **NOT IMPLEMENTED.**

---

## 16. Security and Performance Considerations

| Topic | Finding | Status |
|---|---|---|
| Authentication overhead | Session guard; standard cost. | GOOD |
| Authorization | 9 policies via `authorize()`; admin gated by `EnsureUserIsAdmin`. | GOOD |
| Middleware duplication | None beyond the single `SecurityHeaders`. | GOOD |
| Session config | `encrypt=false`; lifetime 120 min; file driver locally. Files aren't shared across servers → multi-server would break sessions. | NEEDS IMPROVEMENT |
| CSRF | Standard `web` CSRF; AJAX sends `X-CSRF-TOKEN`. | GOOD |
| **Rate limiting** | Only `throttle:10,1` on guest `login`/`register`. **No throttle** on `cart.store`, cart update/remove, or `notifications.poll`. | NEEDS IMPROVEMENT |
| **Excessive logging** | `.env` `LOG_LEVEL=debug` and `single` channel; debug-level logging adds I/O overhead in production if left on. | NEEDS IMPROVEMENT |
| **Debug mode** | `.env` `APP_DEBUG=true`. Must be `false` in production (leaks details **and** disables some optimizations). | NEEDS IMPROVEMENT |
| Storage access policy | Public bucket by design; product images are public content. | GOOD |
| Signed URL generation | Not used (public bucket). | GOOD |
| Repeated permission checks | Policy checks run once per action; negligible. | GOOD |
| Security headers | Minimal, applied globally. | GOOD |

**Principle honored:** none of the above recommends weakening security for speed.

---

## 17. Complete Performance Issues Register

Severity: CRITICAL / HIGH / MEDIUM / LOW / INFORMATIONAL · Confidence: CONFIRMED / HIGH CONFIDENCE / POTENTIAL / UNVERIFIED.

---

**PERF-01 — No server-side image resizing, compression, thumbnails, or format conversion**
- **Component:** Image storage & delivery
- **File:** `app/Services/Storage/ImageStorageService.php` (`store()`, `replace()`), `config/media.php`, `resources/views/components/product-card.blade.php`, `resources/views/public/products/show.blade.php`
- **Location:** `ImageStorageService::store()` streams the uploaded file verbatim (`writeStream`) with no processing; `product-card.blade.php` renders `$image->public_url` (original) as the card image.
- **Evidence:** No intervention library (e.g. GD/Imagick/Intervention) appears in `composer.json`; `store()` performs `$this->disk()->writeStream($path, $stream)` on the raw temp file. Gallery thumbnails render the same original URL.
- **Why slow:** Every card, gallery, and thumbnail downloads a full-resolution (up to 5 MB / 4000×4000) original — high bytes → higher LCP and bandwidth, especially on mobile.
- **Expected impact:** Large transfer sizes per page (a 12-card page can be many MB); poor LCP.
- **Severity:** HIGH · **Confidence:** CONFIRMED · **Complexity:** Medium
- **Recommended solution:** Generate tiered derivatives — small thumbnails for cards/gallery, medium for detail, original only on demand. Either use Supabase Image Transformations (`/render/image/...?width=&quality=`) in `publicUrl()` variants, or a build-time/queue-time pipeline storing `thumb`/`medium` paths.

---

**PERF-02 — No application-level caching of rarely-changing data**
- **Component:** Caching (homepage, categories, banners, settings, footer)
- **File:** `app/Http/Controllers/HomeController.php`, `app/Http/Controllers/CatalogController.php`, `resources/views/components/footer.blade.php`, `app/Models/SiteSetting.php`
- **Evidence:** Grep for `Cache::`, `cache(`, `remember(` → **0 matches** in `app/`.
- **Why slow:** Categories (+ counts), banners, social links, and settings are re-queried on every request instead of being read from a cache with invalidation.
- **Expected impact:** Constant avoidable DB work on every page; scales with traffic.
- **Severity:** HIGH · **Confidence:** CONFIRMED · **Complexity:** Low–Medium
- **Recommended solution:** Cache `SiteSetting`, `Category` (with counts), `Banner`, and `SocialLink` with tag-based invalidation on save/delete; optionally cache rendered listing fragments with a short TTL.

---

**PERF-03 — Notification writes execute synchronously inside HTTP requests**
- **Component:** Queue / notifications
- **File:** `app/Services/Notifications/RegistrationNotifier.php`, `app/Services/Notifications/LoginNotifier.php`, `app/Services/Orders/OrderService.php` (`notifyAdmins()`), `app/Services/Orders/OrderStatusService.php`, `app/Notifications/*.php`
- **Evidence:** All notifications implement only `via()` returning `['database']` with **no `ShouldQueue`**; `.env` `QUEUE_CONNECTION=sync`.
- **Why slow:** Registration notifies **every admin** in a loop plus the customer; order placement notifies all admins after commit — all as inline DB inserts before the response returns.
- **Expected impact:** Request latency grows with the number of admins and notification volume.
- **Severity:** MEDIUM · **Confidence:** CONFIRMED · **Complexity:** Medium
- **Recommended solution:** Make notifications `ShouldQueue`, configure a real queue driver (`database` or Redis) and run a worker; keep "notify after commit" semantics (e.g. `afterCommit`).

---

**PERF-04 — Redundant global queries on every page**
- **Component:** Layout / cart / notifications / footer
- **File:** `resources/views/components/header.blade.php` (line 8: `app(CartService::class)->count()`, lines 9–11: unread count), `resources/views/components/mobile-bottom-nav.blade.php` (line 2: `count()`), `resources/views/components/footer.blade.php` (lines 9–13: `SocialLink::…->get()`)
- **Evidence:** `CartService` is resolved via `app(CartService::class)` (not a singleton) → `count()` runs **twice** per page; the footer issues a `SocialLink` query on every page; the header issues an unread-count query when `$unreadCount` is not passed.
- **Why slow:** 3–4 avoidable aggregate/select queries per page render.
- **Expected impact:** Constant per-request DB overhead across all traffic.
- **Severity:** MEDIUM · **Confidence:** CONFIRMED · **Complexity:** Low
- **Recommended solution:** Bind `CartService` as a singleton (or memoize `count()`), compute the cart count once and pass it to both nav components, and cache `SocialLink`/settings reads.

---

**PERF-05 — Product search cannot use the existing functional index**
- **Component:** Database query (search)
- **File:** `app/Http/Controllers/CatalogController.php` (line ~48), migration `2026_10_23_000002_create_products_table.php` (line 33: `CREATE INDEX products_name_lower_index ON products (lower(name))`)
- **Evidence:** `$builder->whereLike('name', '%'.$escaped.'%')` → leading wildcard → PostgreSQL `ILIKE '%…%'`; B-tree indexes do not serve leading-wildcard matches.
- **Why slow:** Sequential scan of `products` on every search.
- **Expected impact:** Search cost scales linearly with catalog size (worst at 10k–100k products).
- **Severity:** MEDIUM · **Confidence:** CONFIRMED · **Complexity:** Medium
- **Recommended solution:** Use a trigram index (`pg_trgm` + GIN on `lower(name)`) for substring search, or restrict search to prefix/`tsvector` full-text; either requires a migration and a query change.

---

**PERF-06 — Dashboard monetary aggregation scans all orders**
- **Component:** Admin dashboard
- **File:** `app/Services/Dashboard/DashboardMetricsService.php` (`amountsByCurrency()`, lines 79–83)
- **Evidence:** `DB::table('orders')->selectRaw('…, SUM(total)')->groupBy('currency_code','status','payment_status')->get()` — a full scan of `orders`.
- **Why slow:** `SUM(...)` over the entire `orders` table per dashboard view; grows with order history.
- **Expected impact:** Dashboard latency grows with order volume (single query, so bounded but increasing).
- **Severity:** LOW–MEDIUM · **Confidence:** HIGH CONFIDENCE · **Complexity:** Medium
- **Recommended solution:** Maintain a rolled-up summary (per currency/status) updated on order writes, or cache the aggregate for a short TTL.

---

**PERF-07 — `notifications` table lacks supporting indexes**
- **Component:** Database index
- **File:** migration `2026_10_29_000001_create_notifications_table.php`; queried in `app/Http/Controllers/NotificationController.php`, `app/Http/Controllers/OrderController.php`
- **Evidence:** Only `uuidMorphs('notifiable')` exists; queries filter `read_at` and order by `created_at` with no matching index.
- **Why slow:** Unread-count and latest-notification queries may scan a user's rows and sort.
- **Expected impact:** Degrades with per-user notification volume.
- **Severity:** MEDIUM · **Confidence:** HIGH CONFIDENCE · **Complexity:** Low
- **Recommended solution:** Add a composite index, e.g. `(notifiable_type, notifiable_id, read_at)` and/or `(notifiable_type, notifiable_id, created_at)`.

---

**PERF-08 — No production cache/route/view/event artifacts present**
- **Component:** Deployment / Laravel bootstrap
- **File:** `bootstrap/cache/` (contains only `.gitignore`), `composer.json`
- **Evidence:** No `config.php`, `routes-v7.php`, `events.php` present; whether production runs `php artisan optimize` is unverifiable.
- **Why slow:** Without config/route/event/view caching, Laravel re-parses configuration, routes and events, and recompiles views on first access.
- **Expected impact:** Higher per-request bootstrap cost; worst on cold processes.
- **Severity:** MEDIUM · **Confidence:** POTENTIAL (repo-state only) · **Complexity:** Low
- **Recommended solution:** Ensure the production deploy runs the standard optimize sequence (`config:cache`, `route:cache`, `event:cache`, `view:cache`) and OPcache is enabled. (Not performed here — audit-only.)

---

**PERF-09 — Redis configured but unused**
- **Component:** Infrastructure
- **File:** `config/database.php`, `config/cache.php`, `config/queue.php`; `.env`
- **Evidence:** Redis connections defined but active drivers are `file`/`database`/`sync`.
- **Why noted:** It's available infrastructure that is not exploited; relevant for sessions/queues/locks at scale.
- **Expected impact:** None today; a missed future option.
- **Severity:** INFORMATIONAL · **Confidence:** CONFIRMED · **Complexity:** Medium (to adopt)
- **Recommended solution:** Adopt Redis when moving to multiple app servers or introducing queues; not required for a single-server MVP.

---

**PERF-10 — Static assets lack long-lived cache headers**
- **Component:** Web server / static delivery
- **File:** `public/.htaccess`
- **Evidence:** Only rewrite rules; no `mod_expires`/`mod_headers`. Vite output filenames are content-hashed (e.g. `app-DdtvZbX-.js`).
- **Why slow:** Repeat visits may revalidate hashed assets unnecessarily.
- **Expected impact:** Extra conditional requests and slower repeat loads.
- **Severity:** LOW–MEDIUM · **Confidence:** HIGH CONFIDENCE · **Complexity:** Low
- **Recommended solution:** Serve `/build` with `Cache-Control: public, max-age=31536000, immutable`.

---

**PERF-11 — File-based sessions are not shareable across servers**
- **Component:** Session
- **File:** `config/session.php`; `.env` `SESSION_DRIVER=file`
- **Evidence:** `.env` uses the `file` driver locally; `.env.example` uses `database`.
- **Why slow/risky:** On a single server, fine; behind a load balancer, file sessions break sticky-session-free scaling.
- **Expected impact:** Login/cart instability if scaled horizontally without sticky sessions.
- **Severity:** LOW (single server) / MEDIUM (multi-server) · **Confidence:** HIGH CONFIDENCE · **Complexity:** Low
- **Recommended solution:** Use the `database` (or Redis) session driver in production for consistency.

---

**PERF-12 — No rate limiting on cart and polling endpoints**
- **Component:** Security ↔ performance
- **File:** `routes/web.php`
- **Evidence:** `throttle:10,1` exists on guest `login`/`register`; `cart.store`, cart update/remove, and `notifications.poll` have no throttle.
- **Why risky:** Abuse or runaway polling increases DB load.
- **Expected impact:** Potential load amplification under abuse or misbehaving clients.
- **Severity:** LOW–MEDIUM · **Confidence:** HIGH CONFIDENCE · **Complexity:** Low
- **Recommended solution:** Apply sensible throttles to mutating cart routes and the poll endpoint.

---

**PERF-13 — Per-item `exists()` query in cart merge loop**
- **Component:** Cart (login merge)
- **File:** `app/Services/Cart/CartService.php` (`mergeSessionIntoDatabase`)
- **Evidence:** `foreach ($items as $productId => $quantity) { if (! Product::query()->whereKey($productId)->exists()) … }`
- **Why slow:** One `SELECT 1` per merged product (N queries on login).
- **Expected impact:** Minor for small carts; grows with cart size.
- **Severity:** LOW–MEDIUM · **Confidence:** CONFIRMED · **Complexity:** Low
- **Recommended solution:** Fetch valid ids once with `whereIn(...)->pluck('id')` and upsert in a single statement.

---

**PERF-14 — Direct DB connection without pooling/persistence/timeouts**
- **Component:** Supabase connection
- **File:** `config/database.php` (`pgsql` block); `.env`
- **Evidence:** No pooling keys, no `DB_URL` to a pooler port visible, no `ATTR_PERSISTENT`, no timeout/retry.
- **Why slow:** Each PHP request opens a new TCP+TLS connection; cross-region latency is paid on every request.
- **Expected impact:** Per-request connection overhead; amplified by region distance.
- **Severity:** MEDIUM · **Confidence:** POTENTIAL · **Complexity:** Medium
- **Recommended solution:** Use Supabase Supavisor (transaction pooling) and, where the host is stable, consider a warm/persistent connection strategy; add PDO timeouts and retries.

---

**PERF-15 — App ↔ Supabase region distance unknown**
- **Component:** Network
- **File:** — (infrastructure)
- **Evidence:** No region identifiers available without credentials.
- **Why slow:** Cross-region round trips on every request if regions differ.
- **Expected impact:** Potentially large constant latency on all DB operations.
- **Severity:** MEDIUM–HIGH (if regions differ) · **Confidence:** UNVERIFIED · **Complexity:** Medium
- **Recommended solution:** Co-locate the application server with the Supabase region; confirm with latency measurements.

---

**PERF-16 — Development settings risky for production**
- **Component:** Configuration
- **File:** `.env` (`APP_ENV=local`, `APP_DEBUG=true`, `LOG_LEVEL=debug`)
- **Evidence:** Values above; production values not in repo.
- **Why slow/risky:** Debug mode and debug-level logging add overhead and leak detail.
- **Expected impact:** Higher overhead and information disclosure if deployed as-is.
- **Severity:** MEDIUM · **Confidence:** POTENTIAL (production values unverified) · **Complexity:** Low
- **Recommended solution:** Ensure production uses `APP_ENV=production`, `APP_DEBUG=false`, and an appropriate `LOG_LEVEL` (e.g. `warning`).

---

**PERF-17 — Search filter/sort triggers full-page reloads**
- **Component:** Frontend/interaction
- **File:** `resources/views/public/products/index.blade.php`
- **Evidence:** Filter/sort is a plain `GET` form; no AJAX/infinite scroll.
- **Why noted:** Full reloads re-run layout queries and re-download CSS/JS reuse.
- **Expected impact:** Perceived slowness on repeated filtering.
- **Severity:** INFORMATIONAL · **Confidence:** CONFIRMED · **Complexity:** Medium
- **Recommended solution:** Optional progressive enhancement (AJAX partial update of the grid) if UX requires it; not necessary for correctness.

---

**PERF-18 — Notification polling at a fixed 60 s cadence**
- **Component:** Frontend/backend
- **File:** `resources/js/app.js` (`setInterval(pollNotifications, 60000)`), `app/Http/Controllers/NotificationController.php` (`poll`)
- **Evidence:** Client polls every 60 s; each poll runs an unread count and (admins) an order count.
- **Why slow:** Constant background DB work per logged-in user.
- **Expected impact:** Linear background load with concurrent users.
- **Severity:** LOW · **Confidence:** CONFIRMED · **Complexity:** Low
- **Recommended solution:** Increase the interval, pause when the tab is hidden (partially done for toasts), or move to a cheaper counter cache.

---

## 18. Recommended Optimization Architecture

Each item evaluated against **this** project's actual architecture. Nothing was implemented.

| # | Recommendation | Relevant? Why | Problem addressed | How it fits | New infra? | Ops complexity | Extra cost | When |
|---|---|---|---|---|---|---|---|---|
| 1 | **Laravel Cache** | Yes — zero caching today | Repeated home/category/banner/settings queries | Wrap rare reads in `Cache::remember` with tag invalidation on writes | No | Low | None | **Immediate** |
| 2 | **Redis** | Marginal now | Shared sessions/locks/queues at scale | Switch cache/session/queue drivers when scaling beyond one server | Yes | Medium | Possible | Later |
| 3 | **Supabase connection pooling (Supavisor)** | Yes | Per-request connection/TLS cost | Point `DB_URL` at the pooled endpoint (transaction mode) | No (service feature) | Low | None | **Immediate/Soon** |
| 4 | **Database indexing** | Yes | Unindexed `notifications` filters; unindexable search | Add composite notification index; add trigram index for search | No | Low | None | Soon |
| 5 | **Query optimization** | Yes | `count()` duplication, merge-loop `exists()` | Singleton cart service; single `whereIn` fetch; single upsert | No | Low | None | **Immediate** |
| 6 | **Eloquent eager loading** | Already used well | Minor `related` re-load | Keep; trim redundant loads | No | Low | None | Optional |
| 7 | **Pagination / cursor pagination** | Offset pagination adequate | Deep-offset cost at scale | Consider cursor pagination only if deep paging becomes common | No | Low | None | Later |
| 8 | **AJAX-based cart operations** | Partially done | Reload on update/remove | Extend JSON responses to update/remove for no-reload UX | No | Low | None | Optional |
| 9 | **WebP conversion** | Yes | Large originals | Convert on upload/queue to WebP | No | Medium | None | Soon |
| 10 | **AVIF delivery** | Optional | Further size savings | Serve AVIF where supported via `<picture>` | No | Medium | None | Later |
| 11 | **Supabase Image Transformations** | **Yes (best fit)** | Full-size delivery everywhere | Append `width`/`quality` variants to `publicUrl()` for thumb/medium | No | Low | Per-image metering | **Immediate/Soon** |
| 12 | **Supabase Storage CDN** | Already benefiting | Delivery latency | Rely on storage endpoint/CDN, add `preconnect` | No | Low | Included | Optional |
| 13 | **Cloudflare CDN** | Optional | Cache HTML/static globally | Put the site behind Cloudflare, cache `/build` immutably | Yes (account) | Medium | Low | Later |
| 14 | **Laravel Queues** | Yes | Synchronous notification fan-out | Make notifications `ShouldQueue`; run a worker | Worker process | Medium | Low | Soon |
| 15 | **PHP OPcache** | Yes | Script recompilation | Enable/tune OPcache on the PHP-FPM host | No | Low | None | **Immediate (ops)** |
| 16 | **Composer optimized autoloading** | Yes | Autoload cost | `optimize-autoloader: true` already set; add `--classmap-authoritative` at deploy | No | Low | None | Soon |
| 17 | **Browser caching** | Yes | Repeat-visit asset cost | Far-future headers for `/build`, sensible `Cache-Control` on pages | No | Low | None | **Immediate (ops)** |
| 18 | **Gzip/Brotli compression** | Yes | Transfer size | Enable Brotli/gzip for HTML/CSS/JS at the server/CDN | No | Low | None | **Immediate (ops)** |
| 19 | **Frontend asset optimization** | Mostly done | CSS ≈118 KB | Keep Tailwind purge; lazy-load below-the-fold; consider critical CSS only if measured need | No | Low | None | Optional |
| 20 | **Database connection reuse** | Yes (see #3) | Connection setup per request | Pooling (Supavisor) + consider warm connections | No/Maybe | Medium | Possible | Soon |

---

## 19. Prioritized Implementation Roadmap

**Phase A — Immediate Optimizations (high benefit, minimal disruption)**
| Task | Priority | Benefit | Complexity | Dependencies | Risks | Infra |
|---|---|---|---|---|---|---|
| Serve Supabase image variants (thumb/medium) for cards, gallery, detail | HIGH | Large byte savings; better LCP | Low–Med | None | Transformation metering; verify URL support | None |
| Adopt Supavisor pooled DB endpoint | HIGH | Lower per-request latency | Low | Supabase pooler settings | Transaction-mode caveats with long transactions | None |
| Enable Brotli/gzip + far-future cache headers for `/build` | HIGH | Faster repeat loads and first loads | Low | Web server access | Misconfig could cache HTML incorrectly | None |
| Cache `SiteSetting`, `Category` (+counts), `Banner`, `SocialLink` | MEDIUM–HIGH | Removes constant DB work | Low | Cache driver chosen | Stale data if invalidation misses | None |
| Singleton/memoize `CartService::count()`; compute once per page | MEDIUM | Removes duplicate query | Low | None | None | None |
| Enable OPcache and run `php artisan optimize` at deploy | MEDIUM | Lower bootstrap cost | Low | Deploy access | Cached config must be cleared on env change | None |

**Phase B — Backend and Database Optimization**
| Task | Priority | Benefit | Complexity | Dependencies | Risks | Infra |
|---|---|---|---|---|---|---|
| Index `notifications` (`read_at`, `created_at` composites) | MEDIUM | Faster badge/poll queries | Low | Migration | Index write overhead | None |
| Trigram/full-text search index for `products.name` | MEDIUM | Removes sequential scans | Medium | `pg_trgm` extension; query change | Migration must be safe on large tables | Extension |
| Single-fetch cart merge (`whereIn` + upsert) | LOW–MED | Fewer login queries | Low | None | Behavior parity tests | None |
| Review deep-offset pagination at scale | LOW | Deep-page cost | Med | None | Cursor changes UX links | None |

**Phase C — Image and Storage Optimization**
| Task | Priority | Benefit | Complexity | Dependencies | Risks | Infra |
|---|---|---|---|---|---|---|
| Build-time/queue-time thumbnail + medium derivatives | HIGH | Consistent optimized delivery | Med | Job pipeline or transformations | Storage growth; backfill | Storage |
| WebP conversion of uploads | MEDIUM | Smaller files | Med | Image lib or Supabase transform | Quality tuning | None |
| Add `srcset`/`sizes` and explicit dimensions | MEDIUM | Bandwidth + CLS | Low–Med | Derived sizes exist | None | None |
| AVIF where supported | LOW | Extra savings | Med | Browser support | Fallback required | None |

**Phase D — Infrastructure Optimization**
| Task | Priority | Benefit | Complexity | Dependencies | Risks | Infra |
|---|---|---|---|---|---|---|
| Queue worker for notifications (`ShouldQueue`) | MEDIUM | Non-blocking requests | Med | Queue driver + worker | "Notify after commit" correctness | Worker |
| Redis for cache/session/queue at multi-server scale | MEDIUM (later) | Shared state, faster ops | Med | Redis instance | Connection management | Redis |
| Cloudflare CDN in front of static + HTML | LOW–MED | Global latency reduction | Med | Account + DNS | Cache invalidation | CDN |
| Tune PHP-FPM (workers, memory) based on measurement | MEDIUM | Throughput stability | Med | Host access | Over/under-provisioning | None |

**Phase E — Advanced Optimization**
| Task | Priority | Benefit | Complexity | Dependencies | Risks | Infra |
|---|---|---|---|---|---|---|
| Monitoring/APM (e.g. Laravel Nightwatch/Pulse/Telescope) | MEDIUM | Observability, real numbers | Med | Hosting support | Data retention | Optional |
| `pg_stat_statements` review + EXPLAIN-driven tuning | MEDIUM | Data-driven index/query work | Med | DB access | Must run read-only | None |
| Dashboard aggregate rollup/cache | LOW | Stable dashboard latency | Med | Order write hooks | Stale metrics | None |
| Cursor pagination for very large catalogs | LOW | Deep-page performance | Med | API/UX changes | Link compatibility | None |

---

## 20. Potential Risks and Trade-offs

- **Caching correctness.** Caching `products`, prices, or inventory risks stale/incorrect data. The brief explicitly warns against this; recommendations therefore cache only rarely-changing data and always invalidate on writes. **Cart, prices, and orders must remain uncached.**
- **Image transformation cost.** Supabase Image Transformations are metered; heavy traffic could raise cost. Alternative: pre-generate derivatives and store them (increases storage, reduces per-request cost).
- **Queuing changes ordering semantics.** Moving the order-status notification out of the transaction must preserve the "notify only after the row is committed" rule, or customers could be notified of a state that failed to persist.
- **Pooling with long transactions.** Transaction-mode poolers (Supavisor) can conflict with long-lived transactions/locks; the cart/order transactions here are short, so this is low risk but must be verified.
- **Index write overhead / migration safety.** Adding indexes on large, live tables must avoid long locks; use `CONCURRENTLY` where applicable.
- **Search index trade-offs.** A trigram index increases index size and write cost; full-text search changes matching semantics (word-based vs substring).
- **Static caching hazards.** Long-lived `Cache-Control` must be applied only to content-hashed assets, never to HTML that varies per user.
- **Session driver change.** Switching file→database/Redis changes where session state lives; do it deliberately to avoid logging users out unexpectedly.
- **Debug mode disclosure.** Leaving `APP_DEBUG=true` in production is both a performance and a security risk.

---

## 21. Missing Information and Unverified Areas

The following could **not** be inspected because they require credentials, production access, or runtime measurement. Per the brief, they are documented rather than worked around.

1. **Production environment file / server config.** `.env` inspected is the local development file; production `APP_ENV`/`APP_DEBUG`/drivers and the web server (Nginx vs Apache), PHP-FPM pool settings, OPcache configuration, memory/CPU/disk, and HTTP/2+/TLS are **unavailable**. → prevents confirming PERF-08, PERF-15, PERF-16, and the compression/HTTP-version items.
2. **Supabase database access.** Without credentials, the following **could not be run**: `EXPLAIN`/`EXPLAIN ANALYZE`, `pg_stat_statements`, index-usage statistics, sequential-scan counts, dead-tuple/bloat metrics, table sizes, and connection limits. Conclusions about index *effectiveness* on live data and about **region distance (PERF-15)** remain unverified.
3. **Supabase Storage behavior.** Whether object responses carry optimal `Cache-Control`, and whether the storage endpoint sits behind the Supabase CDN for this project, could not be inspected.
4. **Runtime performance measurements.** No request timing, no browser Core Web Vitals (LCP/INP/CLS), and no load tests were run (prohibited and unavailable). All performance statements are code-level.
5. **Production `bootstrap/cache` state.** Only the repository copy was inspected (empty except `.gitignore`); the deployed instance's cache state is unknown.
6. **Notification volume / admin count / catalog size.** Actual row counts and the number of administrators (which drives synchronous fan-out cost in PERF-03) are unknown.
7. **Redis availability.** Whether a Redis server is reachable in production is unknown (config exists; usage does not).

---

## 22. Final Technical Assessment

**Overall verdict: the application is well-architected at the code level, but under-optimized at the delivery and infrastructure levels.**

**Strengths (verified):**
- Consistent **eager loading** — no N+1 problems were found in product listings, cart, orders, or admin pages.
- **Pagination everywhere**; no unrestricted `Product::all()` retrieval.
- **Correct transaction discipline** — cart, order placement, and status changes use `DB::transaction` with `lockForUpdate`, and prices are read from the authoritative product row (never cached in the cart).
- **Efficient aggregates** — the dashboard uses single-round-trip subqueries and a single grouped scan.
- Strong **index coverage** on `products`, `orders`, `cart_items`, and `order_status_history`.
- **Lean frontend** — small bundles (CSS ≈117.7 KB, JS ≈22.1 KB), self-hosted fonts with preload, lazy images, no JS framework.
- **Security-conscious storage** — images delivered directly from Supabase (not proxied), public bucket by design, no signed-URL overhead needed.

**Principal weaknesses (verified, code-level):**
- **Images are never optimized** — no resizing, compression, thumbnails, format conversion, or Supabase transformation URLs (PERF-01). This is the single largest user-facing performance risk.
- **No application caching** — homepage/categories/banners/settings/footer data is re-queried every request (PERF-02).
- **Synchronous notifications** — no queue usage and no `ShouldQueue`, so notification fan-out blocks requests (PERF-03).
- **Redundant per-page queries** — duplicated cart count and an unconditional footer query (PERF-04).
- **Search can't use its index** — leading-wildcard `LIKE` defeats `products_name_lower_index` (PERF-05).
- **Unindexed notification filters** (PERF-07).
- **Infrastructure unknowns** — pooling, OPcache, compression, static cache headers, and region proximity are unverified or unconfigured (PERF-08, PERF-10, PERF-14, PERF-15, PERF-16).

**Highest-leverage next steps (no code was changed):** (1) deliver optimized image variants; (2) adopt Supavisor pooling and co-locate regions; (3) add caching for rarely-changing data; (4) move notification fan-out to a queue; (5) add the missing indexes; (6) enable OPcache, compression, and far-future static cache headers.

**Architectural risk score: Moderate (≈ 5/10).** This score is an **architectural judgement derived from static code inspection**, not a measured runtime benchmark, and it does not account for the unverified infrastructure factors in §21. It would likely improve materially with the Phase A items alone.

*End of report.*
