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
  </div>
  <div class="table-responsive">
    <table id="slotsTable" class="table table-bordered table-hover bg-white text-dark">
      <thead class="thead-dark">
        <tr>
          <th class="sortable">Slot #</th>
          <th class="sortable">Type</th>
          <th class="sortable">Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($all_slots) === 0): ?>
          <tr><td colspan="3" class="text-center">No parking slots found.</td></tr>
        <?php else: foreach ($all_slots as $slot): ?>
          <tr>
            <td><?= htmlspecialchars($slot['slot_number']) ?></td>
            <td><?= htmlspecialchars($slot['slot_type']) ?></td>
            <td><?= htmlspecialchars(ucfirst($slot['slot_status'])) ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
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
  function filterAndSortSlots() {
    var search = $('#slotSearch').val().toLowerCase();
    var type = $('#slotTypeFilter').val();
    var status = $('#slotStatusFilter').val();
    var rows = $('#slotsTable tbody tr').get();
    rows.forEach(function(row) {
      var tds = $(row).children('td');
      var slotNum = tds.eq(0).text().toLowerCase();
      var slotType = tds.eq(1).text().toLowerCase();
      var slotStatus = tds.eq(2).text().toLowerCase();
      var show = true;
      if (search && !slotNum.includes(search) && !slotType.includes(search) && !slotStatus.includes(search)) show = false;
      if (type && slotType !== type.replace('_', ' ')) show = false;
      if (status && slotStatus !== status) show = false;
      $(row).toggle(show);
    });
    // Sorting
    if (window.slotSortCol !== undefined) {
      rows = rows.filter(function(row) { return $(row).css('display') !== 'none'; });
      rows.sort(function(a, b) {
        var tdA = $(a).children('td').eq(window.slotSortCol).text().toLowerCase();
        var tdB = $(b).children('td').eq(window.slotSortCol).text().toLowerCase();
        if (!isNaN(tdA) && !isNaN(tdB)) {
          return window.slotSortAsc ? tdA - tdB : tdB - tdA;
        }
        return window.slotSortAsc ? tdA.localeCompare(tdB) : tdB.localeCompare(tdA);
      });
      $.each(rows, function(i, row) {
        $('#slotsTable tbody').append(row);
      });
    }
  }
  $('#slotSearch, #slotTypeFilter, #slotStatusFilter').on('input change', filterAndSortSlots);
  window.slotSortCol = undefined;
  window.slotSortAsc = true;
  $('#slotsTable thead th.sortable').on('click', function() {
    var idx = $(this).index();
    if (window.slotSortCol === idx) window.slotSortAsc = !window.slotSortAsc;
    else { window.slotSortCol = idx; window.slotSortAsc = true; }
    filterAndSortSlots();
    $('#slotsTable thead th').removeClass('asc desc');
    $(this).addClass(window.slotSortAsc ? 'asc' : 'desc');
  });
});
</script>
