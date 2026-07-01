# AGENTS.md — Poultry Shop POS

Vanilla PHP/MySQL POS system. No framework, no Composer, no build step, no test runner, no CI.

## Quick start

- **Serve:** XAMPP pointing to this dir. Import `database/schema.sql` (creates DB `poultry_shop` + 11 InnoDB tables + seed data).
- **BASE_URL:** Auto-detected in `includes/base_url.php` from `__DIR__` + `$_SERVER['DOCUMENT_ROOT']` — no config needed.
- **DB:** `includes/database.php` — `root` / no password, `poultry_shop`. PDO singleton via `getDB()`.
- **Logins (plaintext):** `admin` / `admin123`, `cashier` / `cashier123`.
- **Timezone:** `Asia/Karachi` (set in `database.php` and `header.php`). Currency: PKR (Rs.).
- **No** `.gitignore`, `.htaccess`, `.env`, URL rewriting.

## Entry points

| File | Notes |
|---|---|
| `index.php` | Standalone — requires `base_url.php`, starts own session, redirects to dashboard or login. |
| `login.php` | No `auth_check.php`. Plaintext comparison `$password === $user['password_hash']`. Sets `$_SESSION['user_id'\|'user_name'\|'user_role'\|'last_activity']`. |
| `logout.php` | Requires `base_url.php`, destroys session, redirects to login. |
| `pages/pos/pos_ajax.php` | JSON endpoint. Does **own** `session_start()` (no `auth_check.php`). Checks auth + CSRF only on `save_sale` action. |

## Protected page pattern (every module page)

Include in order:
1. `includes/auth_check.php` — starts session, checks login + 1h timeout, redirects to `login.php?expired=1`.
2. `includes/database.php` — `getDB()`.
3. `includes/functions.php` — helpers: `sanitize()`, `money()`, `csrf_token()`/`verify_csrf()`, `setFlash()`/`flashMessage()`, `generate_invoice_no()`, `getCustomerBalance()`, `getSupplierBalance()`, `availableStock()`, `todayProfit()`, `isAdmin()`/`isCashier()`, `navActive()`/`navActiveDir()`/`isSectionActive()`.
4. `includes/header.php` — `<head>`, topbar, flash messages, sidebar. Sets `$page_title` before requiring.
5. `includes/footer.php` — loads DataTables, SweetAlert2, `sb-admin-custom.js`.

## Architecture

- **No routing.** Each page is a standalone `.php` file under `pages/module_name/`. Convention: `index.php` = list/CRUD, optional detail files per module.
- **No server-side role gating.** `isAdmin()`/`isCashier()` only used in `header.php` for badge color. Both roles access all pages.
- **Module layout:** `pages/dashboard/`, `pos/`, `customers/`, `suppliers/`, `sales/`, `purchases/`, `payments/`, `supplier_payments/`, `expenses/`, `stock/`, `chicken_types/`, `chicken_rates/`, `reports/`.

## POS flow (`pages/pos/`)

- Frontend form with two-way calculator (weight ↔ amount) in `assets/js/pos.js`.
- `pos_ajax.php` actions: `get_rate` (rate + stock by chicken type), `today_rates` (all types), `search_customer`/`search_customers` (by name/phone), `save_sale` (inserts sale + stock_ledger + payment in DB transaction; requires CSRF).
- Stock checked via `availableStock()` before sale. Walk-in Customer (id=1 from seed) is default when `customer_id=0`.
- Invoice format: `INV-YYYYMMDD-NNNN` (auto-increment per day).

## Conventions

| Convention | Details |
|---|---|
| **CSRF** | `csrf_token()` / `verify_csrf()` — 64-char hex in `$_SESSION` |
| **Flash** | `setFlash()` / `flashMessage()` — auto-displayed in `header.php`, auto-dismiss after 4s |
| **XSS** | `sanitize()` = `htmlspecialchars(strip_tags(trim()))` on input; `htmlspecialchars()` on output |
| **Money** | `money($n)` → 2-decimal thousands separator; `moneyRaw($n)` → no separator |
| **DataTables** | Class `datatable` on `<table>`; init in `sb-admin-custom.js` (pageLength:25, stateSave:true) |
| **Delete** | Class `btn-delete` on `<a>` triggers SweetAlert2 confirmation (reads `data-text` for message) |
| **Stock ledger** | Transaction types: `opening`, `purchase`, `sale`, `adjustment`. Stock = SUM(opening+purchase+adjustment) - SUM(sale). |
| **Today's profit** | `todayProfit()` = revenue - avg purchase cost × weight sold - expenses (simplified — not FIFO) |

## Dependencies

All frontend via CDN (Bootstrap 5.3.3, jQuery 3.7.1, DataTables 1.13.7, SweetAlert2 11, Chart.js 4.4.1, Font Awesome 6.5.1). Custom CSS in `assets/css/sb-admin-custom.css` (`--primary: #059669`). Custom JS in `assets/js/pos.js`, `assets/js/sb-admin-custom.js`.
