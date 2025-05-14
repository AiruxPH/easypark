<?php
require_once '../db.php';
// Filtering by status/type
$filterStatus = isset($_GET['status']) ? $_GET['status'] : '';
$filterType = isset($_GET['type']) ? $_GET['type'] : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;
$where = [];
$params = [];
if ($filterStatus && in_array($filterStatus, ['available','reserved','occupied'])) {
  $where[] = 'slot_status = :status';
  $params[':status'] = $filterStatus;
}
if ($filterType && in_array($filterType, ['two_wheeler','standard','compact'])) {
  $where[] = 'slot_type = :type';
  $params[':type'] = $filterType;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
$countSql = "SELECT COUNT(*) FROM parking_slots $whereSql";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalSlotsFiltered = $countStmt->fetchColumn();
$totalPages = ceil($totalSlotsFiltered / $perPage);
$sql = "SELECT parking_slot_id, slot_number, slot_type, slot_status FROM parking_slots $whereSql ORDER BY slot_number ASC LIMIT :offset, :perPage";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
  $stmt->bindValue($k, $v);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
$stmt->execute();
$slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.parking-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
    padding: 1rem;
}

.parking-slot {
    border-radius: 8px;
    padding: 1.5rem;
    text-align: center;
    transition: transform 0.2s, box-shadow 0.2s;
    color: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.parking-slot:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.parking-slot.available {
    background-color: #28a745;
}

.parking-slot.reserved {
    background-color: #ffc107;
    color: #000;
}

.parking-slot.occupied {
    background-color: #dc3545;
}

.parking-slot h3 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: bold;
}

.parking-slot p {
    margin: 0.5rem 0 0;
    font-size: 0.9rem;
    opacity: 0.9;
}

.slot-type-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    background: rgba(255,255,255,0.2);
    margin-top: 0.5rem;
    font-size: 0.8rem;
    text-transform: uppercase;
}

.slot-actions {
    margin-top: 1rem;
}

.slot-actions button {
    margin: 0.2rem;
    background: rgba(255,255,255,0.9);
    border: none;
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    color: #333;
    font-size: 0.8rem;
    transition: background 0.2s;
}

.slot-actions button:hover {
    background: rgba(255,255,255,1);
}
</style>

<div class="parking-grid">
    <?php if (count($slots) > 0): ?>
        <?php foreach ($slots as $slot): ?>
            <div class="parking-slot <?= htmlspecialchars($slot['slot_status']) ?>">
                <h3>Slot <?= htmlspecialchars($slot['slot_number']) ?></h3>
                <p><?= ucfirst(htmlspecialchars($slot['slot_status'])) ?></p>
                <div class="slot-type-badge">
                    <?= ucwords(str_replace('_', ' ', htmlspecialchars($slot['slot_type']))) ?>
                </div>
                <div class="slot-actions">
                    <button class="btn btn-sm" onclick="editSlot(<?= htmlspecialchars($slot['parking_slot_id']) ?>)">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-sm" onclick="deleteSlot(<?= htmlspecialchars($slot['parking_slot_id']) ?>)">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info">No parking slots found.</div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<nav aria-label="Page navigation" class="mt-4">
  <ul class="pagination justify-content-center">
    <?php
    $window = 2; // how many pages to show on each side
    $start = max(1, $page - $window);
    $end = min($totalPages, $page + $window);
    $queryStr = ($filterStatus ? '&status=' . urlencode($filterStatus) : '') . ($filterType ? '&type=' . urlencode($filterType) : '');
    if ($page > 1) {
      echo '<li class="page-item"><a class="page-link" href="?page=1' . $queryStr . '">&laquo; First</a></li>';
      echo '<li class="page-item"><a class="page-link" href="?page=' . ($page-1) . $queryStr . '">&lsaquo; Prev</a></li>';
    }
    if ($start > 1) {
      echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
    }
    for ($i = $start; $i <= $end; $i++) {
      $active = $i == $page ? ' active' : '';
      echo '<li class="page-item' . $active . '"><a class="page-link" href="?page=' . $i . $queryStr . '">' . $i . '</a></li>';
    }
    if ($end < $totalPages) {
      echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
    }
    if ($page < $totalPages) {
      echo '<li class="page-item"><a class="page-link" href="?page=' . ($page+1) . $queryStr . '">Next &rsaquo;</a></li>';
      echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . $queryStr . '">Last &raquo;</a></li>';
    }
    ?>
  </ul>
</nav>

<script>
function editSlot(id) {
    // Add your edit slot logic here
    alert('Edit slot ' + id);
}

function deleteSlot(id) {
    if (confirm('Are you sure you want to delete this parking slot?')) {
        // Add your delete slot logic here
        alert('Delete slot ' + id);
    }
}
</script>
