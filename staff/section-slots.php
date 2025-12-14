<?php
require_once __DIR__ . '/section-common.php';
// Parking Slots Overview Section (for include or AJAX)

// Fetch dynamic slot types for filter
$typesStmt = $pdo->query("SELECT DISTINCT slot_type FROM parking_slots ORDER BY slot_type");
$availableTypes = $typesStmt->fetchAll(PDO::FETCH_COLUMN);
?>
<div class="section-card">
  <h4 class="mb-3 text-warning"><i class="fa fa-car"></i> Parking Slots Overview</h4>
  <div class="row mb-3">
    <div class="col-md-4 mb-2">
      <input type="text" id="slotSearch" class="form-control" placeholder="Search slots..."
        value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="col-md-3 mb-2">
      <select id="slotTypeFilter" class="form-control">
        <option value="">All Types</option>
        <?php foreach ($availableTypes as $t): ?>
          <option value="<?= htmlspecialchars($t) ?>" <?= $filter_type === $t ? 'selected' : '' ?>>
            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $t))) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3 mb-2">
      <select id="slotStatusFilter" class="form-control">
        <option value="">All Statuses</option>
        <option value="available" <?= $filter_status === 'available' ? 'selected' : '' ?>>Available</option>
        <option value="reserved" <?= $filter_status === 'reserved' ? 'selected' : '' ?>>Reserved</option>
        <option value="occupied" <?= $filter_status === 'occupied' ? 'selected' : '' ?>>Occupied</option>
        <option value="unavailable" <?= $filter_status === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
      </select>
    </div>
    <div class="col-md-2 mb-2">
      <select id="slotSort" class="form-control">
        <option value="slot_number">Sort: Slot #</option>
        <option value="slot_type">Sort: Type</option>
        <option value="slot_status">Sort: Status</option>
      </select>
    </div>
  </div>
  <div class="row" id="slotsGrid">
    <?php if (count($all_slots) === 0): ?>
      <div class="col-12"><div class="alert alert-info text-center">No parking slots found matching your criteria.</div></div>
    <?php else: foreach ($all_slots as $slot): 
        $statusClass = getSlotColorClass($slot['slot_status']);
        $iconClass = ($slot['slot_type'] === 'two_wheeler') ? 'fa-motorcycle' : 'fa-car';
    ?>
      <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-4">
        <div class="card slot-card h-100 shadow-sm <?= $statusClass ?>" style="border-width: 2px;">
          <div class="card-body text-center p-3 d-flex flex-column justify-content-between">
            <h5 class="card-title font-weight-bold mb-2"><?= htmlspecialchars($slot['slot_number']) ?></h5>
            <div class="mb-2">
              <i class="fa <?= $iconClass ?> fa-2x text-muted mb-2"></i>
              <div class="small text-uppercase font-weight-bold text-muted" style="letter-spacing: 1px;">
                <?= htmlspecialchars(str_replace('_', ' ', $slot['slot_type'])) ?>
              </div>
            </div>
            <span class="<?= getBadgeClass($slot['slot_status']) ?> w-100 py-2" style="font-size: 0.9rem;">
                <?= htmlspecialchars(ucfirst($slot['slot_status'] === 'unavailable' ? 'Maintenance' : $slot['slot_status'])) ?>
            </span>
          </div>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <!-- Pagination -->
  <?php if ($slots_total_pages > 1): ?>
  <nav aria-label="Parking Slots pagination">
    <ul class="pagination justify-content-center">
      <li class="page-item <?= ($slots_page <= 1) ? 'disabled' : '' ?>">
        <a class="page-link" href="slots_page=<?= $slots_page - 1 ?>" data-page="<?= $slots_page - 1 ?>" tabindex="-1">Previous</a>
      </li>
      <?php 
      list($pStart, $pEnd) = getPaginationRange($slots_page, $slots_total_pages);
      for ($p = $pStart; $p <= $pEnd; $p++): 
      ?>
      <li class="page-item <?= ($slots_page == $p) ? 'active' : '' ?>">
        <a class="page-link" href="slots_page=<?= $p ?>" data-page="<?= $p ?>"><?= $p ?></a>
      </li>
      <?php endfor; ?>
      <li class="page-item <?= ($slots_page >= $slots_total_pages) ? 'disabled' : '' ?>">
        <a class="page-link" href="slots_page=<?= $slots_page + 1 ?>" data-page="<?= $slots_page + 1 ?>">Next</a>
      </li>
    </ul>
  </nav>
  <?php endif; ?>
</div>

<script>
$(document).ready(function() {
  window.filterSlots = function() {
    var search = $('#slotSearch').val();
    var type = $('#slotTypeFilter').val();
    var status = $('#slotStatusFilter').val();
    var sort = $('#slotSort').val();
    
    var params = {};
    if(search) params.search = search;
    if(type) params.type = type;
    if(status) params.status = status;
    if(sort) params.sort = sort;
    
    loadSection('slots', params);
  };

  var timeout = null;
  $('#slotSearch').on('input', function() {
    clearTimeout(timeout);
    timeout = setTimeout(filterSlots, 500);
  });
  $('#slotTypeFilter, #slotStatusFilter, #slotSort').on('change', filterSlots);
});
</script>