<?php
session_start();
include 'db.php';

$user_type = $_SESSION['user_type'] ?? '';

if (!$user_type) {
    http_response_code(403);
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$notif_stmt = $conn->prepare("SELECT id, message, created_at, is_read FROM notifications WHERE visibility = ? OR visibility = 'all' ORDER BY created_at DESC LIMIT 5");
$notif_stmt->bind_param("s", $user_type);
$notif_stmt->execute();
$result = $notif_stmt->get_result();

$notifications = [];
$unreadCount = 0;

while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        'id' => $row['id'],
        'message' => $row['message'],
        'time' => date("M d, H:i", strtotime($row['created_at'])),
        'is_read' => $row['is_read']
    ];

    if ($row['is_read'] == 0) {
        $unreadCount++;
    }
}

echo json_encode([
    'notifs' => $notifications,
    'unread_count' => $unreadCount
]);
?>
