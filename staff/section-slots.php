<?php
require_once __DIR__ . '/section-common.php';
// Parking Slots Overview Section (for include or AJAX)

// Fetch dynamic slot types for filter
$typesStmt = $pdo->query("SELECT DISTINCT slot_type FROM parking_slots ORDER BY slot_type");
$availableTypes = $typesStmt->fetchAll(PDO::FETCH_COLUMN);
?>
<div class="section-card">
  <h4 class="mb-3 text-warning"><i class="fa fa-car"></i> Parking Slots Overview</h4>
  <div class="row mb-3">
    <div class="col-md-4 mb-2">
      <input type="text" id="slotSearch" class="form-control" placeholder="Search slots..."
        value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="col-md-3 mb-2">
      <select id="slotTypeFilter" class="form-control">
        <option value="">All Types</option>
        <?php foreach ($availableTypes as $t): ?>
          <option value="<?= htmlspecialchars($t) ?>" <?= $filter_type === $t ? 'selected' : '' ?>>
            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $t))) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3 mb-2">
      <select id="slotStatusFilter" class="form-control">
        <option value="">All Statuses</option>
        <option value="available" <?= $filter_status === 'available' ? 'selected' : '' ?>>Available</option>
        <option value="reserved" <?= $filter_status === 'reserved' ? 'selected' : '' ?>>Reserved</option>
        <option value="occupied" <?= $filter_status === 'occupied' ? 'selected' : '' ?>>Occupied</option>
        <option value="unavailable" <?= $filter_status === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
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
  <div class="row" id="slotsGrid"></div>
</div>

<script>
  $(document).ready(function () {
    $('#slotsGrid').html('<div class="col-12"><div class="alert alert-danger text-center">Failed to load slots. Please try again.</div></div>');
    $('nav[aria-label="Parking Slots pagination"]').remove();
  }
      });
    }

  // On filter/search/sort change
  $('#slotSearch, #slotTypeFilter, #slotStatusFilter, #slotSort').on('input change', function () {
    fetchSlots(1);
  });

  // On pagination click (delegated, since pagination is replaced dynamically)
  $(document).on('click', 'nav[aria-label="Parking Slots pagination"] .page-link', function (e) {
    e.preventDefault();
    var page = $(this).data('page');
    if (!page || $(this).parent().hasClass('disabled') || $(this).parent().hasClass('active')) return;
    fetchSlots(page);
  });

  // Initial load: show default sorted slots immediately
  fetchSlots(1);
  });
</script>