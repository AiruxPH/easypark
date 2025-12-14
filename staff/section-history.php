<?php
require_once __DIR__ . '/section-common.php';
// Reservation History Section (for include or AJAX)
?>
<div class="section-card">
  <h4 class="mb-3 text-info"><i class="fa fa-history"></i> Reservation History (Completed/Cancelled)</h4>
  <div class="row mb-2">
    <div class="col-md-4 mb-2">
      <input type="text" id="historySearch" class="form-control" placeholder="Search reservation history..."
        value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="col-md-3 mb-2">
      <select id="historyStatusFilter" class="form-control">
        <option value="">All Statuses</option>
        <option value="completed" <?= $filter_status === 'completed' ? 'selected' : '' ?>>Completed</option>
        <option value="cancelled" <?= $filter_status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        <option value="expired" <?= $filter_status === 'expired' ? 'selected' : '' ?>>Expired</option>
        <option value="void" <?= $filter_status === 'void' ? 'selected' : '' ?>>Void</option>
      </select>
    </div>
    <div class="col-md-5 mb-2">
      <div class="input-group">
        <div class="input-group-prepend"><span class="input-group-text small">Date</span></div>
        <input type="date" id="historyDateFrom" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
        <input type="date" id="historyDateTo" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
      </div>
    </div>
  </div>
  <div class="table-responsive">
    <table id="historyTable" class="table table-bordered table-hover bg-white text-dark">
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
        <?php if (count($history_reservations) === 0): ?>
          <tr>
            <td colspan="8" class="text-center">No completed, cancelled, or expired reservations found.</td>
          </tr>
        <?php else:
          foreach ($history_reservations as $b): ?>
            <tr>
              <td><?= htmlspecialchars($b['reservation_id']) ?></td>
              <td><?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?></td>
              <td><?= htmlspecialchars($b['slot_number']) ?> (<?= htmlspecialchars($b['slot_type']) ?>)</td>
              <td><?= htmlspecialchars($b['brand'] . ' ' . $b['model'] . ' - ' . $b['plate_number']) ?></td>
              <td><?= htmlspecialchars($b['start_time']) ?></td>
              <td><?= htmlspecialchars($b['end_time']) ?></td>
              <td><?= htmlspecialchars($b['duration']) ?></td>
              <td><span class="<?= getBadgeClass($b['status']) ?>"><?= htmlspecialchars(ucfirst($b['status'])) ?></span>
              </td>
            </tr>
          <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<script>
  $(document).ready(function () {
    window.filterAndSortHistory = function () {
      var search = $('#historySearch').val();
      var status = $('#historyStatusFilter').val();
      var dateFrom = $('#historyDateFrom').val();
      var dateTo = $('#historyDateTo').val();

      var params = {};
      if (search) params.search = search;
      if (status) params.status = status;
      if (dateFrom) params.date_from = dateFrom;
      if (dateTo) params.date_to = dateTo;

      loadSection('history', params);
    };

    var timeout = null;
    $('#historySearch').on('input', function () {
      clearTimeout(timeout);
      timeout = setTimeout(filterAndSortHistory, 500);
    });
    $('#historyStatusFilter, #historyDateFrom, #historyDateTo').on('change', filterAndSortHistory);
  });
</script>