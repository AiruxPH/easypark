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
        <div
            class="glass-card p-4 h-100 d-flex flex-column justify-content-between position-relative overflow-hidden group">
            <div class="position-absolute"
                style="top: -10px; right: -10px; font-size: 5rem; opacity: 0.05; color: #fff;">
                <i class="fas fa-th-large"></i>
            </div>
            <div>
                <div class="text-xs font-weight-bold text-success text-uppercase mb-2 letter-spacing-1">Available Slots
                </div>
                <div class="h2 mb-0 font-weight-bold text-white"><?= $available_slots_count ?> <span
                        class="text-white-50 h5">/ <?= $slots_total ?></span></div>
            </div>
            <div class="mt-3">
                <div class="progress" style="height: 6px; background: rgba(255,255,255,0.1);">
                    <?php $percent = ($slots_total > 0) ? ($available_slots_count / $slots_total) * 100 : 0; ?>
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $percent ?>%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Active Reservations -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="glass-card p-4 h-100 d-flex flex-column justify-content-between position-relative overflow-hidden">
            <div class="position-absolute"
                style="top: -10px; right: -10px; font-size: 5rem; opacity: 0.05; color: #fff;">
                <i class="fas fa-play-circle"></i>
            </div>
            <div>
                <div class="text-xs font-weight-bold text-primary text-uppercase mb-2 letter-spacing-1">Active Now</div>
                <div class="h2 mb-0 font-weight-bold text-white"><?= $active_total ?></div>
            </div>
            <div class="mt-3 text-white-50 small">
                Vehicles currently parked
            </div>
        </div>
    </div>

    <!-- Card 3: Pending Bookings -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="glass-card p-4 h-100 d-flex flex-column justify-content-between position-relative overflow-hidden">
            <div class="position-absolute"
                style="top: -10px; right: -10px; font-size: 5rem; opacity: 0.05; color: #fff;">
                <i class="fas fa-clock"></i>
            </div>
            <div>
                <div class="text-xs font-weight-bold text-warning text-uppercase mb-2 letter-spacing-1">Pending Requests
                </div>
                <div class="h2 mb-0 font-weight-bold text-white"><?= count($bookings) ?></div>
            </div>
            <div class="mt-3">
                <a href="javascript:void(0)" onclick="loadSection('bookings')"
                    class="text-warning small font-weight-bold text-decoration-none">Review Requests <i
                        class="fas fa-arrow-right ml-1"></i></a>
            </div>
        </div>
    </div>

    <!-- Card 4: Completed Today -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="glass-card p-4 h-100 d-flex flex-column justify-content-between position-relative overflow-hidden">
            <div class="position-absolute"
                style="top: -10px; right: -10px; font-size: 5rem; opacity: 0.05; color: #fff;">
                <i class="fas fa-check-circle"></i>
            </div>
            <div>
                <div class="text-xs font-weight-bold text-info text-uppercase mb-2 letter-spacing-1">Completed Today
                </div>
                <div class="h2 mb-0 font-weight-bold text-white"><?= $today_completed ?></div>
            </div>
            <div class="mt-3 text-white-50 small">
                Successfully checked out
            </div>
        </div>
    </div>
</div>

<div class="glass-card mt-2">
    <h4 class="mb-4 text-warning"><i class="fas fa-bolt mr-2"></i> Quick Actions Center</h4>
    <div class="row align-items-center">
        <div class="col-md-7 text-white-50">
            <p class="mb-3">Welcome to the Staff Command Center. Select an action to proceed:</p>
            <div class="d-flex flex-wrap">
                <button onclick="loadSection('bookings')" class="btn btn-glass mr-2 mb-2"><i
                        class="fas fa-calendar-check mr-2"></i> Review Bookings</button>
                <button onclick="loadSection('active')" class="btn btn-glass mr-2 mb-2"><i class="fas fa-car mr-2"></i>
                    View Parked Cars</button>
                <button onclick="loadSection('slots')" class="btn btn-glass mb-2"><i class="fas fa-warehouse mr-2"></i>
                    Manage Slots</button>
            </div>
        </div>
        <div class="col-md-5 text-center d-none d-md-block">
            <i class="fas fa-parking fa-5x text-white-50" style="opacity: 0.2"></i>
        </div>
    </div>
</div>