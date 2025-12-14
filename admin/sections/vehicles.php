<?php
// vehicles.php - Vehicles section for admin panel

// Ensure $pdo is available
if (!isset($pdo)) {
    global $pdo;
}
if (!isset($pdo)) {
    echo '<div class="alert alert-danger">Database connection not available.</div>';
}

// Handle search, filter, and sort parameters
$search = trim($_GET['search'] ?? '');
$filterType = trim($_GET['type'] ?? '');
$filterBrand = trim($_GET['brand'] ?? '');
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$sort = $_GET['sort'] ?? 'v.vehicle_id';
$order = strtolower($_GET['order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

// Allowed sort columns for security
$allowedSort = [
    'v.vehicle_id',
    'v.plate_number',
    'm.type',
    'm.brand',
    'm.model',
    'v.color',
    'u.first_name', // sort by owner
    'v.created_at'
];
if (!in_array($sort, $allowedSort)) {
    $sort = 'v.vehicle_id';
}

// Build WHERE clause
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

// --- Export CSV Handler ---
if (isset($_GET['export']) && $_GET['export'] === 'true') {
    $exportSql = "SELECT v.vehicle_id, v.plate_number, m.type, m.brand, m.model, v.color, CONCAT(u.first_name, ' ', u.last_name) as owner_name, v.created_at 
            FROM vehicles v
            LEFT JOIN users u ON v.user_id = u.user_id
            LEFT JOIN Vehicle_Models m ON v.model_id = m.model_id
            $whereSql
            ORDER BY $sort $order";

    $stmt = $pdo->prepare($exportSql);
    foreach ($params as $k => $v)
        $stmt->bindValue($k, $v);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Set headers
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="vehicles_export_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Plate Number', 'Type', 'Brand', 'Model', 'Color', 'Owner Name', 'Registered Date']);

    foreach ($rows as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

try {
    // Get total count with filters
    $countSql = "SELECT COUNT(*) FROM vehicles v
        LEFT JOIN users u ON v.user_id = u.user_id
        LEFT JOIN Vehicle_Models m ON v.model_id = m.model_id
        $whereSql";
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $k => $v)
        $countStmt->bindValue($k, $v);
    $countStmt->execute();
    $total = $countStmt->fetchColumn();
    $totalPages = $total ? ceil($total / $perPage) : 1;

    // Fetch vehicles with user info and model info, filters, sort
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

    // For filter dropdowns
    $types = $pdo->query("SELECT DISTINCT type FROM Vehicle_Models ORDER BY type")->fetchAll(PDO::FETCH_COLUMN);
    $brands = $pdo->query("SELECT DISTINCT brand FROM Vehicle_Models ORDER BY brand")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $result = [];
    $totalPages = 1;
    $types = [];
    $brands = [];
}

// Helper for sorting links
function sortLinkV($col, $label, $currentSort, $currentOrder, $search, $type, $brand)
{
    $newOrder = ($currentSort === $col && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    $icon = '';
    if ($currentSort === $col) {
        $icon = $currentOrder === 'ASC' ? '<i class="fa fa-sort-up ml-1"></i>' : '<i class="fa fa-sort-down ml-1"></i>';
    } else {
        $icon = '<i class="fa fa-sort text-gray-300 ml-1"></i>';
    }

    $url = "?section=vehicles&sort=$col&order=$newOrder&search=" . urlencode($search) .
        "&type=" . urlencode($type) . "&brand=" . urlencode($brand);

    return "<a href='$url' class='text-decoration-none text-dark font-weight-bold'>$label $icon</a>";
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Vehicles Registry</h2>
    </div>

    <!-- Filters -->
    <div class="card mb-4 shadow-sm border-bottom-primary">
        <div class="card-body py-3">
            <form method="GET" class="form-inline justify-content-center">
                <input type="hidden" name="section" value="vehicles">

                <div class="input-group mr-2 mb-2">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-light border-0"><i class="fa fa-search"></i></span>
                    </div>
                    <input type="text" name="search" class="form-control bg-light border-0 small"
                        placeholder="Search plate, name..." value="<?= htmlspecialchars($search) ?>">
                </div>

                <div class="input-group mr-2 mb-2">
                    <select name="type" class="custom-select custom-select-sm border-0 bg-light">
                        <option value="">All Types</option>
                        <?php foreach ($types as $type): ?>
                            <option value="<?= htmlspecialchars($type) ?>" <?= $type === $filterType ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="input-group mr-2 mb-2">
                    <select name="brand" class="custom-select custom-select-sm border-0 bg-light">
                        <option value="">All Brands</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?= htmlspecialchars($brand) ?>" <?= $brand === $filterBrand ? 'selected' : '' ?>>
                                <?= htmlspecialchars($brand) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="input-group mr-2 mb-2">
                    <div class="input-group-prepend"><span class="input-group-text border-0 small">From</span></div>
                    <input type="date" class="form-control form-control-sm border-0 bg-light" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
                </div>
                <div class="input-group mr-2 mb-2">
                    <div class="input-group-prepend"><span class="input-group-text border-0 small">To</span></div>
                    <input type="date" class="form-control form-control-sm border-0 bg-light" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
                </div>

                <button type="submit" class="btn btn-sm btn-primary shadow-sm mb-2">
                    <i class="fa fa-filter"></i> Apply
                </button>
                <button type="submit" formaction="?section=vehicles&export=true" formmethod="GET" class="btn btn-sm btn-success shadow-sm mb-2 ml-2">
                    <i class="fa fa-download"></i> Export
                </button>
                <?php if ($search || $filterType || $filterBrand || $dateFrom || $dateTo): ?>
                    <a href="?section=vehicles" class="btn btn-sm btn-light ml-2 mb-2 text-danger">
                        <i class="fa fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Vehicles Table -->
    <div class="card shadow-sm mb-4">
        <div class="card-body px-0 pt-0 pb-2">
            <div class="table-responsive">
                <table class="table table-hover align-items-center table-flush" id="dataTable" width="100%"
                    cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th class="pl-4">
                                <?= sortLinkV('v.vehicle_id', 'ID', $sort, $order, $search, $filterType, $filterBrand) ?>
                            </th>
                            <th><?= sortLinkV('v.plate_number', 'Plate Number', $sort, $order, $search, $filterType, $filterBrand) ?>
                            </th>
                            <th><?= sortLinkV('m.type', 'Type', $sort, $order, $search, $filterType, $filterBrand) ?>
                            </th>
                            <th><?= sortLinkV('m.brand', 'Brand', $sort, $order, $search, $filterType, $filterBrand) ?>
                            </th>
                            <th><?= sortLinkV('m.model', 'Model', $sort, $order, $search, $filterType, $filterBrand) ?>
                            </th>
                            <th><?= sortLinkV('v.color', 'Color', $sort, $order, $search, $filterType, $filterBrand) ?>
                            </th>
                            <th><?= sortLinkV('u.first_name', 'Owner', $sort, $order, $search, $filterType, $filterBrand) ?>
                            </th>
                            <th><?= sortLinkV('v.created_at', 'Registered', $sort, $order, $search, $filterType, $filterBrand) ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && count($result) > 0): ?>
                            <?php foreach ($result as $row): ?>
                                <tr>
                                    <td class="pl-4 font-weight-bold">#<?= htmlspecialchars($row['vehicle_id']) ?></td>
                                    <td><span class="badge badge-light border text-dark p-2"
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
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-gray-500">
                                    <i class="fa fa-folder-open fa-3x mb-3 text-gray-300"></i><br>
                                    No vehicles found matching your criteria.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4 mb-3">
                    <ul class="pagination pagination-sm justify-content-center">
                        <?php
                        // Helper to preserve search/filter/sort in links
                        function buildQueryV($overrides = [])
                        {
                            $params = $_GET;
                            foreach ($overrides as $k => $v)
                                $params[$k] = $v;
                            return '?' . http_build_query($params);
                        }
                        ?>
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= buildQueryV(['page' => 1]) ?>">First</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="<?= buildQueryV(['page' => $page - 1]) ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        <?php
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                        if ($start > 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        for ($i = $start; $i <= $end; $i++) {
                            echo '<li class="page-item' . ($i === $page ? ' active' : '') . '">';
                            echo '<a class="page-link" href="' . buildQueryV(['page' => $i]) . '">' . $i . '</a>';
                            echo '</li>';
                        }
                        if ($end < $totalPages) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        ?>
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= buildQueryV(['page' => $page + 1]) ?>">Next</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="<?= buildQueryV(['page' => $totalPages]) ?>">Last</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>