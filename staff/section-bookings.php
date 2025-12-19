<?php
require_once __DIR__ . '/section-common.php';
// Bookings Section (for include or AJAX)


?>
<div class="glass-card">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="text-warning mb-0"><i class="fas fa-calendar-check mr-2"></i> Manage Expected Bookings</h4>
  </div>

  <div class="p-3 mb-4 rounded border border-secondary"
    style="background: rgba(255, 255, 255, 0.05); color: rgba(255, 255, 255, 0.7);">
    <i class="fas fa-info-circle mr-2 text-warning"></i>
    Only upcoming <strong>pending</strong> bookings are shown. Use action buttons to Confirm or Cancel requests.
  </div>

  <!-- Filters -->
  <div class="row mb-4">
    <div class="col-md-5 mb-2">
      <div class="input-group">
        <div class="input-group-prepend">
          <span class="input-group-text bg-transparent border-secondary text-white-50"><i
              class="fas fa-search"></i></span>
        </div>
        <input type="text" id="bookingsSearch" class="form-control glass-input border-left-0"
          placeholder="Search by name, plate, or ref #..." value="<?= htmlspecialchars($search) ?>">
      </div>
    </div>
    <div class="col-md-7 mb-2">
      <div class="input-group">
        <div class="input-group-prepend"><span
            class="input-group-text bg-transparent border-secondary text-white-50">Filter Date</span></div>
        <input type="date" id="bookingsDateFrom" class="form-control glass-input"
          value="<?= htmlspecialchars($date_from) ?>">
        <div class="input-group-prepend input-group-append"><span
            class="input-group-text bg-transparent border-secondary text-white-50">to</span></div>
        <input type="date" id="bookingsDateTo" class="form-control glass-input"
          value="<?= htmlspecialchars($date_to) ?>">
      </div>
    </div>
  </div>

  <!-- Table -->
  <div class="table-responsive">
    <table id="bookingsTable" class="table glass-table table-hover">
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
          <th class="sortable">Status</th>
          <th style="min-width: 140px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($bookings) === 0): ?>
          <tr>
            <td colspan="9" class="text-center py-5 text-white-50">
              <i class="fas fa-clipboard-list fa-3x mb-3 d-block opacity-50"></i>
              No pending bookings found matching your criteria.
            </td>
          </tr>
        <?php else:
          foreach ($bookings as $b): ?>
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
                $badgeClass = 'badge-glass-warning';
                if ($b['status'] == 'confirmed')
                  $badgeClass = 'badge-glass-success';
                if ($b['status'] == 'cancelled')
                  $badgeClass = 'badge-glass-danger';
                ?>
                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(ucfirst($b['status'])) ?></span>
              </td>
              <td>
                <?php if ($b['status'] === 'pending'): ?>
                  <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-info btn-sm shadow-sm view-details"
                      data-booking='<?= htmlspecialchars(json_encode($b)) ?>' title="View Details"><i
                        class="fas fa-eye"></i></button>
                    <form method="post" action="action_booking.php" class="mr-1">
                      <input type="hidden" name="reservation_id" value="<?= $b['reservation_id'] ?>">
                      <button type="submit" name="action" value="confirm" class="btn btn-success btn-sm shadow-sm"
                        title="Confirm Booking"><i class="fas fa-check"></i></button>
                    </form>
                    <form method="post" action="action_booking.php">
                      <input type="hidden" name="reservation_id" value="<?= $b['reservation_id'] ?>">
                      <button type="submit" name="action" value="cancel" class="btn btn-danger btn-sm shadow-sm"
                        title="Decline Request"><i class="fas fa-times"></i></button>
                    </form>
                  </div>
                <?php else: ?>
                  <span class="text-white-50 small">No actions</span>
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
    window.filterAndSortBookings = function () {
      var search = $('#bookingsSearch').val();
      var dateFrom = $('#bookingsDateFrom').val();
      var dateTo = $('#bookingsDateTo').val();

      var params = {};
      if (search) params.search = search;
      if (dateFrom) params.date_from = dateFrom;
      if (dateTo) params.date_to = dateTo;

      loadSection('bookings', params);
    };

    var timeout = null;
    $('#bookingsSearch').on('input', function () {
      clearTimeout(timeout);
      timeout = setTimeout(filterAndSortBookings, 500);
    });

    $('#bookingsDateFrom, #bookingsDateTo').on('change', filterAndSortBookings);

    window.bookingsSortCol = undefined;
    window.bookingsSortAsc = true;
    $('#bookingsTable thead th.sortable').on('click', function () {
      // Sorting would need server support or client sort of server results (single page). 
      // For now, client sort of current page is acceptable as Bookings is small.
      // OR ideally implement server sort.
      // But to keep it simple, we leave the client sort logic for the visible table for now, or just disable it if not in requirements.
      // The prompt is about filtering.
    });

    // View Details Logic
    $(document).off('click', '.view-details').on('click', '.view-details', function () {
      const booking = $(this).data('booking');

      $('#view_ref').text(booking.reservation_id);
      $('#view_client').text(booking.first_name + ' ' + booking.last_name);
      $('#view_slot').text(booking.slot_number + ' (' + booking.slot_type + ')');
      $('#view_vehicle').text(booking.plate_number + ' | ' + booking.brand + ' ' + booking.model);

      const start = new Date(booking.start_time).toLocaleString();
      const end = new Date(booking.end_time).toLocaleString();
      $('#view_time').text(start + ' - ' + end);

      $('#view_duration').text(booking.duration + ' hrs');

      // Status Badge
      let badgeClass = 'badge-secondary';
      if (booking.status === 'confirmed') badgeClass = 'badge-success';
      if (booking.status === 'pending') badgeClass = 'badge-warning';
      if (booking.status === 'cancelled') badgeClass = 'badge-danger';

      $('#view_status').removeClass().addClass('badge ' + badgeClass).text(booking.status.toUpperCase());

      $('#viewBookingModal').modal('show');
    });
  });
</script>

<!-- View Booking Detail Modal -->
<div class="modal fade" id="viewBookingModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-card border-0" style="color:white;">
      <div class="modal-header border-bottom-0">
        <h5 class="modal-title">Booking Details <span id="view_ref" class="text-warning font-weight-bold ml-2"></span>
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="row mb-3">
          <div class="col-4 text-white-50">Client:</div>
          <div class="col-8 font-weight-bold" id="view_client"></div>
        </div>
        <div class="row mb-3">
          <div class="col-4 text-white-50">Slot:</div>
          <div class="col-8" id="view_slot"></div>
        </div>
        <div class="row mb-3">
          <div class="col-4 text-white-50">Vehicle:</div>
          <div class="col-8 text-warning" id="view_vehicle"></div>
        </div>
        <div class="row mb-3">
          <div class="col-4 text-white-50">Time:</div>
          <div class="col-8 small" id="view_time"></div>
        </div>
        <div class="row mb-3">
          <div class="col-4 text-white-50">Duration:</div>
          <div class="col-8" id="view_duration"></div>
        </div>
        <div class="row mb-3">
          <div class="col-4 text-white-50">Status:</div>
          <div class="col-8"><span id="view_status" class="badge"></span></div>
        </div>
      </div>
      <div class="modal-footer border-top-0">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>