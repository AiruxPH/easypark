<?php
require_once __DIR__ . '/section-common.php';
// Reservation History Section (for include or AJAX)
?>
<div class="glass-card">
  <h4 class="mb-4 text-info"><i class="fas fa-history mr-2"></i> Reservation History</h4>

  <!-- Filters -->
  <div class="row mb-4">
    <div class="col-md-4 mb-2">
      <div class="input-group">
        <div class="input-group-prepend">
          <span class="input-group-text bg-transparent border-secondary text-white-50"><i
              class="fas fa-search"></i></span>
        </div>
        <input type="text" id="historySearch" class="form-control glass-input border-left-0"
          placeholder="Search reservation history..." value="<?= htmlspecialchars($search) ?>">
      </div>
    </div>
    <div class="col-md-3 mb-2">
      <select id="historyStatusFilter" class="form-control glass-input">
        <option value="">All Statuses</option>
        <option value="completed" <?= $filter_status === 'completed' ? 'selected' : '' ?>>Completed</option>
        <option value="cancelled" <?= $filter_status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        <option value="expired" <?= $filter_status === 'expired' ? 'selected' : '' ?>>Expired</option>
        <option value="void" <?= $filter_status === 'void' ? 'selected' : '' ?>>Void</option>
      </select>
    </div>
    <div class="col-md-5 mb-2">
      <div class="input-group">
        <div class="input-group-prepend"><span
            class="input-group-text bg-transparent border-secondary text-white-50">Filter Date</span></div>
        <input type="date" id="historyDateFrom" class="form-control glass-input"
          value="<?= htmlspecialchars($date_from) ?>">
        <div class="input-group-prepend input-group-append"><span
            class="input-group-text bg-transparent border-secondary text-white-50">to</span></div>
        <input type="date" id="historyDateTo" class="form-control glass-input"
          value="<?= htmlspecialchars($date_to) ?>">
      </div>
    </div>
  </div>

  <!-- Table -->
  <div class="table-responsive">
    <table id="historyTable" class="table glass-table table-hover">
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
        </tr>
      </thead>
      <tbody>
        <?php if (count($history_reservations) === 0): ?>
          <tr>
            <td colspan="8" class="text-center py-5 text-white-50">
              <i class="fas fa-history fa-3x mb-3 d-block opacity-50"></i>
              No history found matching your criteria.
            </td>
          </tr>
        <?php else:
          foreach ($history_reservations as $b): ?>
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
              <td>
                <?php
                $start = new DateTime($b['start_time']);
                $end = new DateTime($b['end_time']);
                $diff = $start->diff($end);
                $parts = [];
                if ($diff->d > 0)
                  $parts[] = $diff->d . 'd';
                if ($diff->h > 0)
                  $parts[] = $diff->h . 'h';
                if ($diff->i > 0)
                  $parts[] = $diff->i . 'm';
                echo htmlspecialchars(implode(' ', $parts) ?: '0m');
                ?>
              </td>
              <td>
                <?php
                $badgeClass = 'badge-secondary';
                if ($b['status'] == 'completed')
                  $badgeClass = 'badge-glass-success';
                if ($b['status'] == 'cancelled')
                  $badgeClass = 'badge-glass-danger';
                if ($b['status'] == 'expired')
                  $badgeClass = 'badge-glass-warning';
                ?>
                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(ucfirst($b['status'])) ?></span>
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