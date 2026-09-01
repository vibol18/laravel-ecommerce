# 🛒 Laravel E-Commerce REST API

A complete, production-ready **E-Commerce REST API** built with **Laravel 10 (LTS line, v10.50.x)**, PHP **8.2+**, **MySQL 8**, and **Laravel Sanctum** token authentication. Runs locally on **XAMPP** and deploys for free to **Railway** or **Render**.

All JSON responses use the consistent envelope:
```json
{
  "success": true,
  "message": "...",
  "data": { }
}
```
Paginated endpoints add a `meta` key with `current_page / last_page / per_page / total / from / to`.

---

## Table of Contents

1. [Features](#features)
2. [Project Structure](#project-structure)
3. [Technology Stack](#technology-stack)
4. [Local Setup with XAMPP](#local-setup-with-xampp)
5. [API Endpoints](#api-endpoints)
6. [Authentication](#authentication)
7. [Error Handling](#error-handling)
8. [Rate Limiting](#rate-limiting)
9. [Testing](#testing)
10. [Seeded Demo Data](#seeded-demo-data)
11. [Testing with Postman](#testing-with-postman)
12. [Deployment to Railway (Free Tier)](#deployment-to-railway-free-tier)
13. [Deployment to Render (Free Tier)](#deployment-to-render-free-tier)
14. [License](#license)

---

## Features

| Module | Description |
|---|---|
| **Auth** | Register / Login / Logout / Current user via Sanctum bearer tokens. Role-based: `admin` or `customer`. |
| **Products** | Full CRUD (admin-only writes). Fields: name, slug, description, price, compare_price, stock, category_id, images (JSON array), status, is_featured, weight. Public listing with **pagination, search, category/price filter, sorting**, soft deletes. |
| **Categories** | CRUD (admin-only writes), nested parent/child categories, soft deletes. |
| **Cart** | Add / update / remove / clear items, per authenticated user, with stock validation. |
| **Orders** | Checkout from cart inside a DB transaction: order items snapshot, totals (subtotal + 10% tax + shipping), status workflow `pending → paid → shipped → completed / cancelled`, auto stock decrement, re-stock on cancel. Customers see only their own; admins see all. |
| **Payments** | Mock/simple payment record (method + status), integration-ready structure for Stripe/PayPal later. `PATCH /payments/{order}/mark-paid`. |
| **Reviews** | Customers can review **only products they purchased** (one review per product). Rating 1–5 + title + comment. |
| **Admin Dashboard** | Total sales, total orders, total products, total customers, categories, pending orders, low-stock alerts, recent orders. |

Cross-cutting concerns:

- **Form Requests** for all validation (`app/Http/Requests/`).
- **API Resources** for consistent JSON output (`app/Http/Resources/`).
- **Policies** for authorization (admin vs customer) + an `admin` route middleware.
- **Rate limiting** on auth routes (`throttle:auth`, 5/min) and the whole API (`120/min`).
- **CORS** pre-configured for `api/*`.
- Consistent JSON error responses for `401 / 403 / 404 / 405 / 422 / 500`.

---

## Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── AuthController.php
│   │       ├── CartController.php
│   │       ├── CategoryController.php
│   │       ├── DashboardController.php
│   │       ├── OrderController.php
│   │       ├── PaymentController.php
│   │       ├── ProductController.php
│   │       └── ReviewController.php
│   ├── Middleware/
│   │   ├── EnsureUserIsAdmin.php
│   │   └── ForceJsonResponse.php
│   ├── Requests/
│   │   ├── Auth/            (RegisterRequest, LoginRequest)
│   │   ├── Product/         (StoreProductRequest, UpdateProductRequest)
│   │   ├── Category/        (StoreCategoryRequest, UpdateCategoryRequest)
│   │   ├── Cart/            (AddToCartRequest, UpdateCartItemRequest)
│   │   ├── Order/           (CheckoutRequest, UpdateOrderStatusRequest)
│   │   └── Review/          (StoreReviewRequest)
│   └── Resources/
│       ├── Auth/UserResource, CategoryResource, ProductResource,
│       ├── CartItemResource, OrderResource, OrderItemResource,
│       ├── PaymentResource, ReviewResource, DashboardStatsResource
├── Models/                  (User, Category, Product, CartItem,
│                             Order, OrderItem, Payment, Review)
├── Policies/                (ProductPolicy, CategoryPolicy,
│                             OrderPolicy, ReviewPolicy)
├── Traits/ApiResponses.php  (successResponse / errorResponse / paginatedResponse)
├── Exceptions/Handler.php   (consistent JSON error responses)
└── Providers/               (AuthServiceProvider registers policies,
                              RouteServiceProvider rate limiters)
database/
├── migrations/              (users+role/phone, categories, products, cart_items,
│                             orders, order_items, payments, reviews, tokens)
├── factories/               (UserFactory, CategoryFactory, ProductFactory, ReviewFactory)
└── seeders/                 (UserSeeder, ProductSeeder, OrderSeeder, ReviewSeeder)
routes/api.php               (all API routes)
tests/Feature/               (AuthTest, ProductTest, OrderTest — 24 tests)
postman/                     (ecommerce_api.postman_collection.json)
Procfile                     (Railway)
render.yaml                  (Render blueprint)
nixpacks.toml                (Railway/Nixpacks build+start config)
```

---

## Technology Stack

- **Laravel 10.50** (stable LTS line) — PHP `^8.1` required, tested on PHP 8.2
- **PHP 8.2+**
- **MySQL 8** (local XAMPP) / **Railway MySQL plugin** / **Render PostgreSQL**
- **Laravel Sanctum 3.x** — token-based API auth
- **Eloquent ORM** with migrations, factories, seeders
- Composer for dependency management

---

## Local Setup with XAMPP

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) with PHP 8.2+ and MySQL 8
- [Composer](https://getcomposer.org/) 2.x

### Step-by-step

```bash
# 1. Start XAMPP (Apache + MySQL)
#    Open the XAMPP Control Panel and start both services.

# 2. Create the database
#    Open http://localhost/phpmyadmin  ->  click "New"
#    Database name: ecommerce_api
#    Collation: utf8mb4_unicode_ci
```

```bash
# 3. Install dependencies
composer install

# 4. Prepare environment
#    On Windows:  copy .env.example .env
#    On macOS/Linux: cp .env.example .env

# 5. Edit .env
#    APP_NAME="Laravel E-Commerce API"
#    APP_URL=http://127.0.0.1:8000
#    DB_CONNECTION=mysql
#    DB_HOST=127.0.0.1
#    DB_PORT=3306
#    DB_DATABASE=ecommerce_api
#    DB_USERNAME=root
#    DB_PASSWORD=            # empty password is the XAMPP default

# 6. Generate application key
php artisan key:generate

# 7. Run migrations + seed demo data (users, 20 categories, 60 products, orders, reviews)
php artisan migrate --seed

# 8. Start the dev server
php artisan serve
# Server runs at: http://127.0.0.1:8000
```

### Quick sanity check

```bash
curl http://127.0.0.1:8000/api/products
# -> {"success":true,"message":"Products retrieved successfully","data":[...],...}
```

---

## API Endpoints

Base URL: `http://127.0.0.1:8000/api`

### Auth
| Method | Route | Description | Auth |
|---|---|---|---|
| POST | `/register` | Register a new customer | No |
| POST | `/login` | Login, returns Bearer token | No |
| POST | `/logout` | Revoke current token | Yes |
| GET | `/user` | Get current authenticated user | Yes |

### Categories
| Method | Route | Description | Auth |
|---|---|---|---|
| GET | `/categories` | List categories (paginated, `search`, `parent_id`, `active_only`) | No |
| GET | `/categories/{category}` | Show single category (+ parent, children, products) | No |
| POST | `/categories` | Create category | Admin |
| PUT/PATCH | `/categories/{category}` | Update category | Admin |
| DELETE | `/categories/{category}` | Delete category (blocks if it has products) | Admin |

### Products
| Method | Route | Description | Auth |
|---|---|---|---|
| GET | `/products` | List products (paginated) | No |
| GET | `/products/{product}` | Show single product (+ category, reviews) | No |
| GET | `/products/{product}/reviews` | List approved reviews for a product | No |
| POST | `/products` | Create product | Admin |
| PUT/PATCH | `/products/{product}` | Update product | Admin |
| DELETE | `/products/{product}` | Soft-delete product | Admin |

**Product listing query params:** `search`, `category_id`, `min_price`, `max_price`, `status`, `featured`, `sort_by` (`name|price|stock|created_at|updated_at`), `sort_direction` (`asc|desc`), `per_page`.
> Note: listing defaults to active products only, and the `per_page` is capped by the API global rate limit (120/min).

### Cart (all Auth: Yes)
| Method | Route | Description |
|---|---|---|
| GET | `/cart` | Get current user's cart (+ subtotal) |
| POST | `/cart/add` | Add item `{product_id, quantity}` (stock-checked) |
| PUT | `/cart/items/{cartItem}` | Update quantity |
| DELETE | `/cart/items/{cartItem}` | Remove item |
| DELETE | `/cart` | Clear cart |

### Orders
| Method | Route | Description | Auth |
|---|---|---|---|
| GET | `/orders` | Current user's orders | User |
| GET | `/orders/{order}` | Order detail (owner or admin) | User |
| POST | `/orders/checkout` | Create order from cart | User |
| GET | `/orders/admin/all` | List all orders (`status`, `search`) | Admin |
| PATCH | `/orders/{order}/status` | Update status | Admin |

Status workflow: `pending → paid → shipped → completed`, or `cancelled` (from `pending`). Cancelling a pending order restores stock.

### Payments
| Method | Route | Description | Auth |
|---|---|---|---|
| GET | `/payment-methods` | List available payment methods | No |
| PATCH | `/payments/{order}/mark-paid` | Mark an order's payment completed | Admin |

### Reviews
| Method | Route | Description | Auth |
|---|---|---|---|
| POST | `/products/{product}/reviews` | Create review (must have purchased) | User |
| GET | `/products/{product}/reviews/{review}` | Show review | No |
| PUT/PATCH | `/products/{product}/reviews/{review}` | Update review (owner or admin) | User/Admin |
| DELETE | `/products/{product}/reviews/{review}` | Delete review (owner or admin) | User/Admin |

### Admin Dashboard (all Admin)
| Method | Route | Description |
|---|---|---|
| GET | `/admin/dashboard/stats` | Total sales, orders, products, customers, categories, pending orders, low-stock list, recent orders |
| GET | `/admin/low-stock?threshold=10` | Products with stock ≤ threshold |

---

## Authentication

1. `POST /api/register` or `POST /api/login` → response contains:
   ```json
   {
     "success": true,
     "message": "Login successful",
     "data": {
       "user": { "id": 1, "name": "Admin User", "role": "admin", ... },
       "token": "1|abc123...",
       "token_type": "Bearer"
     }
   }
   ```
2. Send the token in later requests:
   ```
   Authorization: Bearer 1|abc123...
   ```
3. Routes that return `401` prompt for a valid token; `403` means authenticated but missing admin privileges.

---

## Error Handling

Consistent JSON errors from `app/Exceptions/Handler.php`:

| Status | Shape |
|---|---|
| 401 | `{"success":false,"message":"Unauthenticated...","data":null}` |
| 403 | `{"success":false,"message":"Access denied...","data":null}` |
| 404 | `{"success":false,"message":"Resource not found.","data":null}` |
| 405 | `{"success":false,"message":"Method not allowed.","data":null}` |
| 422 | `{"success":false,"message":"Validation failed","data":null,"errors":{...}}` |
| 500 | `{"success":false,"message":"Internal server error.","data":null}` (debug shows message locally) |

---

## Rate Limiting

Defined in `app/Providers/RouteServiceProvider.php`:
- `auth` limiter: **5 requests/min** per IP on `/api/register` and `/api/login`.
- `api` limiter: **120 requests/min** globally on the `api` group.

---

## Testing

24 feature tests across `tests/Feature/` (`AuthTest`, `ProductTest`, `OrderTest`). The suite uses an **in-memory SQLite** database (configured in `phpunit.xml`), so it runs without touching your MySQL data.

```bash
php artisan test
# Tests: 24 passed (91 assertions)
```

---

## Seeded Demo Data

Running `php artisan migrate --seed` creates:

| Entity | Count | Notes |
|---|---|---|
| Users | 15 | `admin@example.com / password` (admin), `customer@example.com / password`, + 13 random customers |
| Categories | 20 | 5 parents (Electronics, Fashion, Home & Living, Sports & Outdoors, Books) + 3 children each |
| Products | 60 | Random names, prices, stock, statuses, images |
| Orders | 10 | Statuses spread across the workflow |
| Payments | 10 | One per order |
| Reviews | ~37 | 3–5 star reviews on purchased products |

---

## Testing with Postman

Import `postman/ecommerce_api.postman_collection.json` into Postman (Import → Upload file → Open) and set the `baseUrl` collection variable (default `http://127.0.0.1:8000/api`):

1. Run **Login** with `admin@example.com` → copy token into the `adminToken` variable.
2. Run **Login** with `customer@example.com` → copy token into the `customerToken` variable.
3. Execute any request. Collection variables are in the collection's `Variables` tab.

---

## Deployment to Railway (Free Tier)

### 1. Push to GitHub

```bash
git init
git add .
git commit -m "Laravel e-commerce API"
git remote add origin git@github.com:<USER>/<REPO>.git
git push -u origin main
```

> `.env` and `/vendor` are already git-ignored — never commit them.

### 2. Create the project on Railway

1. Go to https://railway.com → **New Project** → **Deploy from GitHub repo** → pick your repo.
2. Railway (via **Nixpacks**) auto-detects PHP. The provided `Procfile` and `nixpacks.toml` set the exact commands.

### 3. Add the MySQL plugin

1. In your project → **New** → **Database** → **MySQL** (free tier).
2. Copy the connection variables from the MySQL service's **Variables** tab:
   `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE` (or `MYSQL_DATABASE`), `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQL_URL`.

### 4. Set environment variables

In the **Variables** tab of your Laravel service, add:

```
DB_CONNECTION=mysql
DB_HOST=<MYSQLHOST value>
DB_PORT=<MYSQLPORT value>
DB_DATABASE=<MYSQLDATABASE value>
DB_USERNAME=<MYSQLUSER value>
DB_PASSWORD=<MYSQLPASSWORD value>
APP_KEY=<run php artisan key:generate --show locally to obtain one>
APP_URL=https://<your-railway-domain>
APP_ENV=production
APP_DEBUG=false
```

### 5. Configure the service commands

Railway reads `nixpacks.toml` / `Procfile`. If you configure manually:
- **Build command:** `composer install --no-dev --optimize-autoloader`
- **Start command:** `php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT`

### 6. Deploy

Railway rebuilds automatically on every push to `main`. After the first build:
- **Settings → Generate Domain** to get your public `https://your-app.up.railway.app`.
- Use that domain as `APP_URL`.
- Seeding on production is optional: `php artisan db:seed --force` inside Railway's **Deployments → Open Shell** tab.

---

## Deployment to Render (Free Tier)

1. Push the repo to GitHub (see above).
2. Go to https://render.com → **New → Blueprint** → connect your repo. The included `render.yaml` defines the web service **and** a free **PostgreSQL** database automatically.
3. Render provisions the PHP service with:
   - **Build:** `composer install --no-dev --optimize-autoloader`
   - **Start:** `php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT`
4. `render.yaml` wires `DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD` from the managed Postgres and auto-generates `APP_KEY`.
5. After the first deploy, a public URL is created automatically → set that as `APP_URL` in the service's **Environment** tab and redeploy.

> **Prefer MySQL on Render?** Render's native free DB is Postgres only. You can instead use an external free MySQL (e.g., a Railway MySQL plugin or Aiven MySQL) and set `DB_CONNECTION=mysql` with those host/port/name/user/password values — everything else stays the same.

---

## License

MIT — free to use and modify.