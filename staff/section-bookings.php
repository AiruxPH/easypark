<?php
require_once __DIR__ . '/section-common.php';
// Bookings Section (for include or AJAX)

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['reservation_id'])) {
    $reservation_id = filter_input(INPUT_POST, 'reservation_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'];

    if (!$reservation_id) {
        $error_message = 'Invalid reservation ID.';
    } else {
        try {
            // Begin transaction
            $conn->beginTransaction();

            // Verify the reservation exists and is pending
            $stmt = $conn->prepare("SELECT r.parking_slot_id, r.status FROM reservations r WHERE r.reservation_id = ?");
            $stmt->execute([$reservation_id]);
            $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reservation) {
                throw new Exception('Reservation not found.');
            }
            if ($reservation['status'] !== 'pending') {
                throw new Exception('Reservation is not pending.');
            }

            if ($action === 'confirm') {
                // Update reservation status to confirmed
                $stmt = $conn->prepare("UPDATE reservations SET status = 'confirmed' WHERE reservation_id = ?");
                $stmt->execute([$reservation_id]);

                // Update parking slot status to reserved
                $stmt = $conn->prepare("UPDATE parking_slots SET slot_status = 'reserved' WHERE parking_slot_id = ?");
                $stmt->execute([$reservation['parking_slot_id']]);

                $success_message = 'Reservation #' . $reservation_id . ' confirmed successfully.';
            } elseif ($action === 'cancel') {
                // Delete the reservation
                $stmt = $conn->prepare("DELETE FROM reservations WHERE reservation_id = ?");
                $stmt->execute([$reservation_id]);

                // Update parking slot status to available
                $stmt = $conn->prepare("UPDATE parking_slots SET slot_status = 'available' WHERE parking_slot_id = ?");
                $stmt->execute([$reservation['parking_slot_id']]);

                $success_message = 'Reservation #' . $reservation_id . ' cancelled successfully.';
            } else {
                throw new Exception('Invalid action.');
            }

            // Commit transaction
            $conn->commit();
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollBack();
            $error_message = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<div class="section-card">
  <h4 class="mb-3 text-primary"><i class="fa fa-calendar-check-o"></i> Manage Expected Bookings</h4>
  <p class="mb-3" style="color:#212529;background:rgba(255,255,255,0.85);padding:0.5rem 1rem;border-radius:0.5rem;">
    Only upcoming <strong>pending</strong> bookings are shown. To confirm/cancel, use the action buttons for the corresponding <strong>Ref # (Reservation ID)</strong>.
  </p>
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
  window.bookingsSortCol = undefined;
  window.bookingsSortAsc = true;
  $('#bookingsTable thead th.sortable').on('click', function() {
    var idx = $(this).index();
    if (window.bookingsSortCol === idx) window.bookingsSortAsc = !window.bookingsSortAsc;
    else { window.bookingsSortCol = idx; window.bookingsSortAsc = true; }
    var rows = $('#bookingsTable tbody tr').get();
    rows.sort(function(a, b) {
      var tdA = $(a).children('td').eq(window.bookingsSortCol).text().toLowerCase();
      var tdB = $(b).children('td').eq(window.bookingsSortCol).text().toLowerCase();
      if (!isNaN(tdA) && !isNaN(tdB)) {
        return window.bookingsSortAsc ? tdA - tdB : tdB - tdA;
      }
      return window.bookingsSortAsc ? tdA.localeCompare(tdB) : tdB.localeCompare(tdA);
    });
    $.each(rows, function(i, row) {
      $('#bookingsTable tbody').append(row);
    });
    $('#bookingsTable thead th').removeClass('asc desc');
    $(this).addClass(window.bookingsSortAsc ? 'asc' : 'desc');
  });
});
</script>
