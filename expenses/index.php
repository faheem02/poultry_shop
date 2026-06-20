<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDB();
$page_title = 'Expenses';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) die('CSRF failed');

    if ($_POST['action'] === 'create') {
        $stmt = $pdo->prepare("INSERT INTO expenses (expense_category, amount, description, expense_date, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['expense_category'],
            (float)$_POST['amount'],
            sanitize($_POST['description'] ?? ''),
            $_POST['expense_date'] ?: date('Y-m-d'),
            $_SESSION['user_id']
        ]);
        setFlash('Expense added successfully.');
    } elseif ($_POST['action'] === 'update') {
        $stmt = $pdo->prepare("UPDATE expenses SET expense_category=?, amount=?, description=?, expense_date=? WHERE id=?");
        $stmt->execute([
            $_POST['expense_category'],
            (float)$_POST['amount'],
            sanitize($_POST['description'] ?? ''),
            $_POST['expense_date'] ?: date('Y-m-d'),
            (int)$_POST['id']
        ]);
        setFlash('Expense updated successfully.');
    } elseif ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('Expense deleted.');
    }
    header('Location: /poultry_shop/expenses/index.php');
    exit;
}

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to'] ?? date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT e.*, u.username AS created_by_name
    FROM expenses e
    LEFT JOIN users u ON u.id = e.created_by
    WHERE e.expense_date BETWEEN ? AND ?
    ORDER BY e.expense_date DESC, e.id DESC
");
$stmt->execute([$from, $to]);
$expenses = $stmt->fetchAll();

$categories = ['labour', 'transport', 'electricity', 'misc'];

// Totals
$totals = [];
foreach ($categories as $cat) {
    $s = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) AS t FROM expenses WHERE expense_category = ? AND expense_date BETWEEN ? AND ?");
    $s->execute([$cat, $from, $to]);
    $totals[$cat] = (float)$s->fetch()['t'];
}
$grand_total = array_sum($totals);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Expenses</h1>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#expenseModal">
        <i class="fas fa-plus me-1"></i> Add Expense
    </button>
</div>

<!-- Summary -->
<div class="row mb-4">
    <?php foreach ($totals as $cat => $total): ?>
    <div class="col-md-3 col-6 mb-3">
        <div class="card border-start-warning dashboard-card h-100">
            <div class="card-body text-center py-3">
                <div class="text-xs fw-bold text-warning text-uppercase"><?= ucfirst($cat) ?></div>
                <div class="h5 mb-0 fw-bold">Rs. <?= money($total) ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <div class="col-md-3 col-6 mb-3">
        <div class="card border-start-danger dashboard-card h-100">
            <div class="card-body text-center py-3">
                <div class="text-xs fw-bold text-danger text-uppercase">Total</div>
                <div class="h5 mb-0 fw-bold">Rs. <?= money($grand_total) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="card mb-4 border-start-info">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto"><input type="date" name="from" class="form-control form-control-sm" value="<?= $from ?>"></div>
            <div class="col-auto"><input type="date" name="to" class="form-control form-control-sm" value="<?= $to ?>"></div>
            <div class="col-auto"><button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter me-1"></i> Filter</button></div>
        </form>
    </div>
</div>

<div class="card border-start-primary">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table datatable table-hover mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th>Added By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expenses as $e): ?>
                    <tr>
                        <td><?= date('d M Y', strtotime($e['expense_date'])) ?></td>
                        <td><span class="badge bg-warning text-dark"><?= ucfirst($e['expense_category']) ?></span></td>
                        <td class="fw-bold text-danger">Rs. <?= money($e['amount']) ?></td>
                        <td><?= htmlspecialchars($e['description'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($e['created_by_name'] ?? '-') ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary edit-btn"
                                data-id="<?= $e['id'] ?>"
                                data-cat="<?= $e['expense_category'] ?>"
                                data-amount="<?= $e['amount'] ?>"
                                data-desc="<?= htmlspecialchars(str_replace(["\r","\n"],' ', $e['description'] ?? ''), ENT_QUOTES) ?>"
                                data-date="<?= $e['expense_date'] ?>"
                                data-bs-toggle="modal" data-bs-target="#editModal">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger btn-delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="expenseModal">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <div class="modal-header">
                <h5 class="modal-title">Add Expense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Category *</label>
                    <select name="expense_category" class="form-select" required>
                        <?php foreach ($categories as $c): ?>
                        <option value="<?= $c ?>"><?= ucfirst($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Amount (Rs.) *</label>
                    <input type="number" name="amount" class="form-control" step="0.01" min="0" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <div class="modal-header">
                <h5 class="modal-title">Edit Expense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Category *</label>
                    <select name="expense_category" id="edit_cat" class="form-select" required>
                        <?php foreach ($categories as $c): ?>
                        <option value="<?= $c ?>"><?= ucfirst($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Amount (Rs.) *</label>
                    <input type="number" name="amount" id="edit_amount" class="form-control" step="0.01" min="0" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="expense_date" id="edit_date" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="edit_desc" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).on('click', '.edit-btn', function () {
    $('#edit_id').val($(this).data('id'));
    $('#edit_cat').val($(this).data('cat'));
    $('#edit_amount').val($(this).data('amount'));
    $('#edit_date').val($(this).data('date'));
    $('#edit_desc').val($(this).data('desc'));
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
