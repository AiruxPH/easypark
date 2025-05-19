<?php
// vehicles.php - Vehicles section for admin panel

global $pdo;

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get total count
$countStmt = $pdo->query("SELECT COUNT(*) FROM vehicles");
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

// Fetch vehicles with user info
$sql = "SELECT v.vehicle_id, v.plate_number, v.vehicle_type, v.brand, v.model, v.color, v.created_at, u.first_name, u.last_name
    FROM vehicles v
    LEFT JOIN users u ON v.user_id = u.user_id
    ORDER BY v.vehicle_id DESC
    LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Vehicles</h2>
    </div>
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
                        <td><?= htmlspecialchars($row['vehicle_type']) ?></td>
                        <td><?= htmlspecialchars($row['brand']) ?></td>
                        <td><?= htmlspecialchars($row['model']) ?></td>
                        <td><?= htmlspecialchars($row['color']) ?></td>
                        <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
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
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?section=vehicles&page=1">First</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?section=vehicles&page=<?= $page - 1 ?>">Previous</a>
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
                        echo '<a class="page-link" href="?section=vehicles&page=' . $i . '">' . $i . '</a>';
                        echo '</li>';
                    }
                    if ($end < $totalPages) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                    ?>
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?section=vehicles&page=<?= $page + 1 ?>">Next</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?section=vehicles&page=<?= $totalPages ?>">Last</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>