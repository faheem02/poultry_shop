<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT p.*, sl.chicken_type_id AS ledger_chicken_type_id
    FROM purchases p
    LEFT JOIN stock_ledger sl ON sl.reference_id = p.id AND sl.transaction_type = 'purchase'
    WHERE p.id = ?
");
$stmt->execute([$id]);
$purchase = $stmt->fetch();

if (!$purchase) {
    header('Location: index.php');
    exit;
}

$suppliers = $pdo->query("SELECT id, name FROM suppliers ORDER BY name")->fetchAll();
$types     = $pdo->query("SELECT id, name FROM chicken_types ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) die('CSRF failed');

    $supplier_id    = (int)$_POST['supplier_id'];
    $chicken_type_id = (int)$_POST['chicken_type_id'];
    $invoice_no     = sanitize($_POST['invoice_no']);
    $total_birds    = (int)$_POST['total_birds'];
    $total_weight   = (float)$_POST['total_weight'];
    $purchase_rate  = (float)$_POST['purchase_rate'];
    $purchase_date  = $_POST['purchase_date'] ?: date('Y-m-d');
    $farm_name      = sanitize($_POST['farm_name'] ?? '');
    $vehicle_no     = sanitize($_POST['vehicle_no'] ?? '');
    $total_cost     = $total_weight * $purchase_rate;

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            UPDATE purchases
            SET supplier_id = ?, invoice_no = ?, total_birds = ?, total_weight = ?,
                purchase_rate = ?, total_cost = ?, purchase_date = ?,
                farm_name = ?, vehicle_no = ?
            WHERE id = ?
        ");
        $stmt->execute([$supplier_id, $invoice_no, $total_birds, $total_weight, $purchase_rate, $total_cost, $purchase_date, $farm_name, $vehicle_no, $id]);

        $stmt = $pdo->prepare("
            UPDATE stock_ledger
            SET transaction_date = ?, chicken_type_id = ?, birds_count = ?, weight_kg = ?,
                rate_per_kg = ?, amount = ?, notes = ?
            WHERE reference_id = ? AND transaction_type = 'purchase'
        ");
        $stmt->execute([$purchase_date, $chicken_type_id, $total_birds, $total_weight, $purchase_rate, $total_cost, $id, 'Purchase: ' . ($invoice_no ?: 'N/A')]);

        $pdo->commit();
        setFlash('Purchase updated successfully. Stock updated.');
        header('Location: ' . BASE_URL . '/pages/purchases/index.php');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('Error: ' . $e->getMessage());
        header('Location: edit.php?id=' . $id);
        exit;
    }
}

$page_title = 'Edit Purchase #' . $purchase['id'];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-edit me-1"></i> Edit Purchase
    </h1>
    <a href="<?= BASE_URL ?>/pages/purchases/index.php" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow border-start-primary">
            <div class="card-header py-3">
                <h5 class="mb-0 fw-bold"><i class="fas fa-truck me-2"></i>Purchase Information</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Supplier</label>
                            <select name="supplier_id" class="form-select" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($suppliers as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= $s['id'] === (int)$purchase['supplier_id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Chicken Type</label>
                            <select name="chicken_type_id" class="form-select" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($types as $t): ?>
                                <option value="<?= $t['id'] ?>" <?= $t['id'] === (int)($purchase['ledger_chicken_type_id'] ?? 0) ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Date</label>
                            <input type="date" name="purchase_date" class="form-control" value="<?= $purchase['purchase_date'] ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Invoice No.</label>
                            <input type="text" name="invoice_no" class="form-control" value="<?= htmlspecialchars($purchase['invoice_no'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Total Weight (KG)</label>
                            <input type="number" name="total_weight" class="form-control" step="0.001" min="0" required value="<?= $purchase['total_weight'] ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Purchase Rate/KG</label>
                            <input type="number" name="purchase_rate" class="form-control" step="0.01" min="0" required value="<?= $purchase['purchase_rate'] ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Total Cost (Auto)</label>
                            <input type="text" class="form-control" id="total_cost_display" readonly value="<?= money($purchase['total_cost']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Farm Name</label>
                            <input type="text" name="farm_name" class="form-control" value="<?= htmlspecialchars($purchase['farm_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Vehicle No.</label>
                            <input type="text" name="vehicle_no" class="form-control" value="<?= htmlspecialchars($purchase['vehicle_no'] ?? '') ?>">
                        </div>
                    </div>

                    <hr>
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="<?= BASE_URL ?>/pages/purchases/index.php" class="btn btn-outline-secondary"><i class="fas fa-times me-1"></i> Cancel</a>
                        <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Update Purchase</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('[name="total_weight"], [name="purchase_rate"]').forEach(function(el) {
    el.addEventListener('input', function () {
        var w = parseFloat(document.querySelector('[name="total_weight"]').value) || 0;
        var r = parseFloat(document.querySelector('[name="purchase_rate"]').value) || 0;
        document.getElementById('total_cost_display').value = (w * r).toFixed(2);
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
