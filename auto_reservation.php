<?php
include 'db.php';

$logFile = __DIR__ . '/cron_log.txt';
$log = "[" . date('Y-m-d H:i:s') . "] Cron job started.\n";

try {
    $now = date('Y-m-d H:i:s');

    // Cancel no-show reservations
    $stmt_cancel = $pdo->prepare("
        UPDATE reservations
        SET status = 'cancelled'
        WHERE start_time < :now AND status = 'pending'
    ");
    $stmt_cancel->execute(['now' => $now]);
    $log .= "Cancelled reservations: " . $stmt_cancel->rowCount() . "\n";

    // Expire outdated reservations
    $stmt_expire = $pdo->prepare("
        UPDATE reservations
        SET status = 'expired'
        WHERE end_time < :now AND status IN ('pending', 'confirmed')
    ");
    $stmt_expire->execute(['now' => $now]);
    $log .= "Expired reservations: " . $stmt_expire->rowCount() . "\n";

    // Complete finished ongoing reservations
    $stmt_complete = $pdo->prepare("
        UPDATE reservations
        SET status = 'completed'
        WHERE end_time < :now AND status = 'ongoing'
    ");
    $stmt_complete->execute(['now' => $now]);
    $log .= "Completed reservations: " . $stmt_complete->rowCount() . "\n";

    // Update payments: refunded
    $stmt_refund = $pdo->prepare("
        UPDATE payments
        SET status = 'refunded'
        WHERE reservation_id IN (
            SELECT reservation_id FROM reservations
            WHERE status IN ('cancelled', 'expired')
        ) AND status != 'refunded'
    ");
    $stmt_refund->execute();
    $log .= "Refunded payments: " . $stmt_refund->rowCount() . "\n";

    // Update payments: successful
    $stmt_success = $pdo->prepare("
        UPDATE payments
        SET status = 'successful'
        WHERE reservation_id IN (
            SELECT reservation_id FROM reservations
            WHERE status = 'completed'
        ) AND status != 'successful'
    ");
    $stmt_success->execute();
    $log .= "Successful payments: " . $stmt_success->rowCount() . "\n";

    // Free up parking slots
    $stmt_free = $pdo->prepare("
        UPDATE parking_slots
        SET slot_status = 'available'
        WHERE parking_slot_id IN (
            SELECT r.parking_slot_id
            FROM reservations r
            WHERE r.end_time < :now
              AND r.status IN ('expired', 'completed', 'cancelled')
        )
        AND parking_slot_id NOT IN (
            SELECT parking_slot_id FROM reservations
            WHERE status IN ('pending', 'confirmed', 'ongoing')
        )
    ");
    $stmt_free->execute(['now' => $now]);
    $log .= "Freed parking slots: " . $stmt_free->rowCount() . "\n";

    $log .= "Cron job finished successfully.\n\n";
} catch (PDOException $e) {
    $log .= "Error: " . $e->getMessage() . "\n\n";
}

// Write to log file
file_put_contents($logFile, $log, FILE_APPEND);
?>
