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

if ($supplier) {
    $purchases = $pdo->prepare("SELECT * FROM purchases WHERE supplier_id = ? ORDER BY purchase_date, id");
    $purchases->execute([$supplier_id]);
    foreach ($purchases as $p) {
        $total_purchases += (float)$p['total_cost'];
        $entries[] = $p;
    }
}

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
    <div class="col-md-6">
        <div class="card border-start-primary">
            <div class="card-body">
                <h5 class="fw-bold"><?= htmlspecialchars($supplier['name']) ?></h5>
                <p class="mb-1 small">Phone: <?= htmlspecialchars($supplier['phone'] ?? '-') ?></p>
                <p class="mb-0 small">Email: <?= htmlspecialchars($supplier['email'] ?? '-') ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start-success">
            <div class="card-body text-center">
                <div class="text-xs fw-bold text-success text-uppercase">Total Purchases</div>
                <div class="h4 mb-0 fw-bold">Rs. <?= money($total_purchases) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-start-primary">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table datatable table-hover mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Invoice</th>
                        <th class="text-end">Birds</th>
                        <th class="text-end">Weight (kg)</th>
                        <th class="text-end">Rate (Rs./kg)</th>
                        <th class="text-end">Total Cost (Rs.)</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($entries)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No purchase records found.</td></tr>
                    <?php else: ?>
                    <?php foreach ($entries as $e): ?>
                    <tr>
                        <td><?= date('d M Y', strtotime($e['purchase_date'])) ?></td>
                        <td><a href="/poultry_shop/purchases/view.php?id=<?= $e['id'] ?>"><?= htmlspecialchars($e['invoice_no']) ?></a></td>
                        <td class="text-end"><?= $e['total_birds'] ?></td>
                        <td class="text-end"><?= money($e['total_weight']) ?></td>
                        <td class="text-end"><?= money($e['purchase_rate']) ?></td>
                        <td class="text-end fw-bold"><?= money($e['total_cost']) ?></td>
                        <td><?= htmlspecialchars($e['notes'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php elseif ($supplier_id): ?>
<div class="alert alert-danger">Supplier not found.</div>
<?php else: ?>
<div class="text-center text-muted py-5">
    <i class="fas fa-hand-pointer fa-3x mb-3"></i>
    <p>Select a supplier above to view their purchase ledger.</p>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
