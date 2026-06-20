<?php
date_default_timezone_set('Asia/Karachi');
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDB();
$page_title = 'Payments';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) die('CSRF failed');

    if ($_POST['action'] === 'create') {
        $cust_id = (int)$_POST['customer_id'];
        $amount  = (float)$_POST['amount'];
        $method  = $_POST['payment_method'];
        $notes   = sanitize($_POST['notes'] ?? '');
        $date    = $_POST['payment_date'] ?: date('Y-m-d');

        if ($cust_id && $amount > 0) {
            $stmt = $pdo->prepare("INSERT INTO payments (customer_id, amount, payment_method, notes, payment_date, received_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$cust_id, $amount, $method, $notes, $date, $_SESSION['user_id']]);
            setFlash('Payment recorded successfully.');
        } else {
            setFlash('Select a customer and enter amount.');
        }
    } elseif ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM payments WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('Payment deleted.');
    }
    header('Location: /poultry_shop/payments/index.php');
    exit;
}

$customers = $pdo->query("SELECT id, name FROM customers ORDER BY name")->fetchAll();
$payments = $pdo->query("
    SELECT p.*, c.name AS customer_name, u.username AS received_by_name
    FROM payments p
    LEFT JOIN customers c ON c.id = p.customer_id
    LEFT JOIN users u ON u.id = p.received_by
    ORDER BY p.payment_date DESC, p.id DESC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Customer Payments</h1>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#paymentModal">
        <i class="fas fa-plus me-1"></i> Record Payment
    </button>
</div>

<div class="card border-start-primary">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table datatable table-hover mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Notes</th>
                        <th>Received By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?= date('d M Y', strtotime($p['payment_date'])) ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($p['customer_name']) ?></td>
                        <td class="fw-bold text-success">Rs. <?= money($p['amount']) ?></td>
                        <td><span class="badge bg-secondary"><?= ucfirst($p['payment_method']) ?></span></td>
                        <td><?= htmlspecialchars($p['notes'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($p['received_by_name'] ?? '-') ?></td>
                        <td>
                            <form method="POST" class="d-inline delete-payment-form">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-payment">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <div class="modal-header">
                <h5 class="modal-title">Record Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Customer *</label>
                    <select name="customer_id" class="form-select" required>
                        <option value="">-- Select --</option>
                        <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Amount (Rs.) *</label>
                    <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" class="form-select">
                        <option value="cash">Cash</option>
                        <option value="bank">Bank</option>
                        <option value="credit">Credit</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Record</button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).on('click', '.btn-delete-payment', function () {
    const form = $(this).closest('form');
    Swal.fire({
        title: 'Delete this payment?',
        text: 'This will affect the customer balance.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e3342f',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (result.isConfirmed) form.submit();
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
