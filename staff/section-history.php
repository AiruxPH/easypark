<?php
require_once __DIR__ . '/section-common.php';
// Reservation History Section (for include or AJAX)
?>
<div class="section-card">
  <h4 class="mb-3 text-info"><i class="fa fa-history"></i> Reservation History (Completed/Cancelled)</h4>
  <div class="row mb-2">
    <div class="col-md-4 mb-2">
      <input type="text" id="historySearch" class="form-control" placeholder="Search reservation history...">
    </div>
    <div class="col-md-3 mb-2">
      <select id="historyStatusFilter" class="form-control">
        <option value="">All Statuses</option>
        <option value="completed">Completed</option>
        <option value="cancelled">Cancelled</option>
      </select>
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
          <tr><td colspan="8" class="text-center">No completed or cancelled reservations found.</td></tr>
        <?php else: foreach ($history_reservations as $b): ?>
          <tr>
            <td><?= htmlspecialchars($b['reservation_id']) ?></td>
            <td><?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?></td>
            <td><?= htmlspecialchars($b['slot_number']) ?> (<?= htmlspecialchars($b['slot_type']) ?>)</td>
            <td><?= htmlspecialchars($b['brand'].' '.$b['model'].' - '.$b['plate_number']) ?></td>
            <td><?= htmlspecialchars($b['start_time']) ?></td>
            <td><?= htmlspecialchars($b['end_time']) ?></td>
            <td><?= htmlspecialchars($b['duration']) ?></td>
            <td><?= htmlspecialchars(ucfirst($b['status'])) ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<script>
$(document).ready(function() {
  function filterAndSortHistory() {
    var search = $('#historySearch').val().toLowerCase();
    var status = $('#historyStatusFilter').val();
    var rows = $('#historyTable tbody tr').get();
    rows.forEach(function(row) {
      var tds = $(row).children('td');
      var rowStatus = tds.eq(7).text().toLowerCase();
      var rowText = $(row).text().toLowerCase();
      var show = true;
      if (search && rowText.indexOf(search) === -1) show = false;
      if (status && rowStatus !== status) show = false;
      $(row).toggle(show);
    });
    // Sorting
    if (window.historySortCol !== undefined) {
      rows = rows.filter(function(row) { return $(row).css('display') !== 'none'; });
      rows.sort(function(a, b) {
        var tdA = $(a).children('td').eq(window.historySortCol).text().toLowerCase();
        var tdB = $(b).children('td').eq(window.historySortCol).text().toLowerCase();
        if (!isNaN(tdA) && !isNaN(tdB)) {
          return window.historySortAsc ? tdA - tdB : tdB - tdA;
        }
        return window.historySortAsc ? tdA.localeCompare(tdB) : tdB.localeCompare(tdA);
      });
      $.each(rows, function(i, row) {
        $('#historyTable tbody').append(row);
      });
    }
  }
  $('#historySearch, #historyStatusFilter').on('input change', filterAndSortHistory);
  window.historySortCol = undefined;
  window.historySortAsc = true;
  $('#historyTable thead th.sortable').on('click', function() {
    var idx = $(this).index();
    if (window.historySortCol === idx) window.historySortAsc = !window.historySortAsc;
    else { window.historySortCol = idx; window.historySortAsc = true; }
    filterAndSortHistory();
    $('#historyTable thead th').removeClass('asc desc');
    $(this).addClass(window.historySortAsc ? 'asc' : 'desc');
  });
});
</script>
