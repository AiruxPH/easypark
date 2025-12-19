<?php
// admin/ajax/search_activity_logs_html.php
require_once '../../includes/db.php';

// Filter & Pagination Logic
$actionFilter = isset($_GET['action']) ? $_GET['action'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$currentUserType = $_GET['user_type_context'] ?? 'admin'; // Passed from frontend context

$params = [];
$whereClauses = ["1=1"];

// Role-Based Filtering
if ($currentUserType === 'staff') {
    $whereClauses[] = "l.user_type IN ('client', 'staff')";
}

// Action Filter
if (!empty($actionFilter)) {
    $whereClauses[] = "l.action = ?";
    $params[] = $actionFilter;
}

// Search Filter
if (!empty($search)) {
    $whereClauses[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR l.details LIKE ? OR l.action LIKE ?)";
    $term = "%$search%";
    $params = array_merge($params, [$term, $term, $term, $term, $term]);
}

$whereSql = implode(' AND ', $whereClauses);

// Count
$countSql = "SELECT COUNT(*) FROM activity_logs l LEFT JOIN users u ON l.user_id = u.user_id WHERE $whereSql";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$totalRecords = $stmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Fetch Logs
$sql = "SELECT l.*, u.first_name, u.last_name, u.email 
        FROM activity_logs l 
        LEFT JOIN users u ON l.user_id = u.user_id 
        WHERE $whereSql
        ORDER BY l.created_at DESC 
        LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
// Table Body
if (count($logs) === 0): ?>
    <tr>
        <td colspan="6" class="text-center text-muted">No activity logs found.</td>
    </tr>
<?php else:
    foreach ($logs as $log): ?>
        <tr>
            <td><?= htmlspecialchars($log['created_at']) ?></td>
            <td>
                <?php if ($log['user_id']): ?>
                    <?php if (isset($log['first_name'])): ?>
                        <?= htmlspecialchars($log['first_name'] . ' ' . $log['last_name']) ?>
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
    <?php endforeach;
endif;
$tableHtml = ob_get_clean();

ob_start();
// Pagination
if ($totalPages > 1): ?>
    <ul class="pagination justify-content-center">
        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
            <a class="page-link" href="#" data-page="1">First</a>
        </li>
        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
            <a class="page-link" href="#" data-page="<?= $page - 1 ?>">Prev</a>
        </li>
        <?php
        $start = max(1, $page - 2);
        $end = min($totalPages, $page + 2);
        for ($i = $start; $i <= $end; $i++):
            ?>
            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                <a class="page-link" href="#" data-page="<?= $i ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
            <a class="page-link" href="#" data-page="<?= $page + 1 ?>">Next</a>
        </li>
        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
            <a class="page-link" href="#" data-page="<?= $totalPages ?>">Last</a>
        </li>
    </ul>
<?php endif;
$paginationHtml = ob_get_clean();

echo json_encode([
    'success' => true,
    'table_html' => $tableHtml,
    'pagination_html' => $paginationHtml
]);
