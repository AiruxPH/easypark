<?php
// admin/sections/dashboard.php
global $pdo;

// Get Filter Date (Default to Today)
$filterDate = $_GET['filter_date'] ?? date('Y-m-d');

// 1. Fetch KPI Metrics (Daily Focus)
// Revenue (for filtered date)
$revenueStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'successful' AND DATE(payment_date) = ?");
$revenueStmt->execute([$filterDate]);
$revenueToday = $revenueStmt->fetchColumn();

// Arrivals (for filtered date)
$arrivalsStmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE DATE(start_time) = ? AND status IN ('confirmed', 'ongoing', 'completed')");
$arrivalsStmt->execute([$filterDate]);
$arrivalsToday = $arrivalsStmt->fetchColumn();

// Active Parking (Always Real-time, ignore date filter)
$activeNow = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'ongoing'")->fetchColumn();

// 2. Fetch Overstaying Vehicles (Critical Alert - Real-time)
$overstays = $pdo->query("
    SELECT 
        r.reservation_id,
        ps.slot_number, 
        r.end_time, 
        CONCAT(u.first_name, ' ', u.last_name) as user_name,
        v.plate_number,
        TIMESTAMPDIFF(MINUTE, r.end_time, NOW()) as minutes_over
    FROM parking_slots ps
    JOIN reservations r ON ps.parking_slot_id = r.parking_slot_id
    JOIN users u ON r.user_id = u.user_id
    JOIN vehicles v ON r.vehicle_id = v.vehicle_id
    WHERE ps.slot_status = 'occupied' 
      AND r.status = 'ongoing'
      AND r.end_time < NOW()
    ORDER BY r.end_time ASC
")->fetchAll(PDO::FETCH_ASSOC);

$overstayCount = count($overstays);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Dashboard Overview</h2>
        
        <!-- Date Filter Form -->
        <form method="GET" class="form-inline shadow-sm p-2 bg-white rounded">
            <label class="mr-2 font-weight-bold text-gray-600"><i class="fa fa-filter"></i> Filter Date:</label>
            <input type="date" name="filter_date" class="form-control form-control-sm mr-2" value="<?= $filterDate ?>">
            <button type="submit" class="btn btn-sm btn-primary">Apply</button>
            <?php if($filterDate !== date('Y-m-d')): ?>
                <a href="index.php" class="btn btn-sm btn-light ml-1" title="Reset to Today"><i class="fa fa-refresh"></i></a>
            <?php endif; ?>
        </form>
    </div>

    <!-- KPI Cards Row -->
    <div class="row">

        <!-- Revenue Today -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2 stats-card success">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Revenue (<?= $filterDate === date('Y-m-d') ? 'Today' : $filterDate ?>)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₱<?= number_format($revenueToday, 2) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fa fa-money fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Arrivals Today -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2 stats-card primary">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Arrivals (<?= $filterDate === date('Y-m-d') ? 'Today' : $filterDate ?>)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $arrivalsToday ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fa fa-car fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Now -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2 stats-card warning">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Active Parking (Now)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $activeNow ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fa fa-clock-o fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overstay Alerts -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2 stats-card danger">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Overstaying Vehicles</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $overstayCount ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fa fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">

        <!-- Area Chart: Revenue Trend -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Revenue Overview (Last 7 Days)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 320px;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pie/Bar Chart: Hourly Distribution -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Peak Hours (Heatmap)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2" style="height: 250px;">
                        <canvas id="hourlyChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small text-muted">
                        Based on historical booking start times
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Overstaying Vehicles Table -->
    <?php if ($overstays): ?>
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4 border-left-danger">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-danger">⚠️ Action Required: Overstaying Vehicles</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Slot</th>
                                    <th>Plate Number</th>
                                    <th>Owner</th>
                                    <th>Scheduled End</th>
                                    <th>Overdue By</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($overstays as $os): ?>
                                <tr>
                                    <td class="font-weight-bold"><?= htmlspecialchars($os['slot_number']) ?></td>
                                    <td><?= htmlspecialchars($os['plate_number']) ?></td>
                                    <td><?= htmlspecialchars($os['user_name']) ?></td>
                                    <td><?= htmlspecialchars($os['end_time']) ?></td>
                                    <td class="text-danger font-weight-bold"><?= $os['minutes_over'] ?> mins</td>
                                    <td>
                                        <button class="btn btn-sm btn-danger" onclick="resolveOverstay(<?= $os['reservation_id'] ?>, '<?= $os['plate_number'] ?>')">
                                            Resolve Issue
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- Resolve Overstay Modal -->
<div class="modal fade" id="resolveOverstayModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Resolve Overstay</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Vehicle <strong><span id="resolve_plate"></span></strong> has exceeded the parking time.</p>
                <p>Select an action:</p>
                <div class="d-grid gap-2">
                    <button class="btn btn-success btn-block" onclick="submitResolution('complete')">
                        <i class="fa fa-check-circle"></i> <strong>Force Complete (Exit)</strong><br>
                        <small>Customer has left or paid penalty. Free the slot.</small>
                    </button>
                    <button class="btn btn-warning btn-block" onclick="submitResolution('extend')">
                        <i class="fa fa-clock-o"></i> <strong>Extend Time (+1 Hour)</strong><br>
                        <small>Customer requested extension. Add 1 hour.</small>
                    </button>
                </div>
                <input type="hidden" id="resolve_reservation_id">
            </div>
        </div>
    </div>
</div>

<!-- Chart.js via CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
let currentReservationId = null;

function resolveOverstay(resId, plate) {
    currentReservationId = resId;
    document.getElementById('resolve_plate').textContent = plate;
    document.getElementById('resolve_reservation_id').value = resId;
    $('#resolveOverstayModal').modal('show');
}

function submitResolution(action) {
    if(!currentReservationId) return;

    if(!confirm('Are you sure you want to perform this action?')) return;

    fetch('ajax/resolve_overstay.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `reservation_id=${currentReservationId}&action=${action}`
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    });
}

document.addEventListener("DOMContentLoaded", function() {
    // Determine font family from CSS
    Chart.defaults.font.family = 'Inter';
    Chart.defaults.color = '#858796';

    fetch('ajax/get_dashboard_analytics.php')
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error("Failed to load chart data", data.error);
                return;
            }

            // 1. Revenue Chart
            const ctxRev = document.getElementById("revenueChart");
            new Chart(ctxRev, {
                type: 'line',
                data: {
                    labels: data.revenue_labels,
                    datasets: [{
                        label: "Revenue (₱)",
                        lineTension: 0.3,
                        backgroundColor: "rgba(78, 115, 223, 0.05)",
                        borderColor: "rgba(78, 115, 223, 1)",
                        pointRadius: 3,
                        pointBackgroundColor: "rgba(78, 115, 223, 1)",
                        pointBorderColor: "rgba(78, 115, 223, 1)",
                        pointHoverRadius: 3,
                        pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                        pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                        pointHitRadius: 10,
                        pointBorderWidth: 2,
                        data: data.revenue_data,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    layout: { padding: { left: 10, right: 25, top: 25, bottom: 0 } },
                    scales: {
                        x: { grid: { display: false, drawBorder: false }, ticks: { maxTicksLimit: 7 } },
                        y: { ticks: { maxTicksLimit: 5, padding: 10, callback: function(value) { return '₱' + value; } }, grid: { color: "rgb(234, 236, 244)", zeroLineColor: "rgb(234, 236, 244)", drawBorder: false, borderDash: [2], zeroLineBorderDash: [2] } },
                    },
                    plugins: { 
                        legend: { display: false },
                        tooltip: { 
                            callbacks: { label: function(context) { return 'Revenue: ₱' + context.parsed.y.toLocaleString(); } }
                        }
                    },
                },
            });

            // 2. Hourly Chart
            const ctxHour = document.getElementById("hourlyChart");
            new Chart(ctxHour, {
                type: 'bar', // Using Bar for hour distribution
                data: {
                    labels: ['12AM', '1', '2', '3', '4', '5', '6', '7AM', '8', '9', '10', '11', '12PM', '1', '2', '3', '4', '5', '6', '7PM', '8', '9', '10', '11'],
                    datasets: [{
                        label: "Bookings",
                        backgroundColor: "#4e73df",
                        hoverBackgroundColor: "#2e59d9",
                        borderColor: "#4e73df",
                        data: data.hourly_data,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        x: { grid: { display: false, drawBorder: false } },
                        y: { ticks: { maxTicksLimit: 5, padding: 10 }, grid: { color: "rgb(234, 236, 244)", zeroLineColor: "rgb(234, 236, 244)", drawBorder: false, borderDash: [2], zeroLineBorderDash: [2] } },
                    },
                    plugins: { legend: { display: false } },
                },
            });
        });
});
</script>