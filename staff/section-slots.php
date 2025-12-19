<?php
require_once __DIR__ . '/section-common.php';
// Parking Slots Overview Section (for include or AJAX)

// Fetch dynamic slot types for filter
$typesStmt = $pdo->query("SELECT DISTINCT slot_type FROM parking_slots ORDER BY slot_type");
$availableTypes = $typesStmt->fetchAll(PDO::FETCH_COLUMN);
?>
<div class="glass-card">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="text-warning mb-0"><i class="fas fa-warehouse mr-2"></i> Manage Parking Slots</h4>
  </div>

  <div class="row mb-4">
    <div class="col-md-4 mb-2">
      <div class="input-group">
        <div class="input-group-prepend">
          <span class="input-group-text bg-transparent border-secondary text-white-50"><i
              class="fas fa-search"></i></span>
        </div>
        <input type="text" id="slotSearch" class="form-control glass-input border-left-0" placeholder="Search slots..."
          value="<?= htmlspecialchars($search) ?>">
      </div>
    </div>
    <div class="col-md-3 mb-2">
      <select id="slotTypeFilter" class="form-control glass-input">
        <option value="">All Types</option>
        <?php foreach ($availableTypes as $t): ?>
          <option value="<?= htmlspecialchars($t) ?>" <?= $filter_type === $t ? 'selected' : '' ?>>
            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $t))) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3 mb-2">
      <select id="slotStatusFilter" class="form-control glass-input">
        <option value="">All Statuses</option>
        <option value="available" <?= $filter_status === 'available' ? 'selected' : '' ?>>Available</option>
        <option value="reserved" <?= $filter_status === 'reserved' ? 'selected' : '' ?>>Reserved</option>
        <option value="occupied" <?= $filter_status === 'occupied' ? 'selected' : '' ?>>Occupied</option>
        <option value="unavailable" <?= $filter_status === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
      </select>
    </div>
    <div class="col-md-2 mb-2">
      <select id="slotSort" class="form-control glass-input">
        <option value="slot_number">Sort: Slot #</option>
        <option value="slot_type">Sort: Type</option>
        <option value="slot_status">Sort: Status</option>
      </select>
    </div>
  </div>

  <div class="row" id="slotsGrid">
    <?php if (count($all_slots) === 0): ?>
      <div class="col-12">
        <div class="text-center text-white-50 py-5">No parking slots found matching your criteria.</div>
      </div>
    <?php else:
      foreach ($all_slots as $slot):
        // Determine Color Logic
        $statusColor = 'success'; // Green for available
        if ($slot['slot_status'] == 'occupied')
          $statusColor = 'danger';
        if ($slot['slot_status'] == 'reserved')
          $statusColor = 'warning';
        if ($slot['slot_status'] == 'unavailable')
          $statusColor = 'secondary';

        $iconClass = ($slot['slot_type'] === 'two_wheeler') ? 'fa-motorcycle' : 'fa-car';
        ?>
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-4">
          <div class="glass-card mb-0 h-100 p-3 text-center position-relative overflow-hidden slot-card-hover"
            style="border-top: 5px solid var(--<?= $statusColor == 'secondary' ? 'white' : $statusColor ?>); padding: 1.5rem !important;">
            <div class="d-flex flex-column h-100 justify-content-between">
              <div>
                <h5 class="font-weight-bold text-white mb-2"><?= htmlspecialchars($slot['slot_number']) ?></h5>
                <i class="fa <?= $iconClass ?> fa-2x text-white-50 mb-2"></i>
                <div class="small text-uppercase font-weight-bold text-muted mb-3"
                  style="font-size: 10px; letter-spacing: 1px;">
                  <?= htmlspecialchars(str_replace('_', ' ', $slot['slot_type'])) ?>
                </div>
              </div>
              <span class="badge badge-glass-<?= $statusColor ?> w-100 py-2">
                <?= htmlspecialchars(ucfirst($slot['slot_status'] === 'unavailable' ? 'Maintenance' : $slot['slot_status'])) ?>
              </span>

              <?php if ($slot['owner_name'] && ($slot['slot_status'] === 'occupied' || $slot['slot_status'] === 'reserved')): ?>
                <hr class="border-secondary my-2">
                <div class="small">
                  <div class="text-white-50">Occupant:</div>
                  <div class="font-weight-bold"><?= htmlspecialchars($slot['owner_name']) ?></div>
                  <?php if ($slot['plate_number']): ?>
                    <div class="text-white-50 mt-1">Vehicle:</div>
                    <div class="font-weight-bold text-warning"><?= htmlspecialchars($slot['plate_number']) ?></div>
                  <?php endif; ?>
                  <?php if ($slot['start_time']): ?>
                    <div class="text-muted mt-1" style="font-size: 0.8rem;">
                      Waiting until: <?= date('M d, H:i', strtotime($slot['end_time'])) ?>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; endif; ?>
  </div>

  <!-- Pagination -->
  <?php if ($slots_total_pages > 1): ?>
    <nav aria-label="Parking Slots pagination" class="mt-4">
      <ul class="pagination justify-content-center">
        <li class="page-item <?= ($slots_page <= 1) ? 'disabled' : '' ?>">
          <a class="page-link" href="slots_page=<?= $slots_page - 1 ?>" data-page="<?= $slots_page - 1 ?>"
            tabindex="-1">Previous</a>
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

<style>
  .slot-card-hover {
    transition: transform 0.2s;
  }

  .slot-card-hover:hover {
    transform: translateY(-5px);
    background: rgba(255, 255, 255, 0.15);
  }
</style>

<script>
  $(document).ready(function () {
    window.filterSlots = function () {
      var search = $('#slotSearch').val();
      var type = $('#slotTypeFilter').val();
      var status = $('#slotStatusFilter').val();
      var sort = $('#slotSort').val();

      var params = {};
      if (search) params.search = search;
      if (type) params.type = type;
      if (status) params.status = status;
      if (sort) params.sort = sort;

      loadSection('slots', params);
    };

    var timeout = null;
    $('#slotSearch').on('input', function () {
      clearTimeout(timeout);
      timeout = setTimeout(filterSlots, 500);
    });
    $('#slotTypeFilter, #slotStatusFilter, #slotSort').on('change', filterSlots);
  });
</script>