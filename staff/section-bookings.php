<?php
require_once __DIR__ . '/section-common.php';
// Bookings Section (for include or AJAX)
?>
<div class="section-card">
  <h4 class="mb-3 text-primary"><i class="fa fa-calendar-check-o"></i> Manage Expected Bookings</h4>
  <p class="mb-3" style="color:#212529;background:rgba(255,255,255,0.85);padding:0.5rem 1rem;border-radius:0.5rem;">
    Only upcoming <strong>pending</strong> bookings are shown. To confirm/cancel, use the action buttons for the corresponding <strong>Ref # (Reservation ID)</strong>.
  </p>
  <div class="row mb-2">
    <div class="col-md-4 mb-2">
      <input type="text" id="bookingsSearch" class="form-control" placeholder="Search bookings...">
    </div>
    <div class="col-md-3 mb-2">
      <select id="bookingsStatusFilter" class="form-control">
        <option value="">All Statuses</option>
        <option value="pending">Pending</option>
        <option value="confirmed">Confirmed</option>
        <option value="cancelled">Cancelled</option>
        <option value="completed">Completed</option>
      </select>
    </div>
  </div>
  <div class="table-responsive">
    <table id="bookingsTable" class="table table-bordered table-hover bg-white text-dark">
      <thead class="thead-dark">
        <tr>
          <th>Ref #</th>
          <th>Client</th>
          <th>Slot</th>
          <th>Vehicle</th>
          <th>Start</th>
          <th>End</th>
          <th>Duration</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($bookings) === 0): ?>
          <tr><td colspan="9" class="text-center">No bookings found.</td></tr>
        <?php else: foreach ($bookings as $b): ?>
          <tr>
            <td><?= htmlspecialchars($b['reservation_id']) ?></td>
            <td><?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?></td>
            <td><?= htmlspecialchars($b['slot_number']) ?> (<?= htmlspecialchars($b['slot_type']) ?>)</td>
            <td><?= htmlspecialchars($b['brand'].' '.$b['model'].' - '.$b['plate_number']) ?></td>
            <td><?= htmlspecialchars($b['start_time']) ?></td>
            <td><?= htmlspecialchars($b['end_time']) ?></td>
            <td><?= htmlspecialchars($b['duration']) ?></td>
            <td><?= htmlspecialchars(ucfirst($b['status'])) ?></td>
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
$(document).ready(function() {
  function filterBookings() {
    var search = $('#bookingsSearch').val().toLowerCase();
    var status = $('#bookingsStatusFilter').val();
    $('#bookingsTable tbody tr').each(function() {
      var tds = $(this).children('td');
      var rowStatus = tds.eq(7).text().toLowerCase();
      var rowText = $(this).text().toLowerCase();
      var show = true;
      if (search && rowText.indexOf(search) === -1) show = false;
      if (status && rowStatus !== status) show = false;
      $(this).toggle(show);
    });
  }
  $('#bookingsSearch, #bookingsStatusFilter').on('input change', filterBookings);
});
</script>
