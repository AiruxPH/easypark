<?php
require_once __DIR__ . '/section-common.php';

// Fetch "Today's Completed" count
$today_start = date('Y-m-d 00:00:00');
$today_end = date('Y-m-d 23:59:59');
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE status = 'completed' AND end_time BETWEEN ? AND ?");
$stmt->execute([$today_start, $today_end]);
$today_completed = $stmt->fetchColumn();

// Count available slots (approximate based on total - active, or query directly if needed)
// Better to query directly for accuracy
$stmt = $pdo->prepare("SELECT COUNT(*) FROM parking_slots WHERE slot_status = 'available'");
$stmt->execute();
$available_slots_count = $stmt->fetchColumn();

?>
<div class="row">
    <!-- Card 1: Available Slots -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2 bg-dark text-white border-0"
            style="border-left: 4px solid #28a745 !important;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Available Slots</div>
                        <div class="h5 mb-0 font-weight-bold"><?= $available_slots_count ?> / <?= $slots_total ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fa fa-th-large fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Active Reservations -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2 bg-dark text-white border-0"
            style="border-left: 4px solid #007bff !important;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Active Now</div>
                        <div class="h5 mb-0 font-weight-bold"><?= $active_total ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fa fa-play-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Pending Bookings -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2 bg-dark text-white border-0"
            style="border-left: 4px solid #ffc107 !important;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Requests</div>
                        <div class="h5 mb-0 font-weight-bold"><?= count($bookings) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fa fa-clock-o fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Completed Today -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2 bg-dark text-white border-0"
            style="border-left: 4px solid #17a2b8 !important;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Completed Today</div>
                        <div class="h5 mb-0 font-weight-bold"><?= $today_completed ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fa fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="section-card mt-4">
    <h4 class="mb-3 text-warning"><i class="fa fa-info-circle"></i> Quick Actions</h4>
    <div class="row">
        <div class="col-md-6">
            <p>Use the navigation menu to:</p>
            <ul>
                <li><strong>Bookings:</strong> Review and confirm pending reservations.</li>
                <li><strong>Active:</strong> View currently parked vehicles and ongoing sessions.</li>
                <li><strong>History:</strong> Search past records.</li>
                <li><strong>Slots:</strong> Check real-time slot status and maintenance.</li>
            </ul>
        </div>
        <div class="col-md-6 text-center">
            <img src="../images/easypark_logo.png" alt="Logo" style="max-height: 100px; opacity: 0.8;"
                onerror="this.style.display='none'">
        </div>
    </div>
</div>