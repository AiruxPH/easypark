<?php
// Bookings Section (for include or AJAX)
?>
<div class="section-card">
  <h4 class="mb-3 text-primary"><i class="fa fa-calendar-check-o"></i> Manage Expected Bookings</h4>
  <p class="mb-3">Only upcoming <strong>pending</strong> bookings are shown. To confirm/cancel, use the action buttons for the corresponding <strong>Ref # (Reservation ID)</strong>.</p>
  <input type="text" id="bookingsSearch" class="form-control mb-2" placeholder="Search bookings...">
  <div class="table-responsive">
    <table id="bookingsTable" class="table table-bordered table-hover">
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
