<?php
// admin/ajax/search_parking_html.php
require_once '../../includes/db.php';

// Get filters
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$type = $_GET['type'] ?? '';
$sort = $_GET['sort'] ?? 'slot_number';
$order = $_GET['order'] ?? 'ASC';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$where = [];
$params = [];

if ($search) {
    $where[] = "(ps.slot_number LIKE :search)";
    $params[':search'] = "%$search%";
}
if ($status && in_array($status, ['available', 'reserved', 'occupied', 'unavailable'])) {
    $where[] = 'ps.slot_status = :status';
    $params[':status'] = $status;
}
if ($type) {
    $where[] = 'ps.slot_type = :type';
    $params[':type'] = $type;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Validation for sort
$allowedSort = ['slot_number', 'slot_type', 'slot_status'];
$sort = in_array($sort, $allowedSort) ? $sort : 'slot_number';
$order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

// Get total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM parking_slots ps $whereClause");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

// Get slots
$sql = "
    SELECT 
        ps.*,
        (SELECT COUNT(*) FROM reservations r2 
         WHERE r2.parking_slot_id = ps.parking_slot_id 
         AND r2.status IN ('pending', 'confirmed', 'ongoing') 
         AND (r2.status = 'ongoing' OR r2.end_time > NOW())
        ) as active_bookings,
        r.reservation_id,
        r.status as res_status,
        v.plate_number,
        CONCAT(u.first_name, ' ', u.last_name) as owner_name,
        r.start_time,
        r.end_time
    FROM parking_slots ps
    LEFT JOIN reservations r ON ps.parking_slot_id = r.parking_slot_id 
        AND r.status IN ('confirmed', 'ongoing')
        AND (
            r.status = 'ongoing' 
            OR 
            (r.start_time <= NOW() AND r.end_time >= NOW())
        )
        LEFT JOIN vehicles v ON r.vehicle_id = v.vehicle_id
    LEFT JOIN users u ON r.user_id = u.user_id
    $whereClause 
    ORDER BY ps.$sort $order 
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Output JSON with HTML segments
ob_start();
// Render Grid
foreach ($slots as $slot):
    $statusClass = 'slot-available';
    $icon = 'fa-check';

    if ($slot['slot_status'] === 'occupied') {
        $statusClass = 'slot-occupied';
        $icon = 'fa-car';
    } elseif ($slot['slot_status'] === 'reserved') {
        $statusClass = 'slot-reserved';
        $icon = 'fa-clock-o';
    } elseif ($slot['slot_status'] === 'unavailable') {
        $statusClass = 'slot-unavailable';
        $icon = 'fa-ban';
    }
    // Note: We escape JSON in onclick to handle quotes safely
    $slotJson = htmlspecialchars(json_encode($slot), ENT_QUOTES, 'UTF-8');
    ?>
    <div class="parking-slot-box <?= $statusClass ?>" onclick="editSlot(<?= $slotJson ?>)">
        <div class="slot-number"><?= htmlspecialchars($slot['slot_number']) ?></div>
        <div class="slot-icon"><i class="fa <?= $icon ?>"></i></div>
        <div class="slot-type">
            <?php if ($slot['slot_type'] === 'two_wheeler'): ?>
                <i class="fa fa-motorcycle"></i> Moto
            <?php elseif ($slot['slot_type'] === 'standard'): ?>
                <i class="fa fa-car"></i> Car
            <?php else: ?>
                <?= ucfirst(htmlspecialchars($slot['slot_type'])) ?>
            <?php endif; ?>
        </div>
        <?php if ($slot['plate_number']): ?>
            <div class="occupant-info">
                <small>Plate:</small>
                <strong><?= htmlspecialchars($slot['plate_number']) ?></strong>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach;
$gridHtml = ob_get_clean();


ob_start();
// Render Pagination
if ($totalPages > 1): ?>
    <nav aria-label="Page navigation" class="mt-4 mb-5">
        <ul class="pagination pagination-sm justify-content-center">
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
        <!-- Jump to Page for AJAX handled via JS separately or we can exclude it here -->
    </nav>
<?php endif;
$paginationHtml = ob_get_clean();

echo json_encode([
    'success' => true,
    'grid_html' => $gridHtml,
    'pagination_html' => $paginationHtml,
    'total_pages' => $totalPages,
    'current_page' => $page
]);
