<?php
// admin/sections/payments.php

global $pdo;

// --- Helper Functions ---
function getBadgeClass($status)
{
    switch ($status) {
        case 'successful':
            return 'success';
        case 'pending':
            return 'warning';
        case 'failed':
            return 'danger';
        case 'refunded':
            return 'info';
        default:
            return 'secondary';
    }
}

// --- Filters & Pagination Inputs ---
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// --- Build Query Conditions ---
$where = [];
$params = [];

if ($search) {
    // Search by Reference Number, Client First/Last Name, or Email
    $where[] = "(p.reference_number LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($statusFilter && in_array($statusFilter, ['successful', 'pending', 'failed', 'refunded'])) {
    $where[] = "p.status = :status";
    $params[':status'] = $statusFilter;
}

if ($dateFrom) {
    $where[] = "DATE(p.payment_date) >= :date_from";
    $params[':date_from'] = $dateFrom;
}
if ($dateTo) {
    $where[] = "DATE(p.payment_date) <= :date_to";
    $params[':date_to'] = $dateTo;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// --- Queries ---

// 1. Total Count for Pagination
$countSql = "
    SELECT COUNT(*) 
    FROM payments p
    LEFT JOIN reservations r ON p.reservation_id = r.reservation_id
    LEFT JOIN users u ON u.user_id = COALESCE(p.user_id, r.user_id)
    $whereClause
";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

// 2. Fetch Data
$sql = "
    SELECT 
        p.reference_number,
        p.amount,
        p.method,
        p.status AS payment_status,
        p.payment_date,
        r.reservation_id,
        r.start_time,
        r.end_time,
        u.first_name,
        u.last_name,
        u.email
    FROM payments p
    LEFT JOIN reservations r ON p.reservation_id = r.reservation_id
    LEFT JOIN users u ON u.user_id = COALESCE(p.user_id, r.user_id)
    $whereClause
    ORDER BY p.payment_date DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val)
    $stmt->bindValue($key, $val);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Summary Stats (Total Revenue, Today's Revenue)
// Note: These summaries respect the current filters if applied, or global if not?
// Let's make them global metrics for 'Overview' context usually, but filtered metrics are also useful.
// For now, let's show GLOBAL metrics to give a business overview.
$globalRevenueStmt = $pdo->query("SELECT SUM(amount) FROM payments WHERE status = 'successful'");
$totalRevenue = $globalRevenueStmt->fetchColumn() ?: 0;

$todayRevenueStmt = $pdo->query("SELECT SUM(amount) FROM payments WHERE status = 'successful' AND DATE(payment_date) = CURDATE()");
$todayRevenue = $todayRevenueStmt->fetchColumn() ?: 0;

$pendingCountStmt = $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'");
$pendingCount = $pendingCountStmt->fetchColumn() ?: 0;

?>

<div class="container-fluid">

    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0">Payments Management</h1>
    </div>

    <!-- Revenue Summary Cards -->
    <div class="row mb-4">
        <!-- Total Revenue -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Revenue (All
                                Time)</div>
                            <div class="h5 mb-0 font-weight-bold"><i class="fas fa-coins text-warning"></i>
                                <?= number_format($totalRevenue, 2) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fa fa-dollar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Revenue -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Revenue (Today)</div>
                            <div class="h5 mb-0 font-weight-bold"><i class="fas fa-coins text-warning"></i>
                                <?= number_format($todayRevenue, 2) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fa fa-calendar-check-o fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Payments -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Payments
                            </div>
                            <div class="h5 mb-0 font-weight-bold"><?= $pendingCount ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fa fa-clock-o fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <form method="GET" class="form-row align-items-center">
                <input type="hidden" name="section" value="payments">

                <div class="col-auto">
                    <div class="input-group mb-2">
                        <div class="input-group-prepend">
                            <div class="input-group-text border-0"><i class="fa fa-search"></i></div>
                        </div>
                        <input type="text" class="form-control border-0 small" name="search"
                            value="<?= htmlspecialchars($search) ?>" placeholder="Search Ref# or Client...">
                    </div>
                </div>

                <div class="col-auto">
                    <select class="custom-select mb-2 mr-sm-2" name="status">
                        <option value="">All Statuses</option>
                        <option value="successful" <?= $statusFilter === 'successful' ? 'selected' : '' ?>>Successful
                        </option>
                        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?>>Failed</option>
                        <option value="refunded" <?= $statusFilter === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                    </select>
                </div>

                <div class="col-auto">
                    <div class="input-group mb-2">
                        <div class="input-group-prepend"><span class="input-group-text border-0 small">From</span></div>
                        <input type="date" class="form-control form-control-sm" name="date_from"
                            value="<?= htmlspecialchars($dateFrom) ?>">
                    </div>
                </div>
                <div class="col-auto">
                    <div class="input-group mb-2">
                        <div class="input-group-prepend"><span class="input-group-text border-0 small">To</span></div>
                        <input type="date" class="form-control form-control-sm" name="date_to"
                            value="<?= htmlspecialchars($dateTo) ?>">
                    </div>
                </div>

                <div class="col-auto">
                    <button type="submit" class="btn btn-primary mb-2 shadow-sm">Filter</button>
                    <a href="?section=payments" class="btn btn-light mb-2 ml-1 text-secondary">Clear</a>
                </div>
            </form>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Ref #</th>
                            <th>Date</th>
                            <th>Client</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($payments) > 0): ?>
                            <?php foreach ($payments as $p): ?>
                                <tr>
                                    <td class="font-weight-bold text-primary">
                                        <?= htmlspecialchars($p['reference_number']) ?>
                                    </td>
                                    <td class="small">
                                        <?= date('M d, Y', strtotime($p['payment_date'])) ?><br>
                                        <span class="text-muted"><?= date('h:i A', strtotime($p['payment_date'])) ?></span>
                                    </td>
                                    <td>
                                        <div class="font-weight-bold">
                                            <?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?>
                                        </div>
                                        <small class="text-muted"><?= htmlspecialchars($p['email']) ?></small>
                                    </td>
                                    <td class="font-weight-bold">
                                        <i class="fas fa-coins text-warning"></i> <?= number_format($p['amount'], 2) ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-light border"><?= ucfirst($p['method']) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= getBadgeClass($p['payment_status']) ?> px-2 py-1">
                                            <?= ucfirst($p['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($p['reservation_id']): ?>
                                            <small class="text-muted">Res ID: <?= $p['reservation_id'] ?></small>
                                        <?php else: ?>
                                            <span class="badge badge-success"><i class="fas fa-plus-circle"></i> Wallet
                                                Top-Up</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-gray-500">
                                    <i class="fa fa-inbox fa-3x mb-3 d-block text-gray-300"></i>
                                    No payments found matching your criteria.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4 mb-4">
                    <ul class="pagination justify-content-center">
                        <?php
                        $queryParams = $_GET;
                        $queryParams['section'] = 'payments';

                        function buildUrl($page, $params)
                        {
                            $params['page'] = $page;
                            return '?' . http_build_query($params);
                        }
                        ?>

                        <!-- First & Previous -->
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= buildUrl(1, $queryParams) ?>">First</a>
                        </li>
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= buildUrl($page - 1, $queryParams) ?>">Previous</a>
                        </li>

                        <!-- Window Loop -->
                        <?php
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                        for ($i = $start; $i <= $end; $i++):
                            ?>
                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                <a class="page-link" href="<?= buildUrl($i, $queryParams) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <!-- Next & Last -->
                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= buildUrl($page + 1, $queryParams) ?>">Next</a>
                        </li>
                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= buildUrl($totalPages, $queryParams) ?>">Last</a>
                        </li>
                    </ul>

                    <!-- Jump to Page -->
                    <form action="" method="GET" class="form-inline justify-content-center mt-2">
                        <?php foreach ($queryParams as $k => $v): ?>
                            <?php if ($k !== 'page'): ?>
                                <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($v) ?>">
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <label class="mr-2 text-muted small">Jump to:</label>
                        <input type="number" name="page" min="1" max="<?= $totalPages ?>"
                            class="form-control form-control-sm border-secondary" style="width: 70px;"
                            placeholder="<?= $page ?>">
                        <button type="submit" class="btn btn-sm btn-outline-primary ml-1">Go</button>
                    </form>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>