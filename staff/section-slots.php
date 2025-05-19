<?php
require_once __DIR__ . '/section-common.php';
// Parking Slots Overview Section (for include or AJAX)
?>
<div class="section-card">
  <h4 class="mb-3 text-warning"><i class="fa fa-car"></i> Parking Slots Overview</h4>
  <div class="row mb-3">
    <div class="col-md-4 mb-2">
      <input type="text" id="slotSearch" class="form-control" placeholder="Search slots...">
    </div>
    <div class="col-md-3 mb-2">
      <select id="slotTypeFilter" class="form-control">
        <option value="">All Types</option>
        <option value="two_wheeler">Two Wheeler</option>
        <option value="standard">Standard</option>
        <option value="compact">Compact</option>
      </select>
    </div>
    <div class="col-md-3 mb-2">
      <select id="slotStatusFilter" class="form-control">
        <option value="">All Statuses</option>
        <option value="available">Available</option>
        <option value="reserved">Reserved</option>
        <option value="occupied">Occupied</option>
      </select>
    </div>
    <div class="col-md-2 mb-2">
      <select id="slotSort" class="form-control">
        <option value="slot_number">Sort: Slot #</option>
        <option value="slot_type">Sort: Type</option>
        <option value="slot_status">Sort: Status</option>
      </select>
    </div>
  </div>
  <div class="row" id="slotsGrid">
  <?php if (count($all_slots) === 0): ?>
    <div class="col-12"><div class="alert alert-info text-center">No parking slots found.</div></div>
  <?php else: foreach ($all_slots as $slot): ?>
    <div class="col-md-4 mb-3 slot-card" data-slot_number="<?= htmlspecialchars($slot['slot_number']) ?>" data-slot_type="<?= htmlspecialchars($slot['slot_type']) ?>" data-slot_status="<?= htmlspecialchars($slot['slot_status']) ?>">
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
<script>
$(document).ready(function() {
  function normalize(text) {
    return (text || '').toString().toLowerCase().trim();
  }
  function filterAndSortSlots() {
    var search = normalize($('#slotSearch').val());
    var type = $('#slotTypeFilter').val();
    var status = $('#slotStatusFilter').val();
    var sortBy = $('#slotSort').val();
    var $cards = $('.slot-card');
    $cards.each(function() {
      var $card = $(this);
      var slotNum = normalize($card.data('slot_number'));
      var slotType = normalize($card.data('slot_type'));
      var slotStatus = normalize($card.data('slot_status'));
      var show = true;
      if (search && !slotNum.includes(search) && !slotType.includes(search) && !slotStatus.includes(search)) show = false;
      if (type && slotType !== type) show = false;
      if (status && slotStatus !== status) show = false;
      $card.toggle(show);
    });
    // Sorting
    var $visible = $cards.filter(':visible');
    $visible.sort(function(a, b) {
      var valA = normalize($(a).data(sortBy));
      var valB = normalize($(b).data(sortBy));
      if (!isNaN(valA) && !isNaN(valB)) {
        return valA - valB;
      }
      return valA.localeCompare(valB);
    });
    $('#slotsGrid').append($visible);
  }
  $('#slotSearch, #slotTypeFilter, #slotStatusFilter, #slotSort').on('input change', filterAndSortSlots);
});
</script>
