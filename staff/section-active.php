<?php
require_once __DIR__ . '/section-common.php';
// Active Reservations Section (for include or AJAX)
?>
<div class="glass-card">
  <h4 class="mb-4 text-success"><i class="fas fa-play-circle mr-2"></i> Active Reservations (Confirmed & Ongoing)</h4>

  <!-- Filters -->
  <div class="row mb-4">
    <div class="col-md-3 mb-2">
      <div class="input-group">
        <div class="input-group-prepend">
          <span class="input-group-text bg-transparent border-secondary text-white-50"><i
              class="fas fa-search"></i></span>
        </div>
        <input type="text" id="activeSearch" class="form-control glass-input border-left-0" placeholder="Search..."
          value="<?= htmlspecialchars($search) ?>">
      </div>
    </div>
    <div class="col-md-3 mb-2">
      <select id="activeStatusFilter" class="form-control glass-input">
        <option value="">All Statuses</option>
        <option value="confirmed" <?= $filter_status === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
        <option value="ongoing" <?= $filter_status === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
      </select>
    </div>
    <div class="col-md-6 mb-2">
      <div class="input-group">
        <div class="input-group-prepend"><span
            class="input-group-text bg-transparent border-secondary text-white-50">Filter Date</span></div>
        <input type="date" id="activeDateFrom" class="form-control glass-input"
          value="<?= htmlspecialchars($date_from) ?>">
        <div class="input-group-prepend input-group-append"><span
            class="input-group-text bg-transparent border-secondary text-white-50">to</span></div>
        <input type="date" id="activeDateTo" class="form-control glass-input" value="<?= htmlspecialchars($date_to) ?>">
      </div>
    </div>
  </div>

  <!-- Table -->
  <div class="table-responsive">
    <table id="activeTable" class="table glass-table table-hover">
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
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($active_reservations) === 0): ?>
          <tr>
            <td colspan="9" class="text-center py-5 text-white-50">
              <i class="fas fa-car-side fa-3x mb-3 d-block opacity-50"></i>
              No active reservations found.
            </td>
          </tr>
        <?php else:
          foreach ($active_reservations as $b): ?>
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
                $badgeClass = 'badge-glass-info'; // ongoing
                if ($b['status'] == 'confirmed')
                  $badgeClass = 'badge-glass-success';

                // Check if Overdue
                $isOverdue = ($b['status'] === 'ongoing' && strtotime($b['end_time']) < time());
                if ($isOverdue) {
                  $badgeClass = 'badge-glass-danger';
                  $b['status'] = 'OVERDUE';
                }
                ?>
                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(ucfirst($b['status'])) ?></span>
              </td>
              <td>
                <?php if ($b['status'] === 'confirmed'): ?>
                  <form method="post" action="action_booking.php" style="display:inline-block">
                    <input type="hidden" name="reservation_id" value="<?= $b['reservation_id'] ?>">
                    <button type="submit" name="action" value="accept" class="btn btn-primary btn-sm shadow-sm"
                      title="Mark as Ongoing (Vehicle Arrived)">Arrived</button>
                  </form>
                <?php elseif ($b['status'] === 'ongoing' || $b['status'] === 'OVERDUE'): ?>
                  <?php
                  // Inject Rate (Mixed)
                  $hour_rate = 0;
                  $day_rate = 0;
                  if (defined('SLOT_RATES') && isset(SLOT_RATES[$b['slot_type']])) {
                    $hour_rate = SLOT_RATES[$b['slot_type']]['hour'] ?? 0;
                    $day_rate = SLOT_RATES[$b['slot_type']]['day'] ?? ($hour_rate * 24);
                  }
                  $b['hour_rate'] = $hour_rate;
                  $b['day_rate'] = $day_rate;
                  $bJson = htmlspecialchars(json_encode($b));
                  ?>
                  <button type="button" class="btn btn-warning btn-sm shadow-sm action-extend mr-1"
                    data-booking='<?= $bJson ?>' title="Extend Booking"><i class="fas fa-clock"></i></button>

                  <button type="button" class="btn btn-success btn-sm shadow-sm action-complete" data-booking='<?= $bJson ?>'
                    title="Mark as Completed (Vehicle Exited)">Complete</button>
                <?php else: ?>
                  <span class="text-white-50 small">--</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- EXTEND MODAL (Staff) -->
<div class="modal fade" id="extendModal" tabindex="-1" role="dialog" aria-hidden="true" style="color: #000;">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title font-weight-bold"><i class="fas fa-clock mr-2 text-warning"></i> Extend Booking (Staff)
        </h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body p-4">
        <form id="extendForm" method="post" action="action_booking.php">
          <input type="hidden" name="action" value="extend">
          <input type="hidden" name="reservation_id" id="extendResId">

          <div class="alert alert-info small">
            <i class="fas fa-info-circle"></i> Cost will be deducted from the <strong>User's Wallet</strong>. Ensure
            they have balance.
          </div>

          <div class="form-group">
            <label class="font-weight-bold">Extend Duration</label>
            <select id="extendDuration" name="duration" class="form-control">
              <option value="0.5">30 Minutes</option>
              <option value="1">1 Hour</option>
              <option value="2">2 Hours</option>
              <option value="3">3 Hours</option>
              <option value="4">4 Hours</option>
              <option value="5">5 Hours</option>
              <option value="24" class="font-weight-bold">1 Day (24 Hours)</option>
              <option value="48" class="font-weight-bold">2 Days (48 Hours)</option>
              <option value="72" class="font-weight-bold">3 Days (72 Hours)</option>
            </select>
          </div>

          <div class="bg-light p-3 rounded">
            <div class="d-flex justify-content-between mb-1">
              <small class="text-muted">Current End:</small>
              <small class="font-weight-bold" id="extendCurrentEnd">-</small>
            </div>
            <div class="d-flex justify-content-between mb-1">
              <small class="text-muted">New End:</small>
              <small class="text-success font-weight-bold" id="extendNewEnd">-</small>
            </div>
            <div class="d-flex justify-content-between border-top pt-2 mt-2">
              <span class="font-weight-bold">Cost to User:</span>
              <span class="text-warning font-weight-bold" id="extendCost">-</span>
            </div>
            <div class="text-right"><small class="text-muted" id="extendRateDisplay"></small></div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-warning" id="btnConfirmExtend">Extend & Deduct</button>
      </div>
    </div>
  </div>
</div>

<script>
  $(document).ready(function () {
    // ... [Previous Filter Logic] ...

    // EXTEND LOGIC
    let extendBookingData = null;
    const extendModal = $('#extendModal');
    const extendDuration = document.getElementById('extendDuration');
    const extentForm = document.getElementById('extendForm');

    $(document).off('click', '.action-extend').on('click', '.action-extend', function (e) {
      e.preventDefault();
      extendBookingData = $(this).data('booking');

      $('#extendResId').val(extendBookingData.reservation_id);
      $('#extendCurrentEnd').text(extendBookingData.end_time);

      updateExtendCalc();
      extendModal.modal('show');
    });

    $('#extendDuration').on('change', updateExtendCalc);

    function updateExtendCalc() {
      if (!extendBookingData) return;

      const hoursToAdd = parseFloat(extendDuration.value);
      const hourRate = parseFloat(extendBookingData.hour_rate) || 0;
      const dayRate = parseFloat(extendBookingData.day_rate) || (hourRate * 24);

      // Mixed Logic
      const days = Math.floor(hoursToAdd / 24);
      const remHours = hoursToAdd - (days * 24);
      const cost = (days * dayRate) + (remHours * hourRate);

      $('#extendCost').html('🪙 ' + cost.toFixed(2));
      $('#extendRateDisplay').text(`(${days}d @ ${dayRate} + ${remHours}h @ ${hourRate})`);

      // New End Time
      const currentEnd = new Date(extendBookingData.end_time.replace(' ', 'T'));
      const newEnd = new Date(currentEnd.getTime() + (hoursToAdd * 60 * 60 * 1000));
      $('#extendNewEnd').text(newEnd.toLocaleString());
    }

    $('#btnConfirmExtend').on('click', function () {
      if (confirm("Are you sure? This will deduct coins from the customer's wallet.")) {
        extentForm.submit();
      }
    });

    // ACTION COMPLETE LOGIC (Existing)
    $(document).off('click', '.action-complete').on('click', '.action-complete', function (e) {
      e.preventDefault();
      var btn = $(this);
      var booking = btn.data('booking');
      // ... [Modal Population Logic remains same] ...
      // Need to ensuring booking data is refreshed if we changed injection above? 
      // Yes, we updated the PHP block above, so data-booking now has rate details. 
      // The Complete logic relies on data-booking too. 
      // We need to make sure we didn't break the existing JSON structure or the expected 'rate' property if used.
      // We injected hour_rate/day_rate. The old complete logic might look for 'rate'.
      // Let's check:
      // In Complete logic: var rate = parseFloat(booking.rate) || 0;
      // We DO inject 'rate' in the PHP block below if (status == ongoing). 
      // Wait, I replaced the block. I need to make sure I kept the old 'rate' injection or added it.
      // The replace block starts at 117. 
      // Ah, I need to check what I replaced. 

      // Populate Modal
      $('#actionResId').val(booking.reservation_id);
      $('#actionInput').val('complete');
      var modalBody = $('#actionModalBody');
      var confirmBtn = $('#actionConfirmBtn');

      var end = new Date(booking.end_time.replace(' ', 'T'));
      var now = new Date();

      var html = 'Mark Reservation <strong>#' + booking.reservation_id + '</strong> as <span class="text-success font-weight-bold">Completed</span>?';

      if (now > end) {
        var diffSeconds = Math.floor((now - end) / 1000);
        var overHours = Math.ceil(diffSeconds / 3600);
        // Fallback or use hour_rate
        var rate = parseFloat(booking.hour_rate) || 0;
        var penalty = (overHours * rate).toFixed(2);

        html = `
                <div class="alert alert-danger">
                    <h6 class="font-weight-bold"><i class="fas fa-exclamation-triangle"></i> Overdue Warning</h6>
                    <p class="mb-1">Overdue by <strong>${overHours} hour(s)</strong>.</p>
                    <p class="mb-0">Deduction: <strong>🪙${penalty}</strong>.</p>
                </div>
                Mark as complete and apply penalty?
             `;
        confirmBtn.removeClass('btn-primary').addClass('btn-danger').text('Pay & Complete');
      } else {
        confirmBtn.removeClass('btn-danger').addClass('btn-primary').text('Confirm Completion');
      }

      modalBody.html(html);
      $('#actionModal').modal('show');
    });
  });
</script>