<?php
// admin/ajax/get_dashboard_stats.php
require_once '../../includes/db.php';

header('Content-Type: application/json');

try {
    // 1. Revenue Today
    $revenueStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'successful' AND DATE(payment_date) = CURDATE()");
    $revenueStmt->execute();
    $revenueToday = $revenueStmt->fetchColumn();

    // 2. Arrivals Today
    $arrivalsStmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE DATE(start_time) = CURDATE() AND status IN ('confirmed', 'ongoing', 'completed')");
    $arrivalsStmt->execute();
    $arrivalsToday = $arrivalsStmt->fetchColumn();

    // 3. Active Parking (Now)
    $activeNow = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'ongoing'")->fetchColumn();

    // 4. Overstaying Vehicles
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

    echo json_encode([
        'success' => true,
        'revenue' => number_format($revenueToday, 2),
        'arrivals' => $arrivalsToday,
        'active' => $activeNow,
        'overstay_count' => count($overstays),
        'overstays' => $overstays
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
