<?php

function sanitize($input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function money($amount): string {
    return number_format((float)$amount, 2);
}

function moneyRaw($amount): string {
    return number_format((float)$amount, 2, '.', '');
}

function navActive(string $page): string {
    $current = basename($_SERVER['PHP_SELF']);
    return ($current === $page) ? 'active' : '';
}

function navActiveDir(string $dir): string {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $segments = explode('/', trim($path, '/'));
    return (isset($segments[0]) && $segments[0] === $dir) ? 'active' : '';
}

function isSectionActive(string $section): bool {
    $uri = $_SERVER['REQUEST_URI'];
    switch ($section) {
        case 'customers': return strpos($uri, '/customers/') !== false;
        case 'suppliers': return strpos($uri, '/suppliers/') !== false || strpos($uri, '/supplier_payments/') !== false;
        case 'finance':   return strpos($uri, '/cash_book') !== false || strpos($uri, '/bank_book') !== false;
        case 'reports':   return strpos($uri, '/reports/') !== false;
        case 'stock':     return strpos($uri, '/stock/') !== false;
        default:           return false;
    }
}

function isAdmin(): bool {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function isCashier(): bool {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'cashier';
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function generate_invoice_no(): string {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT MAX(id) AS last_id FROM sales");
    $row = $stmt->fetch();
    $next = ($row['last_id'] ?? 0) + 1;
    return 'INV-' . date('Ymd') . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
}

function getCustomerBalance(int $customer_id): float {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT opening_balance FROM customers WHERE id = ?");
    $stmt->execute([$customer_id]);
    $cust = $stmt->fetch();
    $opening = (float)($cust['opening_balance'] ?? 0);

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(net_total), 0) AS total_sales FROM sales WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $sales = (float)$stmt->fetch()['total_sales'];

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) AS total_payments FROM payments WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $payments = (float)$stmt->fetch()['total_payments'];

    return $opening + $sales - $payments;
}

function getSupplierBalance(int $supplier_id): float {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_cost), 0) AS total_purchases FROM purchases WHERE supplier_id = ?");
    $stmt->execute([$supplier_id]);
    $purchases = (float)$stmt->fetch()['total_purchases'];

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) AS total_payments FROM supplier_payments WHERE supplier_id = ?");
    $stmt->execute([$supplier_id]);
    $payments = (float)$stmt->fetch()['total_payments'];

    return $purchases - $payments;
}

function todaySalesTotal(): float {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE DATE(payment_date) = CURDATE()");
    return (float)$stmt->fetch()['total'];
}

function todayProfit(): float {
    $pdo = getDB();
    
    // Revenue today (from payments - includes sales + due payments)
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE DATE(payment_date) = CURDATE()");
    $revenue = (float)$stmt->fetch()['total'];
    
    // COGS today (average purchase rate × weight sold)
    $stmt = $pdo->query("SELECT COALESCE(AVG(purchase_rate), 0) AS avg_rate FROM purchases WHERE DATE(purchase_date) = CURDATE()");
    $avg_rate = (float)$stmt->fetch()['avg_rate'];
    
    $stmt = $pdo->query("SELECT COALESCE(SUM(weight), 0) AS total_weight FROM sales WHERE DATE(sale_date) = CURDATE()");
    $total_weight = (float)$stmt->fetch()['total_weight'];
    
    $cogs = $avg_rate * $total_weight;
    
    // Expenses today
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) AS total FROM expenses WHERE DATE(expense_date) = CURDATE()");
    $expenses = (float)$stmt->fetch()['total'];
    
    // Net Profit = (Revenue - COGS) - Expenses
    return ($revenue - $cogs) - $expenses;
}

function availableStock(int $chicken_type_id): array {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN transaction_type IN ('opening','purchase','adjustment') THEN birds_count ELSE 0 END), 0)
            - COALESCE(SUM(CASE WHEN transaction_type = 'sale' THEN birds_count ELSE 0 END), 0) AS birds,
            COALESCE(SUM(CASE WHEN transaction_type IN ('opening','purchase','adjustment') THEN weight_kg ELSE 0 END), 0)
            - COALESCE(SUM(CASE WHEN transaction_type = 'sale' THEN weight_kg ELSE 0 END), 0) AS weight
        FROM stock_ledger
        WHERE chicken_type_id = ?
    ");
    $stmt->execute([$chicken_type_id]);
    return $stmt->fetch();
}

function flashMessage(): ?string {
    if (isset($_SESSION['flash'])) {
        $msg = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $msg;
    }
    return null;
}

function setFlash(string $msg): void {
    $_SESSION['flash'] = $msg;
}