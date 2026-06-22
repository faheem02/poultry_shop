# AGENTS.md — Poultry Shop POS

Vanilla PHP/MySQL POS system. No framework, no build tools, no Composer, no npm, no tests, no CI.

## Quick start

- **Serve:** XAMPP or any PHP-capable web server pointing to this directory. Import `database/schema.sql` into MySQL (creates DB + tables + seed data).
- **URL prefix:** All pages assume `/poultry_shop/` base path.
- **DB credentials:** `config/database.php` — `root` / no password, database `poultry_shop`.
- **Default logins:** `admin` / `admin123`, `cashier` / `cashier123` (plaintext passwords).

## Architecture

- **No framework.** Each page is a standalone PHP file that includes one or more of:
  - `config/database.php` — `getDB()` returns PDO singleton
  - `includes/auth_check.php` — session auth + 1hr timeout
  - `includes/header.php` — HTML head, sidebar nav, topbar
  - `includes/footer.php` — closing tags, scripts
  - `includes/functions.php` — sanitize, money format, CSRF, balance queries, flash messages
- **Module pattern:** Each feature is a directory with `index.php` (list/CRUD) + optional views (e.g., `ledger.php`, `view.php`, `invoice.php`).
- **DB schema & seed data:** `database/schema.sql` — 11 InnoDB tables, seed data included.

## Conventions

| Convention | Details |
|---|---|
| **CSRF** | `csrf_token()` in forms, `verify_csrf()` on POST |
| **Flash messages** | `setFlash()` / `flashMessage()` — displayed automatically in `header.php` |
| **Role checks** | `isAdmin()` / `isCashier()` |
| **Active nav** | `navActive()`, `navActiveDir()`, `isSectionActive()` |
| **Output** | `htmlspecialchars()` for XSS prevention |
| **DB access** | PDO prepared statements throughout |
| **Money format** | `money()` → number_format with 2 decimals |

## Dependencies

All frontend deps loaded via CDN (Bootstrap 5.3.3, jQuery 3.7.1, DataTables 1.13.7, SweetAlert2 11, Chart.js 4.4.1, Font Awesome 6.5.1). Vendored SB Admin 2 template in `sb-admin2/`.

## Notes

- Passwords stored in plaintext in `users.password_hash`.
- Timezone: `Asia/Karachi`. Currency: PKR (Rs.).
- `pos.js` handles POS calculator, rate loading, customer search, sale submission.
- Custom CSS (`assets/css/sb-admin-custom.css`) uses green theme (`--primary: #059669`).
- No `.env`, no `.gitignore`, no editorconfig.
