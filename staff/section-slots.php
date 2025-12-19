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
            style="border-top: 5px solid var(--<?= $statusColor == 'secondary' ? 'white' : $statusColor ?>); padding: 1.5rem !important; cursor: pointer;"
            data-slot_number="<?= htmlspecialchars($slot['slot_number']) ?>"
            data-slot_status="<?= htmlspecialchars($slot['slot_status']) ?>"
            data-slot_type="<?= htmlspecialchars($slot['slot_type']) ?>"
            data-owner="<?= htmlspecialchars($slot['owner_name'] ?? '') ?>"
            data-plate="<?= htmlspecialchars($slot['plate_number'] ?? '') ?>"
            data-start="<?= htmlspecialchars($slot['start_time'] ?? '') ?>"
            data-end="<?= htmlspecialchars($slot['end_time'] ?? '') ?>">
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
    $('#slotTypeFilter, #slotStatusFilter, #slotSort').on('change', filterSlots);

    // Event Delegation for Slot Click
    $(document).off('click', '.slot-card-hover').on('click', '.slot-card-hover', function () {
      const slot = $(this).data();

      $('#detail_slot_number').text(slot.slot_number);
      $('#detail_slot_type').text(slot.slot_type.replace('_', ' ').toUpperCase());

      // Reset
      $('#detail_occupied_info').hide();
      $('#detail_status_badge').removeClass('badge-success badge-warning badge-danger badge-secondary');

      let badgeClass = 'badge-secondary';
      if (slot.slot_status === 'available') badgeClass = 'badge-success';
      if (slot.slot_status === 'reserved') badgeClass = 'badge-warning';
      if (slot.slot_status === 'occupied') badgeClass = 'badge-danger';

      $('#detail_status_badge').addClass(badgeClass).text(slot.slot_status.toUpperCase());

      // Show info if occupied/reserved
      if ((slot.slot_status === 'occupied' || slot.slot_status === 'reserved') && slot.owner) {
        $('#detail_owner').text(slot.owner);
        $('#detail_plate').text(slot.plate || 'N/A');
        $('#detail_time').text(slot.start + ' to ' + slot.end);
        $('#detail_occupied_info').show();
      }

      $('#slotDetailModal').modal('show');
    });
  });

  /* Removed global function to prevent scope issues with .load() */
</script>

<!-- Slot Detail Modal -->
<div class="modal fade" id="slotDetailModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-card border-0">
      <div class="modal-header border-bottom-0">
        <h5 class="modal-title text-white">Slot <span id="detail_slot_number"
            class="text-warning font-weight-bold"></span></h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="text-center mb-4">
          <span id="detail_status_badge" class="badge p-2 px-3" style="font-size: 1rem;"></span>
          <div class="mt-2 text-white-50 small text-uppercase font-weight-bold" id="detail_slot_type"></div>
        </div>

        <div id="detail_occupied_info" style="display:none;" class="bg-dark p-3 rounded border border-secondary">
          <div class="row mb-2">
            <div class="col-4 text-white-50">Occupant:</div>
            <div class="col-8 font-weight-bold text-white" id="detail_owner"></div>
          </div>
          <div class="row mb-2">
            <div class="col-4 text-white-50">Vehicle:</div>
            <div class="col-8 font-weight-bold text-warning" id="detail_plate"></div>
          </div>
          <div class="row">
            <div class="col-4 text-white-50">Duration:</div>
            <div class="col-8 text-white small" id="detail_time"></div>
          </div>
        </div>
      </div>
      <div class="modal-footer border-top-0">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>