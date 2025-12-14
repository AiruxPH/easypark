<?php
// Parking slots filter and table section
?>
<div id="parking-slots-container" style="<?= $showParkingSlots && !isset($_GET['users']) ? '' : 'display:none;' ?>">
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><i class="fas fa-car"></i> Parking Slots</span>
    </div>
    <div class="card-body">
      <form class="form-inline mb-3">
        <label class="mr-2">Status:</label>
        <select name="status" class="form-control mr-3" onchange="this.form.submit()">
          <option value="">All</option>
          <option value="available" <?= isset($_GET['status']) && $_GET['status'] === 'available' ? ' selected' : '' ?>>
            Available</option>
          <option value="reserved" <?= isset($_GET['status']) && $_GET['status'] === 'reserved' ? ' selected' : '' ?>>
            Reserved
          </option>
          <option value="occupied" <?= isset($_GET['status']) && $_GET['status'] === 'occupied' ? ' selected' : '' ?>>
            Occupied
          </option>
        </select>
        <label class="mr-2">Type:</label>
        <select name="type" class="form-control mr-3" onchange="this.form.submit()">
          <option value="">All</option>
          <option value="two_wheeler" <?= isset($_GET['type']) && $_GET['type'] === 'two_wheeler' ? ' selected' : '' ?>>Two
            Wheeler</option>
          <option value="standard" <?= isset($_GET['type']) && $_GET['type'] === 'standard' ? ' selected' : '' ?>>Standard
          </option>
          <option value="compact" <?= isset($_GET['type']) && $_GET['type'] === 'compact' ? ' selected' : '' ?>>Compact
          </option>
        </select>
      </form>
      <div class="table-responsive">
        <?php include __DIR__ . '/admin-dashboard.php-table.php'; ?>
      </div>
    </div>
  </div>
</div>