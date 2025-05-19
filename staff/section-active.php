<?php
require_once __DIR__ . '/section-common.php';
// Active Reservations Section (for include or AJAX)
?>
<div class="section-card">
  <h4 class="mb-3 text-success"><i class="fa fa-play-circle"></i> Active Reservations (Confirmed & Ongoing)</h4>
  <input type="text" id="activeSearch" class="form-control mb-2" placeholder="Search active reservations...">
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
          <tr><td colspan="8" class="text-center">No active reservations.</td></tr>
        <?php else: foreach ($active_reservations as $b): ?>
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
