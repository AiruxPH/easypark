<?php
require_once __DIR__ . '/section-common.php';

// Activity Logs Section

// Filters
$search = trim($_GET['search'] ?? '');
$filter_action = trim($_GET['action'] ?? '');
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');

// Pagination
$page = isset($_GET['logs_page']) ? (int) $_GET['logs_page'] : 1;
if ($page < 1)
    $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Base Query
$where = ["user_id = ?"];
$params = [$staff_id];

if ($search) {
    $where[] = "(details LIKE ? OR action LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_action) {
    $where[] = "action = ?";
    $params[] = $filter_action;
}

if ($date_from) {
    $where[] = "DATE(created_at) >= ?";
    $params[] = $date_from;
}
if ($date_to) {
    $where[] = "DATE(created_at) <= ?";
    $params[] = $date_to;
}

$whereClause = "WHERE " . implode(" AND ", $where);

// Count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs $whereClause");
$countStmt->execute($params);
$total_logs = $countStmt->fetchColumn();
$total_pages = ceil($total_logs / $limit);

// Fetch
$sql = "SELECT * FROM activity_logs $whereClause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getActionBadge($action)
{
    $a = strtolower($action);
    if (strpos($a, 'confirm') !== false || strpos($a, 'add') !== false || strpos($a, 'create') !== false)
        return 'badge-glass-success';
    if (strpos($a, 'cancel') !== false || strpos($a, 'delete') !== false || strpos($a, 'remove') !== false)
        return 'badge-glass-danger';
    if (strpos($a, 'update') !== false || strpos($a, 'edit') !== false || strpos($a, 'change') !== false)
        return 'badge-glass-warning';
    if (strpos($a, 'login') !== false || strpos($a, 'arrive') !== false)
        return 'badge-glass-info';
    return 'badge-secondary'; // Default opacity
}
?>

<div class="glass-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="text-info mb-0"><i class="fas fa-clipboard-list mr-2"></i> My Activity Logs</h4>
        <span class="badge badge-glass-info font-weight-normal">Total: <?= $total_logs ?></span>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-md-4 mb-2">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-transparent border-secondary text-white-50"><i
                            class="fas fa-search"></i></span>
                </div>
                <input type="text" id="logSearch" class="form-control glass-input border-left-0"
                    placeholder="Search details..." value="<?= htmlspecialchars($search) ?>">
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <select id="logActionFilter" class="form-control glass-input">
                <option value="">All Actions</option>
                <option value="login" <?= $filter_action === 'login' ? 'selected' : '' ?>>Login</option>
                <option value="confirm_booking" <?= $filter_action === 'confirm_booking' ? 'selected' : '' ?>>Confirm
                    Booking</option>
                <option value="cancel_booking" <?= $filter_action === 'cancel_booking' ? 'selected' : '' ?>>Cancel Booking
                </option>
                <option value="mark_arrived" <?= $filter_action === 'mark_arrived' ? 'selected' : '' ?>>Mark Arrived
                </option>
                <option value="update_profile" <?= $filter_action === 'update_profile' ? 'selected' : '' ?>>Update Profile
                </option>
            </select>
        </div>
        <div class="col-md-5 mb-2">
            <div class="input-group">
                <div class="input-group-prepend"><span
                        class="input-group-text bg-transparent border-secondary text-white-50">Date</span></div>
                <input type="date" id="logDateFrom" class="form-control glass-input"
                    value="<?= htmlspecialchars($date_from) ?>">
                <div class="input-group-prepend input-group-append"><span
                        class="input-group-text bg-transparent border-secondary text-white-50">to</span></div>
                <input type="date" id="logDateTo" class="form-control glass-input"
                    value="<?= htmlspecialchars($date_to) ?>">
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table glass-table table-hover">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Action</th>
                    <th>Details</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($logs) === 0): ?>
                    <tr>
                        <td colspan="4" class="text-center py-5 text-white-50">
                            <i class="fas fa-history fa-3x mb-3 d-block opacity-50"></i>
                            No activity logs found.
                        </td>
                    </tr>
                <?php else:
                    foreach ($logs as $log): ?>
                        <tr>
                            <td style="white-space: nowrap;"><?= date('M d, Y h:i A', strtotime($log['created_at'])) ?></td>
                            <td>
                                <span class="badge <?= getActionBadge($log['action']) ?>">
                                    <?= htmlspecialchars(ucwords(str_replace('_', ' ', $log['action']))) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($log['details']) ?></td>
                            <td class="text-white-50 small"><?= htmlspecialchars($log['ip_address']) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <nav aria-label="Logs pagination" class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="logs_page=<?= $page - 1 ?>" data-page="<?= $page - 1 ?>">Previous</a>
                </li>
                <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                    <li class="page-item <?= ($page == $p) ? 'active' : '' ?>">
                        <a class="page-link" href="logs_page=<?= $p ?>" data-page="<?= $p ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="logs_page=<?= $page + 1 ?>" data-page="<?= $page + 1 ?>">Next</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<script>
    $(document).ready(function () {
        window.filterLogs = function () {
            var search = $('#logSearch').val();
            var action = $('#logActionFilter').val();
            var dateFrom = $('#logDateFrom').val();
            var dateTo = $('#logDateTo').val();

            var params = {};
            if (search) params.search = search;
            if (action) params.action = action;
            if (dateFrom) params.date_from = dateFrom;
            if (dateTo) params.date_to = dateTo;

            loadSection('activity', params);
        };

        var timeout = null;
        $('#logSearch').on('input', function () {
            clearTimeout(timeout);
            timeout = setTimeout(filterLogs, 500);
        });
        $('#logActionFilter, #logDateFrom, #logDateTo').on('change', filterLogs);
    });
</script>