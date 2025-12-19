<?php
// admin/ajax/search_users_html.php
require_once '../../includes/db.php';

// Helper for sorting links (redefined since we are in a separate file, or we could include a helper file)
function sortLinkAjax($col, $label, $currentSort, $currentOrder)
{
    // Check if currently sorting by this column
    $isCurrent = ($currentSort === $col);

    // Determine the *next* order state if clicked
    // If active and ASC -> next is DESC
    // If active and DESC -> next is ASC
    // If not active -> default to ASC
    $nextOrder = ($isCurrent && $currentOrder === 'ASC') ? 'DESC' : 'ASC';

    // Icon Logic
    $icon = '<i class="fa fa-sort text-gray-300 ml-1"></i>';
    if ($isCurrent) {
        $icon = ($currentOrder === 'ASC')
            ? '<i class="fa fa-sort-up ml-1 text-dark"></i>'
            : '<i class="fa fa-sort-down ml-1 text-dark"></i>';
    }

    // We return a span with data-attributes for the JS to handle
    return "<a href='#' class='text-decoration-none text-dark font-weight-bold sort-link' data-sort='$col' data-order='$nextOrder'>$label $icon</a>";
}

$currentAdminEmail = $_SESSION['user_email'] ?? '';
$isSuperAdmin = ($currentAdminEmail === 'admin@gmail.com');

// Get filters and pagination parameters
$search = trim($_GET['search'] ?? '');
$userType = $_GET['user_type'] ?? '';
$active = $_GET['active'] ?? ''; // '1', '0', or ''
$sort = $_GET['sort'] ?? 'user_id';
$order = strtoupper($_GET['order'] ?? '') === 'DESC' ? 'DESC' : 'ASC';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$where = [];
$params = [];

if ($search) {
    $where[] = "(first_name LIKE :search OR middle_name LIKE :search OR last_name LIKE :search OR email LIKE :search OR CONCAT(first_name, ' ', last_name) LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($userType) {
    $where[] = "user_type = :user_type";
    $params[':user_type'] = $userType;
}

if ($active !== '' && ($active === '1' || $active === '0')) {
    $where[] = "is_active = :is_active";
    $params[':is_active'] = $active;
}

// Date filters if needed (omitted for brevity if not requested, but good to have)
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
if ($dateFrom) {
    $where[] = "DATE(created_at) >= :date_from";
    $params[':date_from'] = $dateFrom;
}
if ($dateTo) {
    $where[] = "DATE(created_at) <= :date_to";
    $params[':date_to'] = $dateTo;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Validate and sanitize sort column
$allowedSort = ['user_id', 'first_name', 'email', 'user_type', 'is_active', 'created_at', 'coins'];
$sort = in_array($sort, $allowedSort) ? $sort : 'user_id';

// Get total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $whereClause");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

// Get users for current page
$sql = "SELECT * FROM users $whereClause ORDER BY $sort $order LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
// Render Table Body
foreach ($users as $user):
    $isTargetAdmin = ($user['user_type'] === 'admin');
    $isSuperAdminUser = ($user['email'] === 'admin@gmail.com');
    // Can update if: I am Super Admin OR (Target is NOT Admin AND NOT Super Admin)
    $canEditDelete = $isSuperAdmin || (!$isTargetAdmin && !$isSuperAdminUser);

    // JSON safe for onclick
    $userJson = htmlspecialchars(json_encode($user), ENT_QUOTES, 'UTF-8');
    ?>
    <tr>
        <td class="pl-4 font-weight-bold">#<?= htmlspecialchars($user['user_id']) ?></td>
        <td>
            <div class="font-weight-bold">
                <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
            </div>
            <div class="small text-gray-500"><?= htmlspecialchars($user['middle_name']) ?></div>
        </td>
        <td><?= htmlspecialchars($user['email']) ?></td>
        <td><?= htmlspecialchars($user['phone']) ?></td>
        <td><i class="fas fa-coins text-warning"></i> <?= number_format($user['coins'] ?? 0, 2) ?></td>
        <td>
            <?php if ($isSuperAdminUser): ?>
                <span class="badge badge-dark shadow-sm px-2 py-1">👑 Super Admin</span>
            <?php else: ?>
                <?php
                $badgeClass = 'secondary';
                $userIcon = 'user';
                switch ($user['user_type']) {
                    case 'admin':
                        $badgeClass = 'danger';
                        $userIcon = 'shield';
                        break;
                    case 'staff':
                        $badgeClass = 'warning';
                        $userIcon = 'id-badge';
                        break;
                    case 'client':
                        $badgeClass = 'info';
                        $userIcon = 'user';
                        break;
                }
                ?>
                <span class="badge badge-<?= $badgeClass ?> px-2 py-1">
                    <i class="fa fa-<?= $userIcon ?>"></i>
                    <?= ucfirst(htmlspecialchars($user['user_type'])) ?>
                </span>
            <?php endif; ?>
        </td>
        <td>
            <?php if ($user['is_active'] == 1): ?>
                <span class="badge badge-success">Active</span>
            <?php else: ?>
                <span class="badge badge-secondary">Inactive</span>
            <?php endif; ?>
        </td>
        <td class="text-right pr-4">
            <?php if ($canEditDelete): ?>
                <button class="btn btn-sm btn-outline-info shadow-sm" onclick="viewUser(<?= $userJson ?>)" title="View Details">
                    <i class="fa fa-eye"></i>
                </button>
                <?php if ($user['user_type'] === 'client'): ?>
                    <button class="btn btn-sm btn-outline-warning shadow-sm" onclick="manageCoins(<?= $userJson ?>)"
                        title="Manage Coins">
                        <i class="fas fa-coins"></i>
                    </button>
                <?php endif; ?>
                <button class="btn btn-sm btn-outline-primary shadow-sm ml-1" onclick="editUser(<?= $userJson ?>)"
                    title="Edit User">
                    <i class="fa fa-pen"></i>
                </button>
                <?php if (!$isSuperAdminUser): ?>
                    <button class="btn btn-sm btn-outline-danger shadow-sm ml-1"
                        onclick="deleteUser(<?= $user['user_id'] ?>, '<?= $user['user_type'] ?>', '<?= $user['email'] ?>')"
                        title="Delete User">
                        <i class="fa fa-trash"></i>
                    </button>
                <?php endif; ?>
            <?php else: ?>
                <span class="text-muted small"><i class="fa fa-lock"></i> Locked</span>
            <?php endif; ?>
        </td>
    </tr>
<?php endforeach;
$tableHtml = ob_get_clean();

// Render Pagination
ob_start();
if ($totalPages > 1): ?>
    <ul class="pagination pagination-sm justify-content-center mb-0">
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

// Render Header with Sort Arrows
// To properly update sorting arrows, we might want to return the header row as well, 
// OR simpler: we return the sort state and let JS update classes. 
// However, replacing the <thead> is cleaner if we want to ensure everything syncs.
// But usually we just update the body. 
// Let's rely on the JS to re-render the sort icons if we can, or just return headers.

// Actually, returning headers is safer.
ob_start();
?>
<tr>
    <th class="pl-4"><?= sortLinkAjax('user_id', 'ID', $sort, $order) ?></th>
    <th><?= sortLinkAjax('first_name', 'Name', $sort, $order) ?></th>
    <th><?= sortLinkAjax('email', 'Email', $sort, $order) ?></th>
    <th>Phone</th>
    <th>Coins</th>
    <th><?= sortLinkAjax('user_type', 'Role', $sort, $order) ?></th>
    <th><?= sortLinkAjax('is_active', 'Status', $sort, $order) ?></th>
    <th class="text-right pr-4">Actions</th>
</tr>
<?php
$headerHtml = ob_get_clean();


echo json_encode([
    'success' => true,
    'table_html' => $tableHtml,
    'header_html' => $headerHtml,
    'pagination_html' => $paginationHtml,
    'total_pages' => $totalPages,
    'current_page' => $page,
    'total_items' => $total
]);
