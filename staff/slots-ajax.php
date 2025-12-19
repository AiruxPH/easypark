<?php
require_once __DIR__ . '/section-common.php';
header('Content-Type: application/json');

// Ensure helper functions are available for AJAX context
if (!function_exists('getSlotColorClass')) {
  function getSlotColorClass($status)
  {
    switch (strtolower($status)) {
      case 'available':
        return 'border-success';
      case 'reserved':
        return 'border-warning';
      case 'occupied':
        return 'border-danger';
      case 'unavailable':
        return 'border-secondary';
      default:
        return 'border-secondary';
    }
  }
}
if (!function_exists('getPaginationRange')) {
  function getPaginationRange($current, $total, $max = 5)
  {
    $start = max(1, $current - floor($max / 2));
    $end = min($total, $start + $max - 1);
    if ($end - $start + 1 < $max) {
      $start = max(1, $end - $max + 1);
    }
    return [$start, $end];
  }
}

// Get parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'slot_number';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 6;
$offset = ($page - 1) * $per_page;

$where = [];
$params = [];

// Use known columns for search
$searchableCols = ['slot_number', 'slot_type', 'slot_status'];

// Build search condition dynamically
if ($search !== '' && $searchableCols) {
  $searchConds = [];
  foreach ($searchableCols as $col) {
    if ($col == 'slot_number' || $col == 'slot_type' || $col == 'slot_status') {
      $searchConds[] = "parking_slots.$col LIKE :search";
    } else {
      $searchConds[] = "$col LIKE :search";
    }
  }
  $where[] = '(' . implode(' OR ', $searchConds) . ')';
  $params[':search'] = "%$search%";
}
if ($type !== '') {
  $where[] = "parking_slots.slot_type = :type";
  $params[':type'] = $type;
}
if ($status !== '') {
  $where[] = "parking_slots.slot_status = :status";
  $params[':status'] = $status;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countSql = "SELECT COUNT(*) FROM parking_slots $whereSql";
$countStmt = $pdo->prepare($countSql);
foreach ($params as $k => $v) {
  $countStmt->bindValue($k, $v);
}
$countStmt->execute();
$total = $countStmt->fetchColumn();
$total_pages = ceil($total / $per_page);

$allowedSort = ['slot_number', 'slot_type', 'slot_status'];
$sortCol = in_array($sort, $allowedSort) ? $sort : 'slot_number';

// UPDATED QUERY: include booking info
$sql = "SELECT 
          parking_slots.*,
          v.plate_number,
          CONCAT(u.first_name, ' ', u.last_name) as owner_name,
          r.start_time,
          r.end_time
        FROM parking_slots 
        LEFT JOIN reservations r ON parking_slots.parking_slot_id = r.parking_slot_id 
            AND r.status IN ('confirmed', 'ongoing') 
            AND (r.status = 'ongoing' OR (r.start_time <= NOW() AND r.end_time >= NOW()))
        LEFT JOIN vehicles v ON r.vehicle_id = v.vehicle_id 
        LEFT JOIN users u ON r.user_id = u.user_id
        $whereSql 
        ORDER BY parking_slots.$sortCol ASC 
        LIMIT :offset, :per_page";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
  $stmt->bindValue($k, $v);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
$stmt->execute();
$slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
foreach ($slots as $slot): ?>
  <div class="col-md-4 mb-3 slot-card">
    <div class="card bg-dark text-light <?= getSlotColorClass($slot['slot_status']) ?>"
      style="border-width:3px; cursor: pointer;" onclick="viewSlotDetails(this)"
      data-slot_number="<?= htmlspecialchars($slot['slot_number']) ?>"
      data-slot_status="<?= htmlspecialchars($slot['slot_status']) ?>"
      data-slot_type="<?= htmlspecialchars($slot['slot_type']) ?>"
      data-owner="<?= htmlspecialchars($slot['owner_name'] ?? '') ?>"
      data-plate="<?= htmlspecialchars($slot['plate_number'] ?? '') ?>"
      data-start="<?= htmlspecialchars($slot['start_time'] ?? '') ?>"
      data-end="<?= htmlspecialchars($slot['end_time'] ?? '') ?>">
      <div class="card-body">
        <h5 class="card-title">Slot <?= htmlspecialchars($slot['slot_number']) ?></h5>
        <p class="card-text">Type: <?= htmlspecialchars($slot['slot_type']) ?></p>
        <?php
        $statusLabel = ucfirst($slot['slot_status']);
        $statusClass = 'text-success';
        if ($slot['slot_status'] == 'occupied')
          $statusClass = 'text-danger';
        if ($slot['slot_status'] == 'reserved')
          $statusClass = 'text-warning';
        if ($slot['slot_status'] == 'unavailable') {
          $statusClass = 'text-secondary';
          $statusLabel = 'Maintenance';
        }
        ?>
        <p class="card-text">Status: <span
            class="font-weight-bold <?= $statusClass ?>"><?= htmlspecialchars($statusLabel) ?></span></p>


      </div>
    </div>
  </div>
<?php endforeach;
$cardsHtml = ob_get_clean();

if (empty($slots)) {
  $cardsHtml = '<div class="col-12"><div class="alert alert-info text-center">No parking slots found.</div></div>';
}

// Pagination
ob_start();
if ($total_pages > 1) {
  list($slots_start, $slots_end) = getPaginationRange($page, $total_pages);
  ?>
  <nav aria-label="Parking Slots pagination">
    <ul class="pagination justify-content-center">
      <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>">
        <a class="page-link" href="#" data-page="<?= $page - 1 ?>" tabindex="-1">Previous</a>
      </li>
      <?php if ($slots_start > 1): ?>
        <li class="page-item disabled"><span class="page-link">...</span></li>
      <?php endif; ?>
      <?php for ($i = $slots_start; $i <= $slots_end; $i++): ?>
        <li class="page-item<?= $i == $page ? ' active' : '' ?>">
          <a class="page-link" href="#" data-page="<?= $i ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
      <?php if ($slots_end < $total_pages): ?>
        <li class="page-item disabled"><span class="page-link">...</span></li>
      <?php endif; ?>
      <li class="page-item<?= $page >= $total_pages ? ' disabled' : '' ?>">
        <a class="page-link" href="#" data-page="<?= $page + 1 ?>">Next</a>
      </li>
    </ul>
  </nav>
  <?php
}
$paginationHtml = ob_get_clean();

echo json_encode([
  'cards' => $cardsHtml,
  'pagination' => $paginationHtml,
  'total' => $total,
  'debug' => [
    'sql' => $sql,
    'params' => $params,
    'slots' => $slots,
  ]
]);
