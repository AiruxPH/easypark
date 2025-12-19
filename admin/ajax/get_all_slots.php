<?php
// admin/ajax/get_all_slots.php
require_once '../../includes/db.php';

header('Content-Type: application/json');

try {
    // Fetch all slots with current reservation info if occupied/reserved
    $sql = "
        SELECT 
            ps.*,
            v.plate_number,
            CONCAT(u.first_name, ' ', u.last_name) as owner_name,
            r.start_time,
            r.end_time
        FROM parking_slots ps
        LEFT JOIN reservations r ON ps.parking_slot_id = r.parking_slot_id 
            AND r.status IN ('confirmed', 'ongoing')
            AND (
                r.status = 'ongoing' 
                OR 
                (r.start_time <= NOW() AND r.end_time >= NOW())
            )
        LEFT JOIN vehicles v ON r.vehicle_id = v.vehicle_id
        LEFT JOIN users u ON r.user_id = u.user_id
        ORDER BY ps.slot_number ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'slots' => $slots
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
