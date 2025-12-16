<?php
require_once __DIR__ . '/section-common.php';
// Active Reservations Section (for include or AJAX)
?>
<div class="glass-card">
  <h4 class="mb-4 text-success"><i class="fas fa-play-circle mr-2"></i> Active Reservations (Confirmed & Ongoing)</h4>

  <!-- Filters -->
  <div class="row mb-4">
    <div class="col-md-3 mb-2">
      <div class="input-group">
        <div class="input-group-prepend">
          <span class="input-group-text bg-transparent border-secondary text-white-50"><i
              class="fas fa-search"></i></span>
        </div>
        <input type="text" id="activeSearch" class="form-control glass-input border-left-0" placeholder="Search..."
          value="<?= htmlspecialchars($search) ?>">
      </div>
    </div>
    <div class="col-md-3 mb-2">
      <select id="activeStatusFilter" class="form-control glass-input">
        <option value="">All Statuses</option>
        <option value="confirmed" <?= $filter_status === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
        <option value="ongoing" <?= $filter_status === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
      </select>
    </div>
    <div class="col-md-6 mb-2">
      <div class="input-group">
        <div class="input-group-prepend"><span
            class="input-group-text bg-transparent border-secondary text-white-50">Filter Date</span></div>
        <input type="date" id="activeDateFrom" class="form-control glass-input"
          value="<?= htmlspecialchars($date_from) ?>">
        <div class="input-group-prepend input-group-append"><span
            class="input-group-text bg-transparent border-secondary text-white-50">to</span></div>
        <input type="date" id="activeDateTo" class="form-control glass-input" value="<?= htmlspecialchars($date_to) ?>">
      </div>
    </div>
  </div>

  <!-- Table -->
  <div class="table-responsive">
    <table id="activeTable" class="table glass-table table-hover">
      <thead>
        <tr>
          <th class="sortable">Ref #</th>
          <th class="sortable">Client</th>
          <th class="sortable">Slot</th>
          <th class="sortable">Vehicle</th>
          <th class="sortable">Start</th>
          <th class="sortable">End</th>
          <th class="sortable">Duration</th>
          <th class="sortable">Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($active_reservations) === 0): ?>
          <tr>
            <td colspan="9" class="text-center py-5 text-white-50">
              <i class="fas fa-car-side fa-3x mb-3 d-block opacity-50"></i>
              No active reservations found.
            </td>
          </tr>
        <?php else:
          foreach ($active_reservations as $b): ?>
            <tr>
              <td><span class="font-weight-bold text-white"><?= htmlspecialchars($b['reservation_id']) ?></span></td>
              <td><?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?></td>
              <td>
                <span class="badge badge-secondary"><?= htmlspecialchars($b['slot_number']) ?></span>
                <small class="text-white-50 d-block"><?= htmlspecialchars($b['slot_type']) ?></small>
              </td>
              <td>
                <?= htmlspecialchars($b['brand'] . ' ' . $b['model']) ?>
                <small class="text-white-50 d-block"><?= htmlspecialchars($b['plate_number']) ?></small>
              </td>
              <td><?= htmlspecialchars(date('M d, H:i', strtotime($b['start_time']))) ?></td>
              <td><?= htmlspecialchars(date('M d, H:i', strtotime($b['end_time']))) ?></td>
              <td><?= htmlspecialchars($b['duration']) ?></td>
              <td>
                <?php
                $badgeClass = 'badge-glass-info'; // ongoing
                if ($b['status'] == 'confirmed')
                  $badgeClass = 'badge-glass-success';
                ?>
                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(ucfirst($b['status'])) ?></span>
              </td>
              <td>
                <?php if ($b['status'] === 'confirmed'): ?>
                  <form method="post" action="action_booking.php" style="display:inline-block">
                    <input type="hidden" name="reservation_id" value="<?= $b['reservation_id'] ?>">
                    <button type="submit" name="action" value="accept" class="btn btn-primary btn-sm shadow-sm"
                      title="Mark as Ongoing (Vehicle Arrived)">Arrived</button>
                  </form>
                <?php else: ?>
                  <span class="text-white-50 small">--</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<script>
  $(document).ready(function () {
    // Pre-fill fields from URL params (handled by PHP values in inputs, but good to have JS awareness if needed)

    // Server-side filtering function
    window.filterAndSortActive = function () {
      // Debounce active? Rely on user event.
      var search = $('#activeSearch').val();
      var status = $('#activeStatusFilter').val();
      var dateFrom = $('#activeDateFrom').val();
      var dateTo = $('#activeDateTo').val();

      var params = {};
      if (search) params.search = search;
      if (status) params.status = status;
      if (dateFrom) params.date_from = dateFrom;
      if (dateTo) params.date_to = dateTo;

      // Sort? (If we want to keep sort state, we'd need to track it. For now, basic filtering.)
      // loadSection is global from staff-dashboard.php
      loadSection('active', params);
    };

    // Debounce search
    var timeout = null;
    $('#activeSearch').on('input', function () {
      clearTimeout(timeout);
      timeout = setTimeout(filterAndSortActive, 500);
    });
    $('#activeStatusFilter, #activeDateFrom, #activeDateTo').on('change', filterAndSortActive);
  });
</script>