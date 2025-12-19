<?php
// map_experiment/api.php
require_once '../includes/db.php';

// Fetch ALL slots (no pagination) to map them effectively
$sql = "
    SELECT 
        ps.parking_slot_id,
        ps.slot_number,
        ps.slot_status, 
        ps.slot_type,
        v.plate_number
    FROM parking_slots ps
    LEFT JOIN reservations r ON ps.parking_slot_id = r.parking_slot_id 
        AND r.status = 'ongoing'
    LEFT JOIN vehicles v ON r.vehicle_id = v.vehicle_id
";

$stmt = $pdo->query($sql);
$slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Return JSON
header('Content-Type: application/json');
echo json_encode($slots);
