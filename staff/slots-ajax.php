<?php
require_once __DIR__ . '/section-common.php';
header('Content-Type: application/json');

// Ensure helper functions are available for AJAX context
if (!function_exists('getSlotColorClass')) {
    function getSlotColorClass($status) {
        switch (strtolower($status)) {
            case 'available': return 'border-success';
            case 'reserved': return 'border-warning';
            case 'occupied': return 'border-danger';
            default: return 'border-secondary';
        }
    }
}
if (!function_exists('getPaginationRange')) {
    function getPaginationRange($current, $total, $max = 5) {
        $start = max(1, $current - floor($max/2));
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
        $searchConds[] = "$col LIKE :search";
    }
    $where[] = '(' . implode(' OR ', $searchConds) . ')';
    $params[':search'] = "%$search%";
}
if ($type !== '') {
    $where[] = "slot_type = :type";
    $params[':type'] = $type;
}
if ($status !== '') {
    $where[] = "slot_status = :status";
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
$sql = "SELECT parking_slot_id, slot_number, slot_type, slot_status FROM parking_slots $whereSql ORDER BY $sortCol ASC LIMIT :offset, :per_page";
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
  <div class="col-md-4 mb-3 slot-card" data-slot_number="<?= htmlspecialchars($slot['slot_number']) ?>" data-slot_type="<?= htmlspecialchars($slot['slot_type']) ?>" data-slot_status="<?= htmlspecialchars($slot['slot_status']) ?>">
    <div class="card bg-dark text-light <?= getSlotColorClass($slot['slot_status']) ?>" style="border-width:3px;">
      <div class="card-body">
        <h5 class="card-title">Slot <?= htmlspecialchars($slot['slot_number']) ?></h5>
        <p class="card-text">Type: <?= htmlspecialchars($slot['slot_type']) ?></p>
        <p class="card-text">Status: <span class="font-weight-bold text-warning"><?= htmlspecialchars(ucfirst($slot['slot_status'])) ?></span></p>
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
          <a class="page-link" href="#" data-page="<?= $page-1 ?>" tabindex="-1">Previous</a>
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
          <a class="page-link" href="#" data-page="<?= $page+1 ?>">Next</a>
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
