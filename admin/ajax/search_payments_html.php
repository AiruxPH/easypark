<?php
// admin/ajax/search_payments_html.php
require_once '../../includes/db.php';

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

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($search) {
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

// Count
$countSql = "SELECT COUNT(*) FROM payments p
    LEFT JOIN reservations r ON p.reservation_id = r.reservation_id
    LEFT JOIN users u ON u.user_id = COALESCE(p.user_id, r.user_id)
    $whereClause";
$countStmt = $pdo->prepare($countSql);
foreach ($params as $k => $v)
    $countStmt->bindValue($k, $v);
$countStmt->execute();
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

// Fetch
$sql = "SELECT 
        p.reference_number,
        p.amount,
        p.method,
        p.status AS payment_status,
        p.payment_date,
        r.reservation_id,
        u.first_name,
        u.last_name,
        u.email
    FROM payments p
    LEFT JOIN reservations r ON p.reservation_id = r.reservation_id
    LEFT JOIN users u ON u.user_id = COALESCE(p.user_id, r.user_id)
    $whereClause
    ORDER BY p.payment_date DESC
    LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v)
    $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
// Table Body
if (count($payments) > 0):
    foreach ($payments as $p): ?>
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
                    <span class="badge badge-success"><i class="fas fa-plus-circle"></i> Wallet Top-Up</span>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach;
else: ?>
    <tr>
        <td colspan="7" class="text-center py-4 text-gray-500">
            <i class="fa fa-inbox fa-3x mb-3 d-block text-gray-300"></i>
            No payments found matching your criteria.
        </td>
    </tr>
<?php endif;
$tableHtml = ob_get_clean();

ob_start();
// Pagination
if ($totalPages > 1): ?>
    <ul class="pagination justify-content-center mb-0">
        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
            <a class="page-link" href="#" data-page="1">First</a>
        </li>
        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
            <a class="page-link" href="#" data-page="<?= $page - 1 ?>">Previous</a>
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
