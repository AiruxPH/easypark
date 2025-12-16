<?php
// includes/functions.php

/**
 * Logs a user activity to the database.
 * 
 * @param PDO $pdo The database connection object.
 * @param int|null $userId The ID of the user performing the action (NULL if unknown/system).
 * @param string $userType The type of user ('admin', 'staff', 'client').
 * @param string $action Short description of the action (e.g., 'login', 'reservation_created').
 * @param string|null $details Detailed description of the action.
 * @return void
 */
function logActivity($pdo, $userId, $userType, $action, $details = null)
{
    try {
        // Capture IP address
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

        // Prepare SQL statement
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, user_type, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, ?, NOW())");

        // Execute
        $stmt->execute([
            $userId,
            $userType,
            $action,
            $details,
            $ipAddress
        ]);
    } catch (PDOException $e) {
        // Silently fail or log to file system to avoid breaking the main flow
        error_log("Failed to log activity: " . $e->getMessage());
    }
}
?>