<?php
require_once __DIR__ . '/section-common.php';
// Active Reservations Section (for include or AJAX)
?>
<div class="section-card">
  <h4 class="mb-3 text-success"><i class="fa fa-play-circle"></i> Active Reservations (Confirmed & Ongoing)</h4>
  <div class="row mb-2">
    <div class="col-md-4 mb-2">
      <input type="text" id="activeSearch" class="form-control" placeholder="Search active reservations...">
    </div>
    <div class="col-md-3 mb-2">
      <select id="activeStatusFilter" class="form-control">
        <option value="">All Statuses</option>
        <option value="confirmed">Confirmed</option>
        <option value="ongoing">Ongoing</option>
      </select>
    </div>
    <div class="col-md-5 mb-2">
      <div class="input-group">
        <div class="input-group-prepend"><span class="input-group-text small">Date</span></div>
        <input type="date" id="activeDateFrom" class="form-control">
        <input type="date" id="activeDateTo" class="form-control">
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
                  <form method="post" style="display:inline-block">
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
    function filterAndSortActive() {
      var search = $('#activeSearch').val().toLowerCase();
      var status = $('#activeStatusFilter').val();
      var dateFrom = $('#activeDateFrom').val();
      var dateTo = $('#activeDateTo').val();

      var rows = $('#activeTable tbody tr').get();
      rows.forEach(function (row) {
        if ($(row).find('td').length < 8) return;

        var tds = $(row).children('td');
        var rowStatus = tds.eq(7).text().toLowerCase();
        var rowText = $(row).text().toLowerCase();
        var dateStr = tds.eq(4).text();
        var rowDate = dateStr.split(' ')[0];

        var show = true;
        if (search && rowText.indexOf(search) === -1) show = false;
        if (status && rowStatus !== status && rowStatus.indexOf(status) === -1) show = false; // status might contain buttons
        if (dateFrom && rowDate < dateFrom) show = false;
        if (dateTo && rowDate > dateTo) show = false;

        $(row).toggle(show);
      });
      // Sorting
      if (window.activeSortCol !== undefined) {
        rows = rows.filter(function (row) { return $(row).css('display') !== 'none'; });
        rows.sort(function (a, b) {
          var tdA = $(a).children('td').eq(window.activeSortCol).text().toLowerCase();
          var tdB = $(b).children('td').eq(window.activeSortCol).text().toLowerCase();
          if (!isNaN(tdA) && !isNaN(tdB)) {
            return window.activeSortAsc ? tdA - tdB : tdB - tdA;
          }
          return window.activeSortAsc ? tdA.localeCompare(tdB) : tdB.localeCompare(tdA);
        });
        $.each(rows, function (i, row) {
          $('#activeTable tbody').append(row);
        });
      }
    }
    $('#activeSearch, #activeStatusFilter, #activeDateFrom, #activeDateTo').on('input change', filterAndSortActive);
    window.activeSortCol = undefined;
    window.activeSortAsc = true;
    $('#activeTable thead th.sortable').on('click', function () {
      var idx = $(this).index();
      if (window.activeSortCol === idx) window.activeSortAsc = !window.activeSortAsc;
      else { window.activeSortCol = idx; window.activeSortAsc = true; }
      filterAndSortActive();
      $('#activeTable thead th').removeClass('asc desc');
      $(this).addClass(window.activeSortAsc ? 'asc' : 'desc');
    });
  });
</script>