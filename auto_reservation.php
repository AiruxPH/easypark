<?php
include 'db.php'; // your PDO connection file

try {
    $now = date('Y-m-d H:i:s');

    // 1. Cancel pending reservations that missed their start time (no-show)
    $stmt_cancel = $pdo->prepare("
        UPDATE reservations
        SET status = 'cancelled'
        WHERE start_time < :now AND status = 'pending'
    ");
    $stmt_cancel->execute(['now' => $now]);

    // 2. Expire reservations that ended but never completed (pending or confirmed)
    $stmt_expire = $pdo->prepare("
        UPDATE reservations
        SET status = 'expired'
        WHERE end_time < :now AND status IN ('pending', 'confirmed')
    ");
    $stmt_expire->execute(['now' => $now]);

    // 3. Mark ongoing reservations as completed if their end_time has passed
    $stmt_complete = $pdo->prepare("
        UPDATE reservations
        SET status = 'completed'
        WHERE end_time < :now AND status = 'ongoing'
    ");
    $stmt_complete->execute(['now' => $now]);

    // 4. Free up parking slots that are no longer in use (expired, cancelled, completed)
    $stmt_free_slots = $pdo->prepare("
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
    $stmt_free_slots->execute(['now' => $now]);

    echo "Reservation cleanup complete.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
