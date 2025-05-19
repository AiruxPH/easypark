<?php
include 'db.php'; // your PDO connection file

try {
    $now = date('Y-m-d H:i:s');

    // 1. Expire reservations that are still pending or confirmed
    $stmt1 = $pdo->prepare("
        UPDATE reservations
        SET status = 'expired'
        WHERE end_time < :now AND status IN ('pending', 'confirmed')
    ");
    $stmt1->execute(['now' => $now]);

    // 2. Complete reservations that were ongoing
    $stmt2 = $pdo->prepare("
        UPDATE reservations
        SET status = 'completed'
        WHERE end_time < :now AND status = 'ongoing'
    ");
    $stmt2->execute(['now' => $now]);

    // 3. Free parking slots not currently used in any active reservation
    // (pending, confirmed, ongoing are still "in use")
    $stmt3 = $pdo->prepare("
        UPDATE parking_slots
        SET slot_status = 'available'
        WHERE parking_slot_id IN (
            SELECT r.parking_slot_id
            FROM reservations r
            WHERE r.end_time < :now
              AND r.status IN ('expired', 'completed')
        )
        AND parking_slot_id NOT IN (
            SELECT parking_slot_id FROM reservations
            WHERE status IN ('pending', 'confirmed', 'ongoing')
        )
    ");
    $stmt3->execute(['now' => $now]);

    echo "Reservations and parking slots updated.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
