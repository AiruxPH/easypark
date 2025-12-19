<?php
// includes/notifications.php

/**
 * Send a notification to a specific user.
 *
 * @param PDO $pdo
 * @param int $user_id
 * @param string $title
 * @param string $message
 * @param string $type enum('info','success','warning','error')
 * @param string|null $link Optional URL
 * @return bool
 */
function sendNotification($pdo, $user_id, $title, $message, $type = 'info', $link = null)
{
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, link, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())");
        return $stmt->execute([$user_id, $title, $message, $type, $link]);
    } catch (PDOException $e) {
        // Silently fail or log error to avoid crashing user flow
        error_log("Notification Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send a notification to all users of a specific role group.
 *
 * @param PDO $pdo
 * @param string $target_role 'admin', 'staff', 'client'
 * @param string $title
 * @param string $message
 * @param string $type
 * @param string|null $link
 * @return int Number of notifications sent
 */
function notifyGroup($pdo, $target_role, $title, $message, $type = 'info', $link = null)
{
    // 1. Get all user IDs of this role
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_type = ?");
    $stmt->execute([$target_role]);
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $count = 0;
    // 2. Loop and send
    foreach ($users as $uid) {
        if (sendNotification($pdo, $uid, $title, $message, $type, $link)) {
            $count++;
        }
    }
    return $count;
}

/**
 * Get recent notifications (read or unread) for dropdown.
 * 
 * @param PDO $pdo
 * @param int $user_id
 * @param int $limit
 * @return array
 */
function getRecentNotifications($pdo, $user_id, $limit = 5)
{
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    // Bind limit explicitly as INT for LIMIT clause to work in some PDO drivers
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Count unread notifications
 * 
 * @param PDO $pdo
 * @param int $user_id
 * @return int
 */
function countUnreadNotifications($pdo, $user_id)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    return (int) $stmt->fetchColumn();
}

/**
 * Get ALL notifications for a user (for the history page).
 * 
 * @param PDO $pdo
 * @param int $user_id
 * @return array
 */
function getAllNotifications($pdo, $user_id)
{
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>