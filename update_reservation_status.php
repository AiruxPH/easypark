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
// Only allow updating if the reservation is confirmed, ongoing, and end_time has passed
$stmt = $pdo->prepare("UPDATE reservations SET status = 'completed' WHERE reservation_id = ? AND status IN ('confirmed', 'ongoing') AND end_time <= NOW()");
$stmt->execute([$reservation_id]);
if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'No update made']);
}
