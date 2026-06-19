<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDB();
$page_title = 'Chicken Rates';

// Create / Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) die('CSRF validation failed');

    if ($_POST['action'] === 'save') {
        $chicken_type_id = (int)$_POST['chicken_type_id'];
        $rate_per_kg     = (float)$_POST['rate_per_kg'];
        $rate_date       = $_POST['rate_date'] ?: date('Y-m-d');

        if ($chicken_type_id && $rate_per_kg > 0) {
            // Upsert: if rate exists for this type+date, update it
            $stmt = $pdo->prepare("
                INSERT INTO chicken_rates (chicken_type_id, rate_per_kg, rate_date, created_by)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE rate_per_kg = VALUES(rate_per_kg), created_by = VALUES(created_by)
            ");
            $stmt->execute([$chicken_type_id, $rate_per_kg, $rate_date, $_SESSION['user_id']]);
            setFlash('Rate saved successfully.');
        } else {
            setFlash('Please select chicken type and enter valid rate.');
        }
    } elseif ($_POST['action'] === 'update') {
        $id          = (int)$_POST['id'];
        $rate_per_kg = (float)$_POST['rate_per_kg'];
        $rate_date   = $_POST['rate_date'] ?: date('Y-m-d');
        if ($id && $rate_per_kg > 0) {
            $stmt = $pdo->prepare("UPDATE chicken_rates SET rate_per_kg = ?, rate_date = ?, created_by = ? WHERE id = ?");
            $stmt->execute([$rate_per_kg, $rate_date, $_SESSION['user_id'], $id]);
            setFlash('Rate updated successfully.');
        }
    } elseif ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM chicken_rates WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('Rate deleted successfully.');
    }
    header('Location: /poultry_shop/chicken_rates/index.php');
    exit;
}

$types = $pdo->query("SELECT id, name FROM chicken_types ORDER BY name")->fetchAll();

// Get today's rate for each type
$today_rates = [];
$stmt = $pdo->query("SELECT cr.*, ct.name AS type_name
    FROM chicken_rates cr
    JOIN chicken_types ct ON ct.id = cr.chicken_type_id
    WHERE cr.rate_date = CURDATE()
    ORDER BY ct.name");
while ($row = $stmt->fetch()) {
    $today_rates[$row['chicken_type_id']] = $row;
}

// All rates history
$all_rates = $pdo->query("
    SELECT cr.*, ct.name AS type_name, u.username AS created_by_name
    FROM chicken_rates cr
    JOIN chicken_types ct ON ct.id = cr.chicken_type_id
    LEFT JOIN users u ON u.id = cr.created_by
    ORDER BY cr.rate_date DESC, ct.name
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Chicken Rates</h1>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#rateModal">
        <i class="fas fa-plus me-1"></i> Set Today's Rate
    </button>
</div>

<!-- Today's Rates -->
<div class="row mb-4">
    <?php foreach ($types as $t): ?>
    <div class="col-md-3 col-6 mb-3">
        <div class="card border-start-primary dashboard-card h-100">
            <div class="card-body text-center">
                <div class="text-xs fw-bold text-primary text-uppercase mb-1"><?= htmlspecialchars($t['name']) ?></div>
                <?php if (isset($today_rates[$t['id']])): ?>
                    <div class="h4 mb-0 fw-bold text-gray-800">Rs. <?= money($today_rates[$t['id']]['rate_per_kg']) ?></div>
                    <small class="text-muted">/ KG</small>
                <?php else: ?>
                    <div class="h6 mb-0 text-muted">Not set</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Rate History -->
<div class="card">
    <div class="card-header"><i class="fas fa-history me-1"></i> Rate History</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table datatable table-hover mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Chicken Type</th>
                        <th>Rate/KG</th>
                        <th>Set By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_rates as $r): ?>
                    <tr>
                        <td><?= date('d M Y', strtotime($r['rate_date'])) ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($r['type_name']) ?></td>
                        <td>Rs. <?= money($r['rate_per_kg']) ?></td>
                        <td><?= htmlspecialchars($r['created_by_name'] ?? '-') ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary btn-edit-rate"
                                data-id="<?= $r['id'] ?>"
                                data-type="<?= htmlspecialchars($r['type_name']) ?>"
                                data-rate="<?= $r['rate_per_kg'] ?>"
                                data-date="<?= $r['rate_date'] ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" class="delete-rate-form d-inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-rate">
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

<!-- Edit Rate Modal -->
<div class="modal fade" id="editRateModal">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-1"></i> Edit Rate — <span id="edit_type_label"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Rate Per KG (Rs.)</label>
                    <input type="number" name="rate_per_kg" id="edit_rate_per_kg" class="form-control form-control-lg" step="0.01" min="0" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="rate_date" id="edit_rate_date" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Update Rate</button>
            </div>
        </form>
    </div>
</div>

<!-- Set Rate Modal -->
<div class="modal fade" id="rateModal">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <div class="modal-header">
                <h5 class="modal-title">Set Chicken Rate</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Chicken Type</label>
                    <select name="chicken_type_id" class="form-select" required>
                        <option value="">-- Select --</option>
                        <?php foreach ($types as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Rate Per KG (Rs.)</label>
                    <input type="number" name="rate_per_kg" class="form-control" step="0.01" min="0" required placeholder="e.g. 700">
                </div>
                <div class="mb-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="rate_date" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Rate</button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).on('click', '.btn-edit-rate', function () {
    $('#edit_id').val($(this).data('id'));
    $('#edit_type_label').text($(this).data('type'));
    $('#edit_rate_per_kg').val(parseFloat($(this).data('rate')).toFixed(2));
    $('#edit_rate_date').val($(this).data('date'));
    new bootstrap.Modal(document.getElementById('editRateModal')).show();
});

$(document).on('click', '.btn-delete-rate', function () {
    const form = $(this).closest('form');
    Swal.fire({
        title: 'Delete this rate?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e3342f',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
