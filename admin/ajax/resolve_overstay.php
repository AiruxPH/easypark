<?php
// admin/ajax/resolve_overstay.php
require_once '../../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$reservation_id = $_POST['reservation_id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$reservation_id || !$action) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

try {
    if ($action === 'complete') {
        // Force Complete: Free the slot immediately
        $stmt = $pdo->prepare("UPDATE reservations SET status = 'completed' WHERE reservation_id = ?");
        $stmt->execute([$reservation_id]);

        // Note: Slot status in `parking_slots` table might need update if it was 'occupied' purely by this reservation.
        // However, our system now relies on active reservations to determine "Reserved" status, 
        // but 'occupied' is a physical flag. 
        // Ideally, if we force complete, we imply the car is GONE.
        // So let's also set the physical slot to 'available' to ensure consistency.

        // 1. Get slot ID
        $slotStmt = $pdo->prepare("SELECT parking_slot_id FROM reservations WHERE reservation_id = ?");
        $slotStmt->execute([$reservation_id]);
        $slotId = $slotStmt->fetchColumn();

        if ($slotId) {
            $pdo->prepare("UPDATE parking_slots SET slot_status = 'available' WHERE parking_slot_id = ?")->execute([$slotId]);
        }

        echo json_encode(['success' => true, 'message' => 'Reservation marked as completed. Slot freed.']);

    } elseif ($action === 'extend') {
        // Extend: Add 1 hour to end_time
        $stmt = $pdo->prepare("UPDATE reservations SET end_time = DATE_ADD(end_time, INTERVAL 1 HOUR) WHERE reservation_id = ?");
        $stmt->execute([$reservation_id]);

        echo json_encode(['success' => true, 'message' => 'Reservation extended by 1 hour.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
