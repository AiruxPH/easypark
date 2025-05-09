<?php
function addNotification($conn, $message, $visibility) {
    if (!isset($_SESSION)) {
        session_start();
    }

    $user_id = $_SESSION['user_id'] ?? null;

    if (!$user_id) return;

    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, visibility) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $message, $visibility);
    $stmt->execute();
    $stmt->close();
}
?>