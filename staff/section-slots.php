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
  function fetchSlots(page = 1) {
    var search = $('#slotSearch').val();
    var type = $('#slotTypeFilter').val();
    var status = $('#slotStatusFilter').val();
    var sortBy = $('#slotSort').val();
    $.ajax({
      url: 'staff/slots-ajax.php',
      method: 'GET',
      data: {
        search: search,
        type: type,
        status: status,
        sort: sortBy,
        page: page
      },
      beforeSend: function() {
        $('#slotsGrid').html('<div class="col-12 text-center py-4"><span class="spinner-border text-warning"></span></div>');
      },
      success: function(response) {
        // Expect response to contain both slot cards and pagination nav
        var $temp = $('<div>').html(response);
        var $newGrid = $temp.find('#slotsGrid').length ? $temp.find('#slotsGrid').html() : $temp.html();
        var $newPagination = $temp.find('nav[aria-label="Parking Slots pagination"]');
        $('#slotsGrid').html($newGrid);
        if ($newPagination.length) {
          if ($('nav[aria-label="Parking Slots pagination"]').length) {
            $('nav[aria-label="Parking Slots pagination"]').replaceWith($newPagination);
          } else {
            $('#slotsGrid').after($newPagination);
          }
        } else {
          $('nav[aria-label="Parking Slots pagination"]').remove();
        }
      },
      error: function() {
        $('#slotsGrid').html('<div class="col-12"><div class="alert alert-danger text-center">Failed to load slots. Please try again.</div></div>');
      }
    });
  }

  // Initial fetch (if needed)
  // fetchSlots();

  // On filter/search/sort change
  $('#slotSearch, #slotTypeFilter, #slotStatusFilter, #slotSort').on('input change', function() {
    fetchSlots(1);
  });

  // On pagination click (delegated, since pagination is replaced dynamically)
  $(document).on('click', 'nav[aria-label="Parking Slots pagination"] .page-link', function(e) {
    e.preventDefault();
    var href = $(this).attr('href');
    if (!href || $(this).parent().hasClass('disabled') || $(this).parent().hasClass('active')) return;
    var pageMatch = href.match(/slots_page=(\d+)/);
    var page = pageMatch ? parseInt(pageMatch[1], 10) : 1;
    fetchSlots(page);
  });
});
</script>
