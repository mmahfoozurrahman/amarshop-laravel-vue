# AmarShop — E-Commerce Web Application

A complete demo store built for **Module 22** with **Laravel 13, MySQL, Bootstrap 5 and Vue 3**.
Guests can browse and fill a cart; OTP login unlocks wishlist, checkout (SSLCommerz demo) and order history;
admins manage the catalogue and orders from a separate panel.

## Feature checklist

| Area | Delivered |
|---|---|
| Home | Hero slider, brand list, category list, latest products, featured products |
| Catalogue | Product list with search/category/brand/price/sort/pagination, product detail + related, brand pages, category pages |
| Auth | Password-less **OTP login** (6 digits, 5-min expiry, 5 attempts, 60s resend), profile, logout (Sanctum tokens) |
| Cart | Guest cart (server-side via `X-Guest-Token`), add/update/remove, live totals, merge on login |
| Wishlist | Add/remove saved products (login required) |
| Checkout | Address form, server-side totals, stock reservation, demo **SSLCommerz** gateway, order creation |
| Orders | Order history with status filters, order detail, pay-again for unpaid orders |
| Admin | Password login, dashboard stats, CRUD for brands/categories/products/sliders (with uploads), order status workflow, customer activation |
| Quality | Responsive Bootstrap UI, loading/empty/error states, toasts, confirm dialogs, 49 feature tests |

## Tech stack

- PHP 8.4 · Laravel 13.38 · Laravel Sanctum (Bearer tokens)
- MySQL 8 (`module22_ecommerce`, utf8mb4)
- Vue 3 (Composition API, `<script setup>`) + Vue Router 4 + axios — **no Pinia**, state lives in composables
- Bootstrap 5.3 + Bootstrap Icons, Vite 8
- PHPUnit 12 feature tests, Laravel Pint formatting

## Requirements

PHP 8.3+, Composer 2, Node 20+, MySQL 8 (or MariaDB 10.6+).

## Installation

```bash
# 1. Backend dependencies
composer install

# 2. Environment
copy .env.example .env        # Windows
cp .env.example .env          # macOS / Linux
php artisan key:generate

# 3. Database (create it once, then migrate + seed)
mysql -u root -p -e "CREATE DATABASE module22_ecommerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate:fresh --seed

# 4. Public storage link (admin uploads)
php artisan storage:link

# 5. Frontend assets
npm install
npm run build                 # or: npm run dev while developing

# 6. Serve
php artisan serve
```

Open **http://127.0.0.1:8000**.

> The demo catalogue photos are committed in `public/images/**`, so seeding is the only step needed — see
> [Demo images & data](#demo-images--data) if you want to swap them.

> If you previously ran `npm run dev`, delete `public/hot` before using `npm run build`,
> otherwise Laravel keeps pointing at the dev server.

### Environment variables

```dotenv
APP_NAME=AmarShop
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=module22_ecommerce
DB_USERNAME=root
DB_PASSWORD=your_password

SSLCOMMERZ_MODE=demo          # demo | sandbox
SSLCOMMERZ_SANDBOX=true
SSLCOMMERZ_STORE_ID=
SSLCOMMERZ_STORE_PASSWORD=
SSLCOMMERZ_CURRENCY=BDT
```

`SSLCOMMERZ_MODE=demo` uses the in-app demo gateway (works offline, no credentials).
Set `SSLCOMMERZ_MODE=sandbox` and fill the store credentials to use the real SSLCommerz sandbox;
the success callback is then validated through the SSLCommerz validator API before an order is marked paid.

## Demo accounts

| Role | How to sign in |
|---|---|
| **Admin** | `/admin/login` → `admin@amarshop.test` / `password` |
| **Customer** | `/login` → phone `01700000000` → OTP is shown on screen (and written to `storage/logs/laravel.log`) |

New customers are created automatically on their first successful OTP verification.
Seeded data: 6 categories · 8 brands · 36 products · 3 hero slides · 2 sample orders, each with a demo photo.

## How the main flows work

**OTP login** — `POST /api/auth/request-otp` stores a hashed OTP (`otp_verifications`); in demo mode the
code is returned as `demo_otp`. `POST /api/auth/verify-otp` checks expiry/attempts, creates or reuses the
customer, merges the guest cart, and returns a Sanctum token.

**Guest cart** — the browser keeps a UUID in `localStorage` and sends it as `X-Guest-Token`.
Rows live in `cart_items` with a `guest_token`; after login they are merged into the user's rows.

**Checkout** — totals are always recomputed from DB prices, stock is locked and decremented inside a
transaction, line items are snapshotted into `order_items`, the cart is cleared, then the payment session starts.

**Demo payment** — the SPA shows a fake gateway page; Success/Fail/Cancel call
`POST /api/payment/demo/{order_number}/{result}`. Paid orders become `processing / paid`; cancelled ones are
cancelled **and stock is restored exactly once** (guarded by `stock_restored_at`).

## API overview

| Group | Endpoints |
|---|---|
| Guest | `/api/home`, `/api/products`, `/api/products/{slug}`, `/api/brands`, `/api/brands/{slug}`, `/api/categories`, `/api/categories/{slug}`, `/api/guest/cart` (GET/POST/PATCH/DELETE) |
| Auth | `/api/auth/request-otp`, `/api/auth/verify-otp`, `/api/auth/me`, `/api/auth/logout` |
| Customer | `/api/profile`, `/api/cart`, `/api/cart/merge`, `/api/wishlist`, `/api/checkout`, `/api/orders`, `/api/orders/{order_number}`, `/api/orders/{order_number}/pay`, `/api/payment/demo/{order_number}/{result}` |
| Gateway callbacks | `/api/payment/success`, `/api/payment/fail`, `/api/payment/cancel`, `/api/payment/ipn` |
| Admin | `/api/admin/auth/*`, `/api/admin/dashboard`, CRUD `/api/admin/{brands,categories,products,sliders}`, `/api/admin/orders`, `/api/admin/orders/{order}/status`, `/api/admin/users`, `/api/admin/users/{user}/status` |

Full contracts: [`project-memory/03-api-contract.md`](project-memory/03-api-contract.md).

Known traps, the code patterns to copy and a pre-flight checklist for new work live in
[`project-memory/11-pitfalls-and-guardrails.md`](project-memory/11-pitfalls-and-guardrails.md) — read it before
changing code (e.g. API redirects must be **relative** for SPA routes, forms that rely on API validation need
`novalidate`, the navbar holds a **single** `.container`).

## Project structure

```
app/Http/Controllers/
├── Admin/    # staff panel (auth, dashboard, catalogue CRUD, orders, customers)
├── User/     # OTP auth, profile, cart, wishlist, checkout, orders
└── Guest/    # public catalogue, guest cart, gateway callbacks
app/Services/ # OtpService, CartService, OrderService, SslcommerzService, ImageService
database/seeders/ # DatabaseSeeder, catalogue seeders + DemoImageSeeder (demo photos)
public/images/    # committed demo photos for the seeded catalogue + placeholder.svg fallbacks
resources/js/
├── admin/    # admin pages (login, dashboard, CRUD screens)
├── user/     # login, profile, wishlist, checkout, orders
├── guest/    # home, shop, product, brand/category, cart, demo payment, 404
└── shared/   # composables, components, layouts, services, router, utils
project-memory/  # plan, schema notes, API contract, UI spec, todo, progress log, QA checklist + guardrails
```

## Database

`users`, `otp_verifications`, `brands`, `categories`, `products`, `sliders`, `wishlists`,
`cart_items`, `orders`, `order_items` (+ Sanctum tokens, sessions, cache, jobs).
Foreign keys, unique constraints and cascade rules are defined in `database/migrations`.
See [`project-memory/02-database-schema.md`](project-memory/02-database-schema.md).

## Demo images & data

Every seeded catalogue image is a **real photo** that ships with the project: `public/images/{products,categories,brands,sliders}/`
holds 53 centre-cropped JPEGs — products `640×640`, categories `560×420`, brand logos `360×200`, hero slides
`1600×600` — plus one `placeholder.svg` per folder for records without an image. Total size is ≈2.5 MB.

```bash
php artisan migrate:fresh --seed             # full reset: catalogue + demo photos + demo orders
php artisan db:seed --class=DemoImageSeeder  # re-wire only the image columns (safe on a live database)
```

- `DemoImageSeeder` runs last in `DatabaseSeeder`: it points every demo record at its photo and deletes the generated
  SVG art it replaces. It only writes image columns, so stock, prices, orders and customers are never touched.
- **Using your own pictures:** drop the originals into `storage/app/seed-images/` (optional, not shipped) and re-run
  the seeder — `ImageService::demoPhotoFrom()` centre-crops and compresses them to the sizes above, skipping duplicate
  files and anything smaller than 400 × 300. With no source folder present the already generated JPEGs are adopted
  as they are, so a fresh seed always reproduces the photos.
- Anything without a photo keeps the generated SVG art (`ImageService::placeholder()` / `hero()`), and a record with no
  image at all falls back to `images/{type}/placeholder.svg` (model accessors).
- Admin uploads are untouched: they live in `storage/app/public/**` and are never overwritten — products are only
  re-pointed when their stored `thumbnail` starts with `images/`.

## Testing

```bash
php artisan test                 # 49 feature tests / 198 assertions
php artisan migrate:fresh --seed # reproducible demo data
npm run build                    # production assets
vendor/bin/pint                  # code style
```

Tests cover OTP login and throttling, admin authorization, catalogue filtering, guest cart limits and merge,
wishlist privacy, checkout totals and stock reservation, idempotent payments, stock restoration, order ownership
and admin CRUD/status transitions.

## Notes

- OTP delivery is simulated: the code is shown in the UI (debug mode) and logged — no SMS provider is wired.
- Demo catalogue images are real photos committed in `public/images/**` and wired up by `DemoImageSeeder` — see
  [Demo images & data](#demo-images--data) for the sizes, the regeneration command and the SVG fallback.
  Admin uploads use the public disk.
- Payments run in demo mode by default; no real money moves.
- Currency is BDT (৳) and totals are formatted in the browser.
- The hero slider keeps a fixed **8:3** ratio (16:9 on tablets, 4:3 on phones), so slides with different
  image sizes never change the page height. Upload **1600 × 600 px** banners for the best result — other
  sizes are cropped with `object-fit: cover`.
