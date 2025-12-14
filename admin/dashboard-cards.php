<?php
// Dashboard statistics cards section
// Expects: $totalSlots, $availableSlots, $reservedSlots, $occupiedSlots
?>
<div id="dashboard-cards" style="<?= $showParkingSlots || isset($_GET['users']) ? 'display:none;' : '' ?>">
  <div class="row">
    <div class="col-md-3 mb-4">
      <div class="card stats-card primary h-100">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Parking Slots</div>
              <div class="h5 mb-0"><?php echo $totalSlots; ?></div>
            </div>
            <div class="col-auto">
              <i class="fas fa-parking fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-4">
      <div class="card stats-card success h-100">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Available Slots</div>
              <div class="h5 mb-0"><?php echo $availableSlots; ?></div>
            </div>
            <div class="col-auto">
              <i class="fas fa-check-circle fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-4">
      <div class="card stats-card warning h-100">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Reserved Slots</div>
              <div class="h5 mb-0"><?php echo $reservedSlots; ?></div>
            </div>
            <div class="col-auto">
              <i class="fas fa-clock fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-4">
      <div class="card stats-card danger h-100">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Occupied Slots</div>
              <div class="h5 mb-0"><?php echo $occupiedSlots; ?></div>
            </div>
            <div class="col-auto">
              <i class="fas fa-ban fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>