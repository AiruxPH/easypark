<?php
require_once __DIR__ . '/section-common.php';
// Active Reservations Section (for include or AJAX)
?>
<div class="section-card">
  <h4 class="mb-3 text-success"><i class="fa fa-play-circle"></i> Active Reservations (Confirmed & Ongoing)</h4>
  <div class="row mb-2">
    <div class="col-md-4 mb-2">
      <input type="text" id="activeSearch" class="form-control" placeholder="Search active reservations..."
        value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="col-md-3 mb-2">
      <select id="activeStatusFilter" class="form-control">
        <option value="">All Statuses</option>
        <option value="confirmed" <?= $filter_status === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
        <option value="ongoing" <?= $filter_status === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
      </select>
    </div>
    <div class="col-md-5 mb-2">
      <div class="input-group">
        <div class="input-group-prepend"><span class="input-group-text small">Date</span></div>
        <input type="date" id="activeDateFrom" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
        <input type="date" id="activeDateTo" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
      </div>
    </div>
  </div>
  <div class="table-responsive">
    <table id="activeTable" class="table table-bordered table-hover bg-white text-dark">
      <thead class="thead-dark">
        <tr>
          <th class="sortable">Ref #</th>
          <th class="sortable">Client</th>
          <th class="sortable">Slot</th>
          <th class="sortable">Vehicle</th>
          <th class="sortable">Start</th>
          <th class="sortable">End</th>
          <th class="sortable">Duration</th>
          <th class="sortable">Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($active_reservations) === 0): ?>
          <tr>
            <td colspan="8" class="text-center">No active reservations.</td>
          </tr>
        <?php else:
          foreach ($active_reservations as $b): ?>
            <tr>
              <td><?= htmlspecialchars($b['reservation_id']) ?></td>
              <td><?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?></td>
              <td><?= htmlspecialchars($b['slot_number']) ?> (<?= htmlspecialchars($b['slot_type']) ?>)</td>
              <td><?= htmlspecialchars($b['brand'] . ' ' . $b['model'] . ' - ' . $b['plate_number']) ?></td>
              <td><?= htmlspecialchars($b['start_time']) ?></td>
              <td><?= htmlspecialchars($b['end_time']) ?></td>
              <td><?= htmlspecialchars($b['duration']) ?></td>
              <td>
                <span class="<?= getBadgeClass($b['status']) ?>"><?= htmlspecialchars(ucfirst($b['status'])) ?></span>
                <?php if ($b['status'] === 'confirmed'): ?>
                  <form method="post" action="action_booking.php" style="display:inline-block">
                    <input type="hidden" name="reservation_id" value="<?= $b['reservation_id'] ?>">
                    <button type="submit" name="action" value="accept" class="btn btn-success btn-sm ml-2">Accept</button>
                  </form>
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