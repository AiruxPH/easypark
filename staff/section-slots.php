<?php
// Parking Slots Overview Section (for include or AJAX)
?>
<div class="section-card">
  <h4 class="mb-3 text-warning"><i class="fa fa-car"></i> Parking Slots Overview</h4>
  <div class="row">
  <?php if (count($all_slots) === 0): ?>
    <div class="col-12"><div class="alert alert-info text-center">No parking slots found.</div></div>
  <?php else: foreach ($all_slots as $slot): ?>
    <div class="col-md-4 mb-3">
      <div class="card bg-dark text-light <?= getSlotColorClass($slot['slot_status']) ?>" style="border-width:3px;">
        <div class="card-body">
          <h5 class="card-title">Slot <?= htmlspecialchars($slot['slot_number']) ?></h5>
          <p class="card-text">Type: <?= htmlspecialchars($slot['slot_type']) ?></p>
          <p class="card-text">Status: <span class="font-weight-bold text-warning"><?= htmlspecialchars(ucfirst($slot['slot_status'])) ?></span></p>
        </div>
      </div>
    </div>
  <?php endforeach; endif; ?>
  </div>
  <?php if ($slots_total_pages > 1): ?>
  <?php list($slots_start, $slots_end) = getPaginationRange($slots_page, $slots_total_pages); ?>
  <nav aria-label="Parking Slots pagination">
    <ul class="pagination justify-content-center">
      <li class="page-item<?= $slots_page <= 1 ? ' disabled' : '' ?>">
        <a class="page-link" href="?slots_page=<?= $slots_page-1 ?>" tabindex="-1">Previous</a>
      </li>
      <?php if ($slots_start > 1): ?>
        <li class="page-item disabled"><span class="page-link">...</span></li>
      <?php endif; ?>
      <?php for ($i = $slots_start; $i <= $slots_end; $i++): ?>
        <li class="page-item<?= $i == $slots_page ? ' active' : '' ?>">
          <a class="page-link" href="?slots_page=<?= $i ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
      <?php if ($slots_end < $slots_total_pages): ?>
        <li class="page-item disabled"><span class="page-link">...</span></li>
      <?php endif; ?>
      <li class="page-item<?= $slots_page >= $slots_total_pages ? ' disabled' : '' ?>">
        <a class="page-link" href="?slots_page=<?= $slots_page+1 ?>">Next</a>
      </li>
    </ul>
  </nav>
  <?php endif; ?>
</div>
