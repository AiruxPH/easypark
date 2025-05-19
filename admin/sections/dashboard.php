<?php
// Fetch statistics
$stats = [
    'total_slots' => $pdo->query("SELECT COUNT(*) FROM parking_slots")->fetchColumn(),
    'available_slots' => $pdo->query("SELECT COUNT(*) FROM parking_slots WHERE slot_status = 'available'")->fetchColumn(),
    'reserved_slots' => $pdo->query("SELECT COUNT(*) FROM parking_slots WHERE slot_status = 'reserved'")->fetchColumn(),
    'occupied_slots' => $pdo->query("SELECT COUNT(*) FROM parking_slots WHERE slot_status = 'occupied'")->fetchColumn(),
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'client'")->fetchColumn(),
    'total_vehicles' => $pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn(),
    'active_reservations' => $pdo->query("SELECT COUNT(*) FROM reservations WHERE status IN ('confirmed', 'ongoing')")->fetchColumn(),
    'total_revenue' => $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'successful'")->fetchColumn()
];
?>

<div class="container-fluid">
    <h2 class="mb-4">Dashboard Overview</h2>
    
    <div class="row">
        <!-- Parking Statistics -->
        <div class="col-md-3">
            <div class="card stats-card primary">
                <div class="card-body">
                    <h5 class="card-title">Total Slots</h5>
                    <h3><?= number_format($stats['total_slots']) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card success">
                <div class="card-body">
                    <h5 class="card-title">Available</h5>
                    <h3><?= number_format($stats['available_slots']) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card warning">
                <div class="card-body">
                    <h5 class="card-title">Reserved</h5>
                    <h3><?= number_format($stats['reserved_slots']) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card danger">
                <div class="card-body">
                    <h5 class="card-title">Occupied</h5>
                    <h3><?= number_format($stats['occupied_slots']) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Business Statistics -->
        <div class="col-md-3">
            <div class="card stats-card primary">
                <div class="card-body">
                    <h5 class="card-title">Total Users</h5>
                    <h3><?= number_format($stats['total_users']) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card success">
                <div class="card-body">
                    <h5 class="card-title">Total Vehicles</h5>
                    <h3><?= number_format($stats['total_vehicles']) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card warning">
                <div class="card-body">
                    <h5 class="card-title">Active Reservations</h5>
                    <h3><?= number_format($stats['active_reservations']) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card primary">
                <div class="card-body">
                    <h5 class="card-title">Total Revenue</h5>
                    <h3>₱<?= number_format($stats['total_revenue'], 2) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity Section -->
    <div class="row mt-4">
        <div class="col-md-6 offset-md-3">
            <div class="card">
                <div class="card-header">
                    <h5>Recent Transactions</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $stmt = $pdo->query("
                                SELECT * FROM payments 
                                ORDER BY payment_date DESC 
                                LIMIT 5
                            ");
                            while ($row = $stmt->fetch()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['payment_id']) ?></td>
                                    <td>₱<?= number_format($row['amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($row['method']) ?></td>
                                    <td><span class="badge badge-<?= $row['status'] === 'successful' ? 'success' : 'warning' ?>"><?= ucfirst(htmlspecialchars($row['status'])) ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
