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
    <div class="col-12"><div class="alert alert-info text-center">No parking slots found.</div></div>
  </div>
</div>
<script>
$(document).ready(function() {
  function fetchSlots(page = 1) {
    var search = $('#slotSearch').val();
    var type = $('#slotTypeFilter').val();
    var status = $('#slotStatusFilter').val();
    var sortBy = $('#slotSort').val();
    $.ajax({
      url: 'slots-ajax.php',
      method: 'GET',
      data: {
        search: search,
        type: type,
        status: status,
        sort: sortBy,
        page: page
      },
      dataType: 'json',
      beforeSend: function() {
        $('#slotsGrid').html('<div class="col-12 text-center py-4"><span class="spinner-border text-warning"></span></div>');
        $('nav[aria-label="Parking Slots pagination"]').remove();
      },
      success: function(response) {
        if (response && response.cards !== undefined) {
          $('#slotsGrid').html(response.cards);
          if (response.pagination) {
            $('#slotsGrid').after(response.pagination);
          }
        } else {
          $('#slotsGrid').html('<div class="col-12"><div class="alert alert-danger text-center">Invalid response from server.</div></div>');
        }
      },
      error: function(xhr) {
        $('#slotsGrid').html('<div class="col-12"><div class="alert alert-danger text-center">Failed to load slots. Please try again.</div></div>');
      }
    });
  }

  // On filter/search/sort change
  $('#slotSearch, #slotTypeFilter, #slotStatusFilter, #slotSort').on('input change', function() {
    fetchSlots(1);
  });

  // On pagination click (delegated, since pagination is replaced dynamically)
  $(document).on('click', 'nav[aria-label="Parking Slots pagination"] .page-link', function(e) {
    e.preventDefault();
    var page = $(this).data('page');
    if (!page || $(this).parent().hasClass('disabled') || $(this).parent().hasClass('active')) return;
    fetchSlots(page);
  });
});
</script>
