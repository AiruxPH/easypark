<?php
// admin/sections/dashboard.php
global $pdo;

// 1. Fetch KPI Metrics (Daily Focus)
// Revenue Today
$revenueToday = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'successful' AND DATE(payment_date) = CURDATE()")->fetchColumn();

// Arrivals Today (Reservations starting today)
$arrivalsToday = $pdo->query("SELECT COUNT(*) FROM reservations WHERE DATE(start_time) = CURDATE() AND status IN ('confirmed', 'ongoing', 'completed')")->fetchColumn();

// Active Parking (Currently ongoing)
$activeNow = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'ongoing'")->fetchColumn();

// 2. Fetch Overstaying Vehicles (Critical Alert)
// Occupied slots where the reservation time has passed
$overstays = $pdo->query("
    SELECT 
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
        <span class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i
                class="fa fa-calendar fa-sm text-white-50"></i> <?= date('F d, Y') ?></span>
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
                                Revenue (Today)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₱<?= number_format($revenueToday, 2) ?>
                            </div>
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
                                Arrivals Today</div>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Active Parking
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
                                                <a href="?section=parking&status=occupied" class="btn btn-sm btn-danger">Manage
                                                    Slot</a>
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

<!-- Chart.js via CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
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
                            y: { ticks: { maxTicksLimit: 5, padding: 10, callback: function (value) { return '₱' + value; } }, grid: { color: "rgb(234, 236, 244)", zeroLineColor: "rgb(234, 236, 244)", drawBorder: false, borderDash: [2], zeroLineBorderDash: [2] } },
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: { label: function (context) { return 'Revenue: ₱' + context.parsed.y.toLocaleString(); } }
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