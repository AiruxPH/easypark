<?php
session_start();
include 'db.php';

$user_type = $_SESSION['user_type'] ?? '';

if (!$user_type) {
    http_response_code(403);
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$sql = "UPDATE notifications SET is_read = 1 WHERE (visibility = ? OR visibility = 'all')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_type);
$stmt->execute();

echo json_encode(['success' => true]);
?>
