<?php
require_once __DIR__ . '/section-common.php';
// Bookings Section (for include or AJAX)


?>
<div class="section-card">
  <h4 class="mb-3 text-primary"><i class="fa fa-calendar-check-o"></i> Manage Expected Bookings</h4>
  <p class="mb-3" style="color:#212529;background:rgba(255,255,255,0.85);padding:0.5rem 1rem;border-radius:0.5rem;">
    Only upcoming <strong>pending</strong> bookings are shown. To confirm/cancel, use the action buttons for the
    corresponding <strong>Ref # (Reservation ID)</strong>.
  </p>
  <div class="row mb-2">
    <div class="col-md-4 mb-2">
      <input type="text" id="bookingsSearch" class="form-control" placeholder="Search bookings...">
    </div>
    <div class="col-md-5 mb-2">
      <div class="input-group">
        <div class="input-group-prepend"><span class="input-group-text small">Start Date</span></div>
        <input type="date" id="bookingsDateFrom" class="form-control">
        <input type="date" id="bookingsDateTo" class="form-control">
      </div>
    </div>
  </div>
  <div class="table-responsive">
    <table id="bookingsTable" class="table table-bordered table-hover bg-white text-dark">
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
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($bookings) === 0): ?>
          <tr>
            <td colspan="9" class="text-center">No bookings found.</td>
          </tr>
        <?php else:
          foreach ($bookings as $b): ?>
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
              <td>
                <?php if ($b['status'] === 'pending'): ?>
                  <form method="post" style="display:inline-block">
                    <input type="hidden" name="reservation_id" value="<?= $b['reservation_id'] ?>">
                    <button type="submit" name="action" value="confirm" class="btn btn-success btn-sm">Confirm</button>
                  </form>
                  <form method="post" style="display:inline-block">
                    <input type="hidden" name="reservation_id" value="<?= $b['reservation_id'] ?>">
                    <button type="submit" name="action" value="cancel" class="btn btn-danger btn-sm">Cancel</button>
                  </form>
                <?php else: ?>
                  <span class="text-muted">No actions</span>
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
    function filterAndSortBookings() {
      var search = $('#bookingsSearch').val().toLowerCase();
      var dateFrom = $('#bookingsDateFrom').val();
      var dateTo = $('#bookingsDateTo').val();

      var rows = $('#bookingsTable tbody tr').get();
      rows.forEach(function (row) {
        if ($(row).find('td').length < 9) return;

        var tds = $(row).children('td');
        var rowText = $(row).text().toLowerCase();
        var dateStr = tds.eq(4).text(); // Start time
        var rowDate = dateStr.split(' ')[0];

        var show = true;
        if (search && rowText.indexOf(search) === -1) show = false;
        if (dateFrom && rowDate < dateFrom) show = false;
        if (dateTo && rowDate > dateTo) show = false;

        $(row).toggle(show);
      });

      // Re-apply sort if active
      if (window.bookingsSortCol !== undefined) {
        rows = rows.filter(function (row) { return $(row).css('display') !== 'none'; });
        rows.sort(function (a, b) {
          var tdA = $(a).children('td').eq(window.bookingsSortCol).text().toLowerCase();
          var tdB = $(b).children('td').eq(window.bookingsSortCol).text().toLowerCase();
          if (!isNaN(tdA) && !isNaN(tdB)) {
            return window.bookingsSortAsc ? tdA - tdB : tdB - tdA;
          }
          return window.bookingsSortAsc ? tdA.localeCompare(tdB) : tdB.localeCompare(tdA);
        });
        $.each(rows, function (i, row) {
          $('#bookingsTable tbody').append(row);
        });
      }
    }

    $('#bookingsSearch, #bookingsDateFrom, #bookingsDateTo').on('input change', filterAndSortBookings);

    window.bookingsSortCol = undefined;
    window.bookingsSortAsc = true;
    $('#bookingsTable thead th.sortable').on('click', function () {
      var idx = $(this).index();
      if (window.bookingsSortCol === idx) window.bookingsSortAsc = !window.bookingsSortAsc;
      else { window.bookingsSortCol = idx; window.bookingsSortAsc = true; }

      filterAndSortBookings(); // Uses the new filter function which handles sorting too

      $('#bookingsTable thead th').removeClass('asc desc');
      $(this).addClass(window.bookingsSortAsc ? 'asc' : 'desc');
    });
  });
</script>