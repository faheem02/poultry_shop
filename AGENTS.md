# AGENTS.md — Poultry Shop POS

Vanilla PHP/MySQL POS system. No framework, no build tools, no Composer, no test runner, no CI.

## Quick start

- **Serve:** XAMPP web server pointing to this directory. Import `database/schema.sql` (creates DB `poultry_shop` + 11 InnoDB tables + seed data).
- **URL prefix:** `BASE_URL` constant defined in `includes/database.php` (default `/poultry_shop`). Change for deployment — set to `''` for root domain or `'/shop'` for subdirectory.
- **DB:** `includes/database.php` — `root` / no password, `poultry_shop`. PDO singleton via `getDB()`.
- **Logins (plaintext):** `admin` / `admin123`, `cashier` / `cashier123`.

## Entry points

- `index.php` — session check → redirects to `pages/dashboard/index.php` or `login.php`
- `login.php` — plaintext comparison (`$password === $user['password_hash']`), sets `$_SESSION['user_id'|'user_name'|'user_role'|'last_activity']`
- `logout.php` — destroys session
- `auth_check.php` — included at top of every protected page; starts `session_start()`, checks 1-hour timeout, redirects to `login.php` if expired/unauthenticated

## Architecture

- **No routing.** Each page is a standalone `.php` file including includes in order:
  1. `includes/auth_check.php` — session + auth
  2. `includes/database.php` — `getDB()` (was `config/database.php`)
  3. `includes/functions.php` — helpers
  4. `includes/header.php` — `<head>`, topbar, flash messages (uses `$page_title`); sidebar loaded via `includes/sidebar.php`
  5. `includes/footer.php` — `</body>`, loads DataTables, SweetAlert2, `sb-admin-custom.js`

- **Module pattern:** `pages/module_name/index.php` = list/CRUD. Optional detail views (`view.php`, `invoice.php`, `ledger.php`, `create.php`, `manage.php`). Each module is independent.

## Modules

| Directory | Files | Purpose |
|---|---|---|
| `pages/dashboard/` | `index.php` | Main landing page |
| `pages/pos/` | `index.php`, `pos_ajax.php` | POS calculator, save sale (AJAX) |
| `pages/customers/` | `index.php`, `create.php`, `ledger.php` | Customer CRUD + balance |
| `pages/suppliers/` | `index.php`, `create.php`, `ledger.php` | Supplier CRUD + balance |
| `pages/sales/` | `index.php`, `view.php`, `invoice.php` | Sales list + detail + printable invoice |
| `pages/purchases/` | `index.php`, `view.php` | Purchase list + detail |
| `pages/payments/` | `index.php` | Customer payments |
| `pages/supplier_payments/` | `index.php` | Supplier payments |
| `pages/expenses/` | `index.php` | Expense CRUD |
| `pages/stock/` | `index.php`, `summary.php`, `manage.php` | Stock ledger + summary + adjustments |
| `pages/chicken_types/` | `index.php` | Chicken type CRUD |
| `pages/chicken_rates/` | `index.php` | Daily rate management |
| `pages/reports/` | 8 files | cash_book, bank_book, daily_sales, expense_report, stock_report, customer_ledger, supplier_ledger |

## POS flow (`pages/pos/`)

- `pages/pos/index.php` — frontend form with calculator logic in `assets/js/pos.js`
- `pages/pos/pos_ajax.php` — JSON endpoint. Actions: `get_rate` (rate + stock for chicken type), `today_rates` (all types), `search_customer` / `search_customers` (by name/phone), `save_sale` (inserts sale + stock_ledger + payment in transaction). Requires CSRF token on save.
- Stock is checked before sale (`availableStock()`).
- Walk-in Customer (id=1 from seed) is default when no customer selected.

## Conventions

| Convention | Details |
|---|---|
| **CSRF** | `csrf_token()` / `verify_csrf()` — token in `$_SESSION` |
| **Flash messages** | `setFlash()` / `flashMessage()` — auto-displayed in `header.php` |
| **Role checks** | `isAdmin()` / `isCashier()` — checks `$_SESSION['user_role']` |
| **Active nav** | `navActive()`, `navActiveDir()`, `isSectionActive()` |
| **XSS** | `htmlspecialchars()` on all output |
| **Money** | `money($n)` → 2-decimal string; `moneyRaw($n)` → no thousands separator |
| **DB** | PDO prepared statements throughout |
| **Invoice no.** | `generate_invoice_no()` → `INV-YYYYMMDD-NNNN` |
| **Customer balance** | `getCustomerBalance(id)` = opening_balance + sales - payments |
| **Supplier balance** | `getSupplierBalance(id)` = opening_balance + purchases - payments |
| **Available stock** | `availableStock(type_id)` → `{birds, weight}` from stock_ledger |
| **Today's profit** | `todayProfit()` = revenue - avg purchase cost × weight sold - expenses |
| **DataTables** | Activated by class `datatable` on `<table>`; no explicit JS init needed |

## Dependencies

All frontend via CDN (Bootstrap 5.3.3, jQuery 3.7.1, DataTables 1.13.7, SweetAlert2 11, Chart.js 4.4.1, Font Awesome 6.5.1). SB Admin 2 template vendored in `sb-admin2/`. Custom green theme in `assets/css/sb-admin-custom.css` (`--primary: #059669`). Custom JS in `assets/js/` (`pos.js`, `sb-admin-custom.js`).

`package.json` has `bootstrap ^5.3.8` in `node_modules/` but no build step — not used at runtime.

## Notes

- Timezone: `Asia/Karachi` (set in `includes/database.php` and `header.php`). Currency: PKR (Rs.).
- Passwords stored + compared as **plaintext** in `users.password_hash`.
- No `.htaccess`, no URL rewriting. No `.env`, no `.gitignore`.
