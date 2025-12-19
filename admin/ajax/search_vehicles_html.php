<?php
// admin/ajax/search_vehicles_html.php
require_once '../../includes/db.php';

function sortLinkAjaxV($col, $label, $currentSort, $currentOrder)
{
    $isCurrent = ($currentSort === $col);
    $nextOrder = ($isCurrent && $currentOrder === 'ASC') ? 'DESC' : 'ASC';

    $icon = '<i class="fa fa-sort text-gray-300 ml-1"></i>';
    if ($isCurrent) {
        $icon = ($currentOrder === 'ASC')
            ? '<i class="fa fa-sort-up ml-1 text-dark"></i>'
            : '<i class="fa fa-sort-down ml-1 text-dark"></i>';
    }
    return "<a href='#' class='text-decoration-none text-dark font-weight-bold sort-link' data-sort='$col' data-order='$nextOrder'>$label $icon</a>";
}

// Params
$search = trim($_GET['search'] ?? '');
$filterType = trim($_GET['type'] ?? '');
$filterBrand = trim($_GET['brand'] ?? '');
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$sort = $_GET['sort'] ?? 'v.vehicle_id';
$order = strtolower($_GET['order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Validate Sort
$allowedSort = [
    'v.vehicle_id',
    'v.plate_number',
    'm.type',
    'm.brand',
    'm.model',
    'v.color',
    'u.first_name',
    'v.created_at'
];
if (!in_array($sort, $allowedSort))
    $sort = 'v.vehicle_id';

// Build Query
$where = [];
$params = [];
if ($search !== '') {
    $where[] = "(v.plate_number LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search OR m.brand LIKE :search OR m.model LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}
if ($filterType !== '') {
    $where[] = "m.type = :type";
    $params[':type'] = $filterType;
}
if ($filterBrand !== '') {
    $where[] = "m.brand = :brand";
    $params[':brand'] = $filterBrand;
}
if ($dateFrom) {
    $where[] = "DATE(v.created_at) >= :date_from";
    $params[':date_from'] = $dateFrom;
}
if ($dateTo) {
    $where[] = "DATE(v.created_at) <= :date_to";
    $params[':date_to'] = $dateTo;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count
$countSql = "SELECT COUNT(*) FROM vehicles v
    LEFT JOIN users u ON v.user_id = u.user_id
    LEFT JOIN Vehicle_Models m ON v.model_id = m.model_id
    $whereSql";
$countStmt = $pdo->prepare($countSql);
foreach ($params as $k => $v)
    $countStmt->bindValue($k, $v);
$countStmt->execute();
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

// Fetch
$sql = "SELECT v.vehicle_id, v.plate_number, v.color, v.created_at, 
               u.first_name, u.last_name,
               m.brand, m.model, m.type
        FROM vehicles v
        LEFT JOIN users u ON v.user_id = u.user_id
        LEFT JOIN Vehicle_Models m ON v.model_id = m.model_id
        $whereSql
        ORDER BY $sort $order
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v)
    $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
// Table Body Matches
if ($result && count($result) > 0):
    foreach ($result as $row): ?>
        <tr>
            <td class="pl-4 font-weight-bold">#<?= htmlspecialchars($row['vehicle_id']) ?></td>
            <td><span class="badge border p-2"
                    style="font-family: monospace; font-size: 1rem;"><?= htmlspecialchars($row['plate_number']) ?></span>
            </td>
            <td>
                <?php if (strtolower($row['type']) == 'two_wheeler'): ?>
                    <i class="fa fa-motorcycle text-info"></i> Motorcycle
                <?php else: ?>
                    <i class="fa fa-car text-primary"></i> Standard
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($row['brand']) ?></td>
            <td><?= htmlspecialchars($row['model']) ?></td>
            <td>
                <span class="badge badge-light border">
                    <i class="fa fa-circle"
                        style="color: <?= htmlspecialchars($row['color']) ?>; text-shadow: 0 0 2px #000;"></i>
                    <?= htmlspecialchars($row['color']) ?>
                </span>
            </td>
            <td>
                <div class="font-weight-bold text-gray-700">
                    <?= htmlspecialchars(trim($row['first_name'] . ' ' . $row['last_name'])) ?>
                </div>
            </td>
            <td class="text-muted small"><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
        </tr>
    <?php endforeach;
else: ?>
    <tr>
        <td colspan="8" class="text-center py-5 text-gray-500">
            <i class="fa fa-folder-open fa-3x mb-3 text-gray-300"></i><br>
            No vehicles found matching your criteria.
        </td>
    </tr>
<?php endif;
$tableHtml = ob_get_clean();

ob_start();
// Header
?>
<tr>
    <th class="pl-4"><?= sortLinkAjaxV('v.vehicle_id', 'ID', $sort, $order) ?></th>
    <th><?= sortLinkAjaxV('v.plate_number', 'Plate Number', $sort, $order) ?></th>
    <th><?= sortLinkAjaxV('m.type', 'Type', $sort, $order) ?></th>
    <th><?= sortLinkAjaxV('m.brand', 'Brand', $sort, $order) ?></th>
    <th><?= sortLinkAjaxV('m.model', 'Model', $sort, $order) ?></th>
    <th><?= sortLinkAjaxV('v.color', 'Color', $sort, $order) ?></th>
    <th><?= sortLinkAjaxV('u.first_name', 'Owner', $sort, $order) ?></th>
    <th><?= sortLinkAjaxV('v.created_at', 'Registered', $sort, $order) ?></th>
</tr>
<?php
$headerHtml = ob_get_clean();

// Pagination
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

echo json_encode([
    'success' => true,
    'table_html' => $tableHtml,
    'header_html' => $headerHtml,
    'pagination_html' => $paginationHtml,
    'total_pages' => $totalPages,
    'current_page' => $page
]);
