<?php
// admin/sections/activity_logs.php

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$current_user_type = $_SESSION['user_type'];

// Only Admin and Staff can access this page
if ($current_user_type !== 'admin' && $current_user_type !== 'staff') {
    echo '<div class="alert alert-danger">Unauthorized access.</div>';
    exit();
}

// Filter Logic
$actionFilter = isset($_GET['action']) ? $_GET['action'] : '';
$params = [];

// Base Query
$sql = "SELECT l.*, u.username, u.email 
        FROM activity_logs l 
        LEFT JOIN users u ON l.user_id = u.user_id 
        WHERE 1=1";

// Role-Based Filtering
if ($current_user_type === 'staff') {
    // Staff can only see logs for clients and other staff
    $sql .= " AND l.user_type IN ('client', 'staff')";
}

// Action Filter
if (!empty($actionFilter)) {
    $sql .= " AND l.action = ?";
    $params[] = $actionFilter;
}

// Order by newest first
$sql .= " ORDER BY l.created_at DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique actions for filter dropdown
$actionStmt = $pdo->prepare("SELECT DISTINCT action FROM activity_logs ORDER BY action ASC");
$actionStmt->execute();
$actions = $actionStmt->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4 bg-dark-theme text-light">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Activity Logs</h6>

                <form class="form-inline" method="GET">
                    <input type="hidden" name="section" value="activity_logs">
                    <label class="mr-2 text-muted">Filter by Action:</label>
                    <select name="action" class="form-control form-control-sm bg-dark text-light border-secondary mr-2"
                        onchange="this.form.submit()">
                        <option value="">All Actions</option>
                        <?php foreach ($actions as $act): ?>
                            <option value="<?= htmlspecialchars($act) ?>" <?= $actionFilter === $act ? 'selected' : '' ?>>
                                <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $act))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($actionFilter): ?>
                        <a href="?section=activity_logs" class="btn btn-sm btn-secondary">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-dark table-hover" id="activityTable" width="100%"
                        cellspacing="0">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Action</th>
                                <th>Details</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($logs) === 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No activity logs found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($log['created_at']) ?></td>
                                        <td>
                                            <?php if ($log['user_id']): ?>
                                                <?php if ($log['username']): ?>
                                                    <?= htmlspecialchars($log['username']) ?>
                                                    <small class="d-block text-muted"><?= htmlspecialchars($log['email']) ?></small>
                                                <?php else: ?>
                                                    <span class="text-danger">Deleted User (ID: <?= $log['user_id'] ?>)</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">System / Guest</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $badgeClass = 'secondary';
                                            if ($log['user_type'] === 'admin')
                                                $badgeClass = 'danger';
                                            if ($log['user_type'] === 'staff')
                                                $badgeClass = 'warning';
                                            if ($log['user_type'] === 'client')
                                                $badgeClass = 'info';
                                            ?>
                                            <span class="badge badge-<?= $badgeClass ?>">
                                                <?= ucfirst($log['user_type']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="font-weight-bold text-light">
                                                <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $log['action']))) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($log['details']) ?></td>
                                        <td>
                                            <code class="text-muted"><?= htmlspecialchars($log['ip_address']) ?></code>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>