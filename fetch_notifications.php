<?php
// fetch_notifications.php
session_start();
require_once 'includes/db.php';
require_once 'includes/notifications.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 5;

try {
    // 1. Get Unread Count
    $unreadCount = countUnreadNotifications($pdo, $user_id);

    // 2. Get Recent Notifications
    // We might want to fetch slightly more if some are read, but usually the dropdown shows recent ones.
    // Let's reuse the existing function or custom query.
    // The existing function `getUnreadNotifications` only gets UNREAD.
    // However, the dropdown usually shows a mix of recent history (read/unread).
    // Let's create a custom query here for "Recent" regardless of read status, 
    // BUT prioritize unread.

    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'unread_count' => $unreadCount,
        'notifications' => $notifications
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>