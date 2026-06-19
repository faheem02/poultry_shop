<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDB();
$customer_id = (int)($_GET['id'] ?? 0);

$customers = $pdo->query("SELECT * FROM customers ORDER BY name")->fetchAll();
$customer = null;

if ($customer_id) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch();
}

$page_title = $customer ? 'Ledger - ' . $customer['name'] : 'Customer Ledger';

// Build ledger entries
$entries = [];
$running = 0;

if ($customer) {
    $running = (float)$customer['opening_balance'];
    $entries[] = [
        'date'       => '0000-00-00',
        'sort_id'    => 0,
        'particular' => 'Opening Balance',
        'debit'      => 0,
        'credit'     => 0,
        'balance'    => $running,
    ];

    // Fetch all sales
    $sales = $pdo->prepare("SELECT * FROM sales WHERE customer_id = ? ORDER BY sale_date, id");
    $sales->execute([$customer_id]);
    foreach ($sales->fetchAll() as $s) {
        $entries[] = [
            'date'       => $s['sale_date'],
            'sort_id'    => (int)$s['id'],
            'type'       => 'sale',
            'row_type'   => 1,          // sales before payments on same date
            'particular' => 'Sale Invoice: ' . $s['invoice_no'],
            'debit'      => (float)$s['net_total'],
            'credit'     => 0,
        ];
    }

    // Fetch all payments
    $payments = $pdo->prepare("SELECT * FROM payments WHERE customer_id = ? ORDER BY payment_date, id");
    $payments->execute([$customer_id]);
    foreach ($payments->fetchAll() as $p) {
        $label = $p['notes'] ? htmlspecialchars($p['notes']) : 'Payment (' . ucfirst($p['payment_method']) . ')';
        $entries[] = [
            'date'       => $p['payment_date'],
            'sort_id'    => (int)$p['id'],
            'type'       => 'payment',
            'row_type'   => 2,          // payments after sales on same date
            'particular' => $label,
            'debit'      => 0,
            'credit'     => (float)$p['amount'],
        ];
    }

    // Sort: opening first, then by date, then sales before payments, then by id
    usort($entries, function($a, $b) {
        if ($a['date'] === '0000-00-00') return -1;
        if ($b['date'] === '0000-00-00') return 1;
        if ($a['date'] !== $b['date']) return strcmp($a['date'], $b['date']);
        if (($a['row_type'] ?? 0) !== ($b['row_type'] ?? 0)) return ($a['row_type'] ?? 0) <=> ($b['row_type'] ?? 0);
        return ($a['sort_id'] ?? 0) <=> ($b['sort_id'] ?? 0);
    });

    // Calculate running balance in chronological order
    $running = 0;
    foreach ($entries as &$e) {
        if ($e['date'] === '0000-00-00') {
            $running = $e['balance']; // seed from opening balance
        } else {
            $running += $e['debit'] - $e['credit'];  // debit = customer owes more, credit = customer paid
            $e['balance'] = $running;
        }
    }
    unset($e);
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-book me-1"></i> Customer Ledger</h1>
    <a href="/poultry_shop/customers/index.php" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label small">Select Customer</label>
                <select name="id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Choose Customer --</option>
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $c['id'] === $customer_id ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<?php if ($customer): ?>
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card border-start-primary">
            <div class="card-body">
                <h5 class="fw-bold"><?= htmlspecialchars($customer['name']) ?></h5>
                <p class="mb-1 small">Phone: <?= htmlspecialchars($customer['phone'] ?? '-') ?></p>
                <p class="mb-0 small">Email: <?= htmlspecialchars($customer['email'] ?? '-') ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start-<?= $running > 0 ? 'danger' : 'success' ?>">
            <div class="card-body text-center">
                <div class="text-xs fw-bold text-uppercase text-<?= $running > 0 ? 'danger' : 'success' ?>">Current Balance</div>
                <div class="h4 mb-0 fw-bold text-<?= $running > 0 ? 'danger' : ($running < 0 ? 'primary' : 'success') ?>">
                    Rs. <?= money(abs($running)) ?>
                </div>
                <small class="fw-bold text-<?= $running > 0 ? 'danger' : ($running < 0 ? 'primary' : 'success') ?>">
                    <?= $running > 0 ? '⚠ Amount Due' : ($running < 0 ? '✓ Advance Paid' : '✓ Account Clear') ?>
                </small>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Particular</th>
                        <th class="text-end">Debit (Rs.)</th>
                        <th class="text-end">Credit (Rs.)</th>
                        <th class="text-end">Balance (Rs.)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $e): ?>
                    <tr class="<?= ($e['type'] ?? '') === 'sale' ? 'table-warning' : (($e['type'] ?? '') === 'payment' ? 'table-success' : '') ?>">
                        <td><?= $e['date'] === '0000-00-00' ? '<span class="text-muted">—</span>' : date('d M Y', strtotime($e['date'])) ?></td>
                        <td>
                            <?php if (($e['type'] ?? '') === 'sale'): ?>
                                <i class="fas fa-file-invoice text-warning me-1"></i>
                            <?php elseif (($e['type'] ?? '') === 'payment'): ?>
                                <i class="fas fa-money-bill-wave text-success me-1"></i>
                            <?php else: ?>
                                <i class="fas fa-balance-scale text-primary me-1"></i>
                            <?php endif; ?>
                            <?= $e['particular'] ?>
                        </td>
                        <td class="text-end text-danger fw-bold"><?= $e['debit'] > 0 ? 'Rs. ' . money($e['debit']) : '-' ?></td>
                        <td class="text-end text-success fw-bold"><?= $e['credit'] > 0 ? 'Rs. ' . money($e['credit']) : '-' ?></td>
                        <td class="text-end fw-bold <?= ($e['balance'] ?? 0) > 0 ? 'text-danger' : (($e['balance'] ?? 0) < 0 ? 'text-primary' : 'text-success') ?>">
                            Rs. <?= money(abs($e['balance'] ?? 0)) ?>
                            <small>
                            <?php if (($e['balance'] ?? 0) > 0): ?>
                                <span class="badge bg-danger">Due</span>
                            <?php elseif (($e['balance'] ?? 0) < 0): ?>
                                <span class="badge bg-primary">Advance</span>
                            <?php else: ?>
                                <span class="badge bg-success">Clear</span>
                            <?php endif; ?>
                            </small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php elseif ($customer_id): ?>
<div class="alert alert-danger">Customer not found.</div>
<?php else: ?>
<div class="text-center text-muted py-5">
    <i class="fas fa-hand-pointer fa-3x mb-3"></i>
    <p>Select a customer above to view their ledger.</p>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
