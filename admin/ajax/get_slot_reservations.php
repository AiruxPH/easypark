<?php
// admin/ajax/get_slot_reservations.php
session_start();
require_once '../../includes/db.php';

header('Content-Type: application/json');

// validation
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['slot_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing slot_id']);
    exit;
}

$slot_id = intval($_GET['slot_id']);

try {
    // Fetch active/pending/confirmed reservations for this slot
    // overlapping with NOW or slightly in the future/past (just get all current/pending candidates)
    // We strictly look for those that are RELEVANT NOW (start_time <= NOW <= end_time) OR pending check-in.

    // Logic: Find confirmed/ongoing reservations that allow the user to be there RIGHT NOW.
    $stmt = $pdo->prepare("
        SELECT 
            r.reservation_id, 
            r.start_time, 
            r.end_time, 
            u.first_name, 
            u.last_name, 
            v.plate_number, 
            vm.brand, 
            vm.model 
        FROM reservations r
        JOIN users u ON r.user_id = u.user_id
        JOIN vehicles v ON r.vehicle_id = v.vehicle_id
        JOIN Vehicle_Models vm ON v.model_id = vm.model_id
        WHERE r.parking_slot_id = ? 
        AND r.status IN ('confirmed', 'ongoing') 
        AND r.start_time <= NOW() 
        AND r.end_time > NOW()
        ORDER BY r.created_at ASC
    ");

    $stmt->execute([$slot_id]);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $reservations]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
