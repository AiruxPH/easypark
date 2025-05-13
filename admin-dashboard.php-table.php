<?php
require_once 'db.php';
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
$sql = "SELECT parking_slot_id, slot_number, slot_type, slot_status FROM parking_slots $whereSql ORDER BY parking_slot_id ASC LIMIT :offset, :perPage";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
  $stmt->bindValue($k, $v);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
$stmt->execute();
$slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="table-responsive">
  <table class="table table-bordered table-hover text-center">
    <thead class="thead-dark">
      <tr>
        <th>ID</th>
        <th>Slot Number</th>
        <th>Type</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($slots as $slot): ?>
        <?php
          $color = 'secondary';
          $label = '';
          switch ($slot['slot_status']) {
            case 'available': $color = 'success'; $label = 'Available'; break;
            case 'reserved': $color = 'warning'; $label = 'Reserved'; break;
            case 'occupied': $color = 'danger'; $label = 'Occupied'; break;
          }
        ?>
        <tr>
          <td><?= htmlspecialchars($slot['parking_slot_id']) ?></td>
          <td><?= htmlspecialchars($slot['slot_number']) ?></td>
          <td><span class="badge badge-info text-uppercase"><?= htmlspecialchars(str_replace('_',' ',$slot['slot_type'])) ?></span></td>
          <td><span class="badge badge-<?= $color ?>"><?= $label ?></span></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($slots)): ?>
        <tr><td colspan="4" class="text-muted">No slots found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<!-- Pagination -->
<nav aria-label="Page navigation">
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
