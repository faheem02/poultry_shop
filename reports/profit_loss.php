<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
$pdo = getDB();
$page_title = 'Daily Profit Report';

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to'] ?? date('Y-m-d');

// Revenue - from payments (includes sales + due payments)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE payment_date BETWEEN ? AND ?");
$stmt->execute([$from, $to]);
$revenue = (float)$stmt->fetch()['total'];

// COGS - Average purchase rate × Total weight sold
$stmt = $pdo->prepare("SELECT COALESCE(AVG(purchase_rate), 0) AS avg_rate FROM purchases WHERE purchase_date BETWEEN ? AND ?");
$stmt->execute([$from, $to]);
$avg_purchase_rate = (float)$stmt->fetch()['avg_rate'];

$stmt = $pdo->prepare("SELECT COALESCE(SUM(weight), 0) AS total_weight FROM sales WHERE sale_date BETWEEN ? AND ?");
$stmt->execute([$from, $to]);
$total_weight = (float)$stmt->fetch()['total_weight'];

$cogs = $avg_purchase_rate * $total_weight;

// Expenses
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) AS total FROM expenses WHERE expense_date BETWEEN ? AND ?");
$stmt->execute([$from, $to]);
$expenses = (float)$stmt->fetch()['total'];

// Calculate Profits
$gross_profit = $revenue - $cogs;
$net_profit   = $gross_profit - $expenses;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-chart-pie me-1"></i> Profit & Loss Report</h1>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-auto"><input type="date" name="from" class="form-control form-control-sm" value="<?= $from ?>"></div>
            <div class="col-auto"><input type="date" name="to" class="form-control form-control-sm" value="<?= $to ?>"></div>
            <div class="col-auto"><button class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i> View</button></div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3 col-6 mb-3">
        <div class="card border-start-primary dashboard-card">
            <div class="card-body text-center">
                <div class="text-xs fw-bold text-primary text-uppercase">Revenue</div>
                <div class="h4 mb-0 fw-bold text-primary">Rs. <?= money($revenue) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="card border-start-info dashboard-card">
            <div class="card-body text-center">
                <div class="text-xs fw-bold text-info text-uppercase">COGS</div>
                <div class="h4 mb-0 fw-bold">Rs. <?= money($cogs) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="card border-start-warning dashboard-card">
            <div class="card-body text-center">
                <div class="text-xs fw-bold text-warning text-uppercase">Expenses</div>
                <div class="h4 mb-0 fw-bold">Rs. <?= money($expenses) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="card border-start-<?= $net_profit >= 0 ? 'success' : 'danger' ?> dashboard-card">
            <div class="card-body text-center">
                <div class="text-xs fw-bold text-<?= $net_profit >= 0 ? 'success' : 'danger' ?> text-uppercase">Net Profit</div>
                <div class="h4 mb-0 fw-bold text-<?= $net_profit >= 0 ? 'success' : 'danger' ?>">Rs. <?= money($net_profit) ?></div>
            </div>
        </div>
    </div>
</div>

<table class="table table-bordered">
    <tr><td class="fw-bold" style="width:200px;">Total Revenue</td><td>Rs. <?= money($revenue) ?></td></tr>
    <tr><td class="fw-bold">Total Weight Sold</td><td><?= number_format($total_weight, 2) ?> KG</td></tr>
    <tr><td class="fw-bold">Avg Purchase Rate</td><td>Rs. <?= money($avg_purchase_rate) ?>/KG</td></tr>
    <tr><td class="fw-bold">Cost of Goods Sold</td><td>Rs. <?= money($cogs) ?></td></tr>
    <tr><td class="fw-bold">Gross Profit</td><td class="fw-bold <?= $gross_profit >= 0 ? 'text-success' : 'text-danger' ?>">Rs. <?= money($gross_profit) ?></td></tr>
    <tr><td class="fw-bold">Total Expenses</td><td class="text-warning fw-bold">Rs. <?= money($expenses) ?></td></tr>
    <tr class="table-active"><td class="fw-bold fs-5">Net Profit</td><td class="fw-bold fs-5 <?= $net_profit >= 0 ? 'text-success' : 'text-danger' ?>">Rs. <?= money($net_profit) ?></td></tr>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
