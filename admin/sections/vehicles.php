<?php
// vehicles.php - Vehicles section for admin panel

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure $pdo is available
if (!isset($pdo)) {
    global $pdo;
}
if (!isset($pdo)) {
    echo '<div class="alert alert-danger">Database connection not available.</div>';
    // Do not return, allow rest of page to render for debugging
}

// Handle search, filter, and sort parameters
$search = trim($_GET['search'] ?? '');
$filterType = trim($_GET['type'] ?? '');
$filterBrand = trim($_GET['brand'] ?? '');
$sort = $_GET['sort'] ?? 'v.vehicle_id';
$order = strtolower($_GET['order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

// Allowed sort columns for security
$allowedSort = [
    'v.vehicle_id', 'v.plate_number', 'm.type', 'm.brand', 'm.model', 'v.color', 'u.first_name', 'v.created_at'
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
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

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
    foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
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
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
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
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Vehicles</h2>
    </div>
    <!-- Search, Filter, Sort Form -->
    <form class="mb-3" method="get" action="">
        <input type="hidden" name="section" value="vehicles">
        <div class="row g-2">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Search plate, owner, brand, model" value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <?php foreach ($types as $type): ?>
                        <option value="<?= htmlspecialchars($type) ?>" <?= $type === $filterType ? 'selected' : '' ?>><?= htmlspecialchars($type) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="brand" class="form-select">
                    <option value="">All Brands</option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?= htmlspecialchars($brand) ?>" <?= $brand === $filterBrand ? 'selected' : '' ?>><?= htmlspecialchars($brand) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="sort" class="form-select">
                    <option value="v.vehicle_id" <?= $sort === 'v.vehicle_id' ? 'selected' : '' ?>>Sort by ID</option>
                    <option value="v.plate_number" <?= $sort === 'v.plate_number' ? 'selected' : '' ?>>Sort by Plate</option>
                    <option value="m.type" <?= $sort === 'm.type' ? 'selected' : '' ?>>Sort by Type</option>
                    <option value="m.brand" <?= $sort === 'm.brand' ? 'selected' : '' ?>>Sort by Brand</option>
                    <option value="m.model" <?= $sort === 'm.model' ? 'selected' : '' ?>>Sort by Model</option>
                    <option value="v.color" <?= $sort === 'v.color' ? 'selected' : '' ?>>Sort by Color</option>
                    <option value="u.first_name" <?= $sort === 'u.first_name' ? 'selected' : '' ?>>Sort by Owner</option>
                    <option value="v.created_at" <?= $sort === 'v.created_at' ? 'selected' : '' ?>>Sort by Created</option>
                </select>
            </div>
            <div class="col-md-1">
                <select name="order" class="form-select">
                    <option value="desc" <?= $order === 'DESC' ? 'selected' : '' ?>>Desc</option>
                    <option value="asc" <?= $order === 'ASC' ? 'selected' : '' ?>>Asc</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" type="submit">Apply</button>
            </div>
        </div>
    </form>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped mt-3">
                <thead>
                    <tr>
                    <th>ID</th>
                    <th>Plate Number</th>
                    <th>Type</th>
                    <th>Brand</th>
                    <th>Model</th>
                    <th>Color</th>
                    <th>Owner</th>
                    <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && count($result) > 0): ?>
                    <?php foreach($result as $row): ?>
                        <tr>
                        <td><?= htmlspecialchars($row['vehicle_id']) ?></td>
                        <td><?= htmlspecialchars($row['plate_number']) ?></td>
                        <td><?= htmlspecialchars($row['type']) ?></td>
                        <td><?= htmlspecialchars($row['brand']) ?></td>
                        <td><?= htmlspecialchars($row['model']) ?></td>
                        <td><?= htmlspecialchars($row['color']) ?></td>
                        <td><?= htmlspecialchars(trim($row['first_name'] . ' ' . $row['last_name'])) ?></td>
                        <td><?= htmlspecialchars($row['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">No vehicles found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php
                    // Helper to preserve search/filter/sort in links
                    function buildQuery($overrides = []) {
                        $params = $_GET;
                        foreach ($overrides as $k => $v) $params[$k] = $v;
                        return '?' . http_build_query($params);
                    }
                    ?>
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= buildQuery(['page'=>1]) ?>">First</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="<?= buildQuery(['page'=>$page-1]) ?>">Previous</a>
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
                        echo '<a class="page-link" href="' . buildQuery(['page'=>$i]) . '">' . $i . '</a>';
                        echo '</li>';
                    }
                    if ($end < $totalPages) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                    ?>
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= buildQuery(['page'=>$page+1]) ?>">Next</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="<?= buildQuery(['page'=>$totalPages]) ?>">Last</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>