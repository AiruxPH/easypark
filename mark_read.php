<?php
// mark_read.php
session_start();
require_once 'includes/db.php'; // Adjust path if needed

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Optional: Mark specific ID if provided, otherwise mark all unread shown
    // For this simple implementation, we assume clicking the bell marks recent ones as read,
    // OR clicking a specific item marks it. Let's support marking specific ID.

    $input = json_decode(file_get_contents('php://input'), true);
    $notification_id = $input['notification_id'] ?? null;

    if ($notification_id) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?");
        $stmt->execute([$notification_id, $user_id]);
    } else {
        // Mark all unread for this user (e.g. "Mark All Read" button)
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$user_id]);
    }

    echo json_encode(['success' => true]);
}
?>