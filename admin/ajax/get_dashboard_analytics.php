<?php
// admin/ajax/get_dashboard_analytics.php
require_once '../../includes/db.php';

header('Content-Type: application/json');

try {
    // 1. Revenue Last 7 Days
    $revenueStmt = $pdo->query("
        SELECT 
            DATE(payment_date) as date, 
            SUM(amount) as total 
        FROM payments 
        WHERE status = 'successful' 
          AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(payment_date)
        ORDER BY date ASC
    ");
    $revenueData = $revenueStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Fill missing days with 0
    $last7Days = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $last7Days[$date] = $revenueData[$date] ?? 0;
    }

    // 2. Hourly Booking Distribution (Peak Hours Analysis)
    // Based on start_time of active/confirmed/completed reservations
    $hourlyStmt = $pdo->query("
        SELECT 
            HOUR(start_time) as hour_of_day, 
            COUNT(*) as count 
        FROM reservations 
        WHERE status IN ('confirmed', 'ongoing', 'completed')
        GROUP BY HOUR(start_time)
        ORDER BY hour_of_day ASC
    ");
    $hourlyDataRaw = $hourlyStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Fill 0-23 hours
    $hourlyDistribution = [];
    for ($i = 0; $i < 24; $i++) {
        $hourlyDistribution[$i] = $hourlyDataRaw[$i] ?? 0;
    }

    echo json_encode([
        'success' => true,
        'revenue_labels' => array_keys($last7Days),
        'revenue_data' => array_values($last7Days),
        'hourly_labels' => array_keys($hourlyDistribution), // 0, 1, ..., 23
        'hourly_data' => array_values($hourlyDistribution)
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
