<?php
// admin/sections/reservations.php

// Use PDO for consistency
global $pdo;

// Filters and pagination
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($search) {
    $where[] = "(u.first_name LIKE :search OR u.last_name LIKE :search OR v.plate_number LIKE :search)";
    $params[':search'] = "%$search%";
}
if ($status) {
    $where[] = "r.status = :status";
    $params[':status'] = $status;
}
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$countStmt = $pdo->prepare("
    SELECT COUNT(*) FROM reservations r
    LEFT JOIN users u ON r.user_id = u.user_id
    LEFT JOIN vehicles v ON r.vehicle_id = v.vehicle_id
    $whereClause
");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

// Get reservations for current page
$sql = "
    SELECT 
        r.reservation_id,
        u.first_name, u.last_name, u.email,
        v.plate_number, v.vehicle_type,
        ps.slot_number, ps.slot_type,
        r.start_time, r.end_time, r.duration, r.status,
        r.created_at, r.updated_at
    FROM reservations r
    LEFT JOIN users u ON r.user_id = u.user_id
    LEFT JOIN vehicles v ON r.vehicle_id = v.vehicle_id
    LEFT JOIN parking_slots ps ON r.parking_slot_id = ps.parking_slot_id
    $whereClause
    ORDER BY r.created_at DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Reservations</h2>
    </div>
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row">
                <input type="hidden" name="section" value="reservations">
                <div class="col-md-4 mb-2">
                    <input type="text" name="search" class="form-control" placeholder="Search by user or plate..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3 mb-2">
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="confirmed"<?= $status === 'confirmed' ? ' selected' : '' ?>>Confirmed</option>
                        <option value="cancelled"<?= $status === 'cancelled' ? ' selected' : '' ?>>Cancelled</option>
                        <option value="pending"<?= $status === 'pending' ? ' selected' : '' ?>>Pending</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <?php if ($search || $status): ?>
                        <a href="?section=reservations" class="btn btn-secondary ml-2">Clear</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    <!-- Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Email</th>
                            <th>Vehicle</th>
                            <th>Type</th>
                            <th>Slot</th>
                            <th>Slot Type</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Duration (hrs)</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($res && count($res) > 0): ?>
                            <?php foreach($res as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['reservation_id']) ?></td>
                                    <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= htmlspecialchars($row['plate_number']) ?></td>
                                    <td><?= htmlspecialchars($row['vehicle_type']) ?></td>
                                    <td><?= htmlspecialchars($row['slot_number']) ?></td>
                                    <td><?= htmlspecialchars($row['slot_type']) ?></td>
                                    <td><?= htmlspecialchars($row['start_time']) ?></td>
                                    <td><?= htmlspecialchars($row['end_time']) ?></td>
                                    <td><?= htmlspecialchars($row['duration']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $row['status'] === 'confirmed' ? 'success' : ($row['status'] === 'cancelled' ? 'danger' : 'secondary') ?>">
                                            <?= htmlspecialchars(ucfirst($row['status'])) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                                    <td><?= htmlspecialchars($row['updated_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="13" class="text-center">No reservations found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?section=reservations&page=1&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>">First</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?section=reservations&page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>">Previous</a>
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
                        echo '<a class="page-link" href="?section=reservations&page=' . $i . '&search=' . urlencode($search) . '&status=' . urlencode($status) . '">' . $i . '</a>';
                        echo '</li>';
                    }
                    if ($end < $totalPages) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                    ?>
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?section=reservations&page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>">Next</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?section=reservations&page=<?= $totalPages ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>">Last</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>