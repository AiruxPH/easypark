<?php
// update_reservation_status.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'client') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['reservation_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}
require_once 'includes/db.php';
$reservation_id = intval($_POST['reservation_id']);
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'cancel') {
    // Allow cancel if status is pending or confirmed
    $stmt = $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE reservation_id = ? AND status IN ('pending', 'confirmed')");
    $stmt->execute([$reservation_id]);
    if ($stmt->rowCount() > 0) {
        // Free up the slot if no other active reservation exists for it
        $stmt = $pdo->prepare("SELECT parking_slot_id FROM reservations WHERE reservation_id = ?");
        $stmt->execute([$reservation_id]);
        $slot_id = $stmt->fetchColumn();
        if ($slot_id) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE parking_slot_id = ? AND status IN ('pending', 'confirmed', 'ongoing') AND reservation_id != ?");
            $stmt->execute([$slot_id, $reservation_id]);
            $active_count = $stmt->fetchColumn();
            if ($active_count == 0) {
                $stmt = $pdo->prepare("UPDATE parking_slots SET slot_status = 'available' WHERE parking_slot_id = ?");
                $stmt->execute([$slot_id]);
            }
        }
        echo json_encode(['success' => true, 'message' => 'Booking cancelled.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Unable to cancel booking.']);
    }
    exit();
} elseif ($action === 'complete') {
    // Allow complete if status is ongoing
    $stmt = $pdo->prepare("UPDATE reservations SET status = 'completed' WHERE reservation_id = ? AND status = 'ongoing'");
    $stmt->execute([$reservation_id]);
    if ($stmt->rowCount() > 0) {
        // Free up the slot if no other active reservation exists for it
        $stmt = $pdo->prepare("SELECT parking_slot_id FROM reservations WHERE reservation_id = ?");
        $stmt->execute([$reservation_id]);
        $slot_id = $stmt->fetchColumn();
        if ($slot_id) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE parking_slot_id = ? AND status IN ('pending', 'confirmed', 'ongoing') AND reservation_id != ?");
            $stmt->execute([$slot_id, $reservation_id]);
            $active_count = $stmt->fetchColumn();
            if ($active_count == 0) {
                $stmt = $pdo->prepare("UPDATE parking_slots SET slot_status = 'available' WHERE parking_slot_id = ?");
                $stmt->execute([$slot_id]);
            }
        }
        echo json_encode(['success' => true, 'message' => 'Booking marked as complete.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Unable to complete booking.']);
    }
    exit();
} else {
    // Fallback: original logic (for timer expiry)
    $stmt = $pdo->prepare("UPDATE reservations SET status = 'completed' WHERE reservation_id = ? AND status IN ('confirmed', 'ongoing') AND end_time <= NOW()");
    $stmt->execute([$reservation_id]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No update made']);
    }
    exit();
}
