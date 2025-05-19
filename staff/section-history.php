<?php
require_once __DIR__ . '/section-common.php';
// Reservation History Section (for include or AJAX)
?>
<div class="section-card">
  <h4 class="mb-3 text-info"><i class="fa fa-history"></i> Reservation History (Completed/Cancelled)</h4>
  <input type="text" id="historySearch" class="form-control mb-2" placeholder="Search reservation history...">
  <div class="table-responsive">
    <table id="historyTable" class="table table-bordered table-hover bg-white text-dark">
      <thead class="thead-light">
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
