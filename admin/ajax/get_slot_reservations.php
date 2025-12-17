<?php
// admin/ajax/get_slot_reservations.php
session_start();
require_once '../../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'staff')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['slot_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing slot_id']);
    exit;
}

$slot_id = intval($_GET['slot_id']);

try {
    // Fetch Pending and Confirmed reservations for this slot
    // We want to show them so admin can select one to "Confirm" (if pending) or "Occupied" (if confirmed)
    $stmt = $pdo->prepare("
        SELECT 
            r.reservation_id, 
            r.user_id, 
            r.start_time, 
            r.end_time, 
            r.status,
            u.first_name, 
            u.last_name, 
            v.plate_number 
        FROM reservations r 
        JOIN users u ON r.user_id = u.user_id 
        JOIN vehicles v ON r.vehicle_id = v.vehicle_id 
        WHERE r.parking_slot_id = ? 
        AND r.status IN ('pending', 'confirmed') 
        ORDER BY r.created_at ASC
    ");
    $stmt->execute([$slot_id]);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $reservations]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>