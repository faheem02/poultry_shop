<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDB();
$page_title = 'Sales';

// Date filter
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to'] ?? date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT s.*, c.name AS customer_name, ct.name AS chicken_type, u.username AS created_by_name
    FROM sales s
    LEFT JOIN customers c ON c.id = s.customer_id
    JOIN chicken_types ct ON ct.id = s.chicken_type_id
    LEFT JOIN users u ON u.id = s.created_by
    WHERE s.sale_date BETWEEN ? AND ?
    ORDER BY s.id DESC
");
$stmt->execute([$from, $to]);
$sales = $stmt->fetchAll();

$total_net = array_sum(array_column($sales, 'net_total'));
$total_balance = array_sum(array_column($sales, 'balance'));

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Sales</h1>
    <a href="/poultry_shop/pos/index.php" class="btn btn-primary btn-sm">
        <i class="fas fa-plus me-1"></i> New Sale
    </a>
</div>

<div class="card mb-4 border-start-info">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label small">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= $from ?>">
            </div>
            <div class="col-auto">
                <label class="form-label small">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= $to ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter me-1"></i> Filter</button>
            </div>
            <div class="col-auto">
                <span class="badge bg-primary fs-6">Total: Rs. <?= money($total_net) ?></span>
                <span class="badge bg-warning fs-6">Pending: Rs. <?= money($total_balance) ?></span>
            </div>
        </form>
    </div>
</div>

<div class="card border-start-primary">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table datatable table-hover mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Invoice</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th>Weight</th>
                        <th>Rate</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Payment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales as $s): ?>
                    <tr>
                        <td class="fw-bold"><?= htmlspecialchars($s['invoice_no']) ?></td>
                        <td><?= date('d M Y', strtotime($s['sale_date'])) ?></td>
                        <td><?= htmlspecialchars($s['customer_name'] ?? 'Walk-in') ?></td>
                        <td><?= htmlspecialchars($s['chicken_type']) ?></td>
                        <td><?= number_format($s['weight'], 3) ?></td>
                        <td>Rs. <?= money($s['rate_per_kg']) ?></td>
                        <td>Rs. <?= money($s['net_total']) ?></td>
                        <td>Rs. <?= money($s['paid_amount']) ?></td>
                        <td><?= $s['balance'] > 0 ? '<span class="text-danger fw-bold">Rs. ' . money($s['balance']) . '</span>' : '<span class="text-success">Paid</span>' ?></td>
                        <td><span class="badge bg-secondary"><?= ucfirst($s['payment_method']) ?></span></td>
                        <td>
                            <a href="view.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-info"><i class="fas fa-eye"></i></a>
                            <a href="invoice.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary" target="_blank"><i class="fas fa-print"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
