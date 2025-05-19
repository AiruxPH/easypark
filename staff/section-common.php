<?php
session_start();
require_once '../db.php';

// Fetch staff profile info (if needed)
$staff_id = $_SESSION['user_id'] ?? null;
if ($staff_id) {
    $stmt = $pdo->prepare('SELECT first_name, middle_name, last_name, email, phone, image FROM users WHERE user_id = ?');
    $stmt->execute([$staff_id]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch only expected bookings (pending and upcoming)
$sql = "SELECT r.reservation_id, r.status, r.start_time, r.end_time, r.duration, s.slot_number, s.slot_type, v.plate_number, m.brand, m.model, u.first_name, u.last_name
FROM reservations r
JOIN parking_slots s ON r.parking_slot_id = s.parking_slot_id
JOIN vehicles v ON r.vehicle_id = v.vehicle_id
JOIN Vehicle_Models m ON v.model_id = m.model_id
JOIN users u ON r.user_id = u.user_id
WHERE r.status = 'pending' AND r.start_time >= NOW()
ORDER BY r.start_time ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pagination settings
$per_page = 6;
// Active Reservations Pagination
$active_page = isset($_GET['active_page']) ? max(1, intval($_GET['active_page'])) : 1;
$active_offset = ($active_page - 1) * $per_page;
$sql_active_count = "SELECT COUNT(*) FROM reservations WHERE status IN ('confirmed', 'ongoing')";
$active_total = $pdo->query($sql_active_count)->fetchColumn();
$sql_active = "SELECT r.reservation_id, r.status, r.start_time, r.end_time, r.duration, s.slot_number, s.slot_type, v.plate_number, m.brand, m.model, u.first_name, u.last_name
FROM reservations r
JOIN parking_slots s ON r.parking_slot_id = s.parking_slot_id
JOIN vehicles v ON r.vehicle_id = v.vehicle_id
JOIN Vehicle_Models m ON v.model_id = m.model_id
JOIN users u ON r.user_id = u.user_id
WHERE r.status IN ('confirmed', 'ongoing')
ORDER BY r.start_time ASC LIMIT $per_page OFFSET $active_offset";
$stmt = $pdo->prepare($sql_active);
$stmt->execute();
$active_reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
$active_total_pages = ceil($active_total / $per_page);

// History Pagination
$history_page = isset($_GET['history_page']) ? max(1, intval($_GET['history_page'])) : 1;
$history_offset = ($history_page - 1) * $per_page;
$sql_history_count = "SELECT COUNT(*) FROM reservations WHERE status IN ('completed', 'cancelled')";
$history_total = $pdo->query($sql_history_count)->fetchColumn();
$sql_history = "SELECT r.reservation_id, r.status, r.start_time, r.end_time, r.duration, s.slot_number, s.slot_type, v.plate_number, m.brand, m.model, u.first_name, u.last_name
FROM reservations r
JOIN parking_slots s ON r.parking_slot_id = s.parking_slot_id
JOIN vehicles v ON r.vehicle_id = v.vehicle_id
JOIN Vehicle_Models m ON v.model_id = m.model_id
JOIN users u ON r.user_id = u.user_id
WHERE r.status IN ('completed', 'cancelled')
ORDER BY r.end_time DESC LIMIT $per_page OFFSET $history_offset";
$stmt = $pdo->prepare($sql_history);
$stmt->execute();
$history_reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
$history_total_pages = ceil($history_total / $per_page);

// Parking Slots Pagination
$slots_page = isset($_GET['slots_page']) ? max(1, intval($_GET['slots_page'])) : 1;
$slots_offset = ($slots_page - 1) * $per_page;
$sql_slots_count = "SELECT COUNT(*) FROM parking_slots";
$slots_total = $pdo->query($sql_slots_count)->fetchColumn();
$sql_slots = "SELECT * FROM parking_slots ORDER BY slot_number ASC LIMIT $per_page OFFSET $slots_offset";
$stmt = $pdo->prepare($sql_slots);
$stmt->execute();
$all_slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
$slots_total_pages = ceil($slots_total / $per_page);

// Helper for slot color class
function getSlotColorClass($status) {
    switch (strtolower($status)) {
        case 'available': return 'border-success';
        case 'reserved': return 'border-warning';
        case 'occupied': return 'border-danger';
        default: return 'border-secondary';
    }
}

// Helper for pagination range
function getPaginationRange($current, $total, $max = 5) {
    $start = max(1, $current - floor($max/2));
    $end = min($total, $start + $max - 1);
    if ($end - $start + 1 < $max) {
        $start = max(1, $end - $max + 1);
    }
    return [$start, $end];
}
