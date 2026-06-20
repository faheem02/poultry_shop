<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDB();
$page_title = 'Supplier Ledger';

$supplier_id = (int)($_GET['id'] ?? 0);
$supplier = null;

if ($supplier_id) {
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->execute([$supplier_id]);
    $supplier = $stmt->fetch();
}

$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name")->fetchAll();

$entries = [];
$total_purchases = 0;
$total_payments = 0;

if ($supplier) {
    // Fetch purchases
    $purchases = $pdo->prepare("SELECT * FROM purchases WHERE supplier_id = ? ORDER BY purchase_date, id");
    $purchases->execute([$supplier_id]);
    foreach ($purchases as $p) {
        $total_purchases += (float)$p['total_cost'];
        $entries[] = [
            'type'    => 'purchase',
            'date'    => $p['purchase_date'],
            'id'      => $p['id'],
            'ref'     => $p['invoice_no'] ?? '-',
            'debit'   => (float)$p['total_cost'],
            'credit'  => 0,
            'details' => $p['total_birds'] . ' birds, ' . money($p['total_weight']) . ' kg @ Rs. ' . money($p['purchase_rate']) . '/kg',
            'notes'   => $p['notes'] ?? '',
        ];
    }

    // Fetch payments
    $payments = $pdo->prepare("SELECT * FROM supplier_payments WHERE supplier_id = ? ORDER BY payment_date, id");
    $payments->execute([$supplier_id]);
    foreach ($payments as $p) {
        $total_payments += (float)$p['amount'];
        $entries[] = [
            'type'    => 'payment',
            'date'    => $p['payment_date'],
            'id'      => $p['id'],
            'ref'     => 'Payment',
            'debit'   => 0,
            'credit'  => (float)$p['amount'],
            'details' => 'Paid via ' . ucfirst($p['payment_method']),
            'notes'   => $p['notes'] ?? '',
        ];
    }

    // Sort by date, then by type (purchases first for same date)
    usort($entries, function ($a, $b) {
        if ($a['date'] !== $b['date']) return strcmp($a['date'], $b['date']);
        return ($a['type'] === 'purchase' ? 0 : 1) - ($b['type'] === 'purchase' ? 0 : 1);
    });
}

$balance = $total_purchases - $total_payments;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-book me-1"></i> Supplier Ledger</h1>
    <a href="/poultry_shop/suppliers/index.php" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
</div>

<div class="card mb-4 border-start-info">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label small">Select Supplier</label>
                <select name="id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Choose Supplier --</option>
                    <?php foreach ($suppliers as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $s['id'] === $supplier_id ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<?php if ($supplier): ?>
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-start-primary">
            <div class="card-body">
                <h5 class="fw-bold"><?= htmlspecialchars($supplier['name']) ?></h5>
                <p class="mb-1 small">Phone: <?= htmlspecialchars($supplier['phone'] ?? '-') ?></p>
                <p class="mb-0 small">Email: <?= htmlspecialchars($supplier['email'] ?? '-') ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-start-success">
            <div class="card-body text-center">
                <div class="text-xs fw-bold text-success text-uppercase">Total Purchases</div>
                <div class="h5 mb-0 fw-bold text-success">Rs. <?= money($total_purchases) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-start-warning">
            <div class="card-body text-center">
                <div class="text-xs fw-bold text-warning text-uppercase">Total Paid</div>
                <div class="h5 mb-0 fw-bold text-warning">Rs. <?= money($total_payments) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-<?= $balance > 0 ? 'danger' : 'success' ?>">
            <div class="card-body text-center">
                <div class="text-xs fw-bold text-<?= $balance > 0 ? 'danger' : 'success' ?> text-uppercase">Balance</div>
                <div class="h4 mb-0 fw-bold text-<?= $balance > 0 ? 'danger' : 'success' ?>">Rs. <?= money($balance) ?></div>
                <?php if ($balance > 0): ?>
                <small class="text-muted">Outstanding balance</small>
                <?php else: ?>
                <small class="text-muted">No dues</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card border-start-primary">
    <div class="card-header bg-transparent">
        <div class="row text-center small fw-bold">
            <div class="col text-success">Purchases (Dr)</div>
            <div class="col text-warning">Payments (Cr)</div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Reference</th>
                        <th>Details</th>
                        <th class="text-end">Debit (Rs.)</th>
                        <th class="text-end">Credit (Rs.)</th>
                        <th class="text-end">Balance (Rs.)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($entries)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No records found.</td></tr>
                    <?php else: ?>
                    <?php
                    $running = 0;
                    foreach ($entries as $e):
                        $running += $e['debit'] - $e['credit'];
                    ?>
                    <tr>
                        <td><?= date('d M Y', strtotime($e['date'])) ?></td>
                        <td>
                            <?php if ($e['type'] === 'purchase'): ?>
                            <a href="/poultry_shop/purchases/view.php?id=<?= $e['id'] ?>"><?= htmlspecialchars($e['ref']) ?></a>
                            <?php else: ?>
                            <span class="text-muted"><?= htmlspecialchars($e['ref']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($e['details']) ?>
                            <?php if ($e['notes']): ?>
                            <br><small class="text-muted"><?= htmlspecialchars($e['notes']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-danger fw-bold"><?= $e['debit'] > 0 ? money($e['debit']) : '-' ?></td>
                        <td class="text-end text-success fw-bold"><?= $e['credit'] > 0 ? money($e['credit']) : '-' ?></td>
                        <td class="text-end fw-bold <?= $running > 0 ? 'text-danger' : 'text-success' ?>"><?= money($running) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="3" class="text-end">Totals</th>
                        <th class="text-end text-danger">Rs. <?= money($total_purchases) ?></th>
                        <th class="text-end text-success">Rs. <?= money($total_payments) ?></th>
                        <th class="text-end <?= $balance > 0 ? 'text-danger' : 'text-success' ?>">Rs. <?= money($balance) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<?php elseif ($supplier_id): ?>
<div class="alert alert-danger">Supplier not found.</div>
<?php else: ?>
<div class="text-center text-muted py-5">
    <i class="fas fa-hand-pointer fa-3x mb-3"></i>
    <p>Select a supplier above to view their ledger.</p>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
