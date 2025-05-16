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
require_once 'db.php';
$reservation_id = intval($_POST['reservation_id']);
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'cancel') {
    // Allow cancel if status is confirmed or ongoing
    $stmt = $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE reservation_id = ? AND status IN ('confirmed', 'ongoing')");
    $stmt->execute([$reservation_id]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Booking cancelled.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Unable to cancel booking.']);
    }
    exit();
} elseif ($action === 'complete') {
    // Allow complete if status is confirmed or ongoing
    $stmt = $pdo->prepare("UPDATE reservations SET status = 'completed' WHERE reservation_id = ? AND status IN ('confirmed', 'ongoing')");
    $stmt->execute([$reservation_id]);
    if ($stmt->rowCount() > 0) {
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
