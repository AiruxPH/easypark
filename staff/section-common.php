<?php
/**
 * section-common.php
 *
 * Shared backend logic for the staff dashboard sections (bookings, active, history, slots, etc.).
 *
 * This file centralizes all data-fetching, pagination, and helper functions needed by the staff dashboard.
 * It should be included at the top of any section or AJAX file that needs access to reservation, slot, or pagination data.
 *
 * Purpose:
 * - Eliminate code duplication between staff-dashboard.php and section files
 * - Ensure consistency and maintainability for all staff dashboard data
 * - Used by: section-bookings.php, section-active.php, section-history.php, section-slots.php, and any AJAX endpoints
 *
 * Do NOT put any HTML output in this file. Only backend logic and helpers.
 */

session_start();
require_once '../includes/db.php';
require_once '../includes/constants.php';

// Fetch staff profile info (if needed)
$staff_id = $_SESSION['user_id'] ?? null;
if ($staff_id) {
    $stmt = $pdo->prepare('SELECT first_name, middle_name, last_name, email, phone, image FROM users WHERE user_id = ?');
    $stmt->execute([$staff_id]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Helper to get safe filter params
$search = trim($_GET['search'] ?? '');
$filter_status = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$filter_type = $_GET['type'] ?? '';

// --- 1. BOOKINGS SECTION (Pending) ---
$bookings_where = ["r.status = 'pending'"];
$bookings_params = [];
if ($search) {
    $bookings_where[] = "(v.plate_number LIKE :b_search OR u.first_name LIKE :b_search OR u.last_name LIKE :b_search OR r.reservation_id LIKE :b_search)";
    $bookings_params[':b_search'] = "%$search%";
}
if ($date_from) {
    $bookings_where[] = "DATE(r.start_time) >= :b_date_from";
    $bookings_params[':b_date_from'] = $date_from;
}
if ($date_to) {
    $bookings_where[] = "DATE(r.start_time) <= :b_date_to";
    $bookings_params[':b_date_to'] = $date_to;
}
$bookings_sql_where = implode(' AND ', $bookings_where);

// For bookings we usually show all (or paginate if needed, but assuming list is short). 
// Let's stick to simple list for now as per original, or add basic pagination if list gets long.
// Original code didn't paginate bookings, just fetchAll.
$sql = "SELECT r.reservation_id, r.status, r.start_time, r.end_time, r.duration, s.slot_number, s.slot_type, v.plate_number, m.brand, m.model, u.first_name, u.last_name
FROM reservations r
JOIN parking_slots s ON r.parking_slot_id = s.parking_slot_id
JOIN vehicles v ON r.vehicle_id = v.vehicle_id
JOIN Vehicle_Models m ON v.model_id = m.model_id
JOIN users u ON r.user_id = u.user_id
WHERE $bookings_sql_where
ORDER BY r.start_time ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($bookings_params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);


// --- 2. ACTIVE RESERVATIONS SECTION (Confirmed/Ongoing) ---
$per_page = 6;
$active_page = isset($_GET['active_page']) ? max(1, intval($_GET['active_page'])) : 1;
$active_offset = ($active_page - 1) * $per_page;

$active_where = ["r.status IN ('confirmed', 'ongoing')"];
$active_params = [];

// Only apply filters if we are possibly in the active section (context separation)
// Ideally we'd separate endpoints, but for now we share common.php.
// To avoid conflicts if searching in one tab affecting others, we can prefix params in JS or just share matching logic.
// The user request implies simple filtering. If I search "John", I likely want to see "John" in whatever active list I'm looking at.
if ($search) {
    $active_where[] = "(v.plate_number LIKE :a_search OR u.first_name LIKE :a_search OR u.last_name LIKE :a_search OR r.reservation_id LIKE :a_search)";
    $active_params[':a_search'] = "%$search%";
}
if ($filter_status && in_array($filter_status, ['confirmed', 'ongoing'])) {
    $active_where[] = "r.status = :a_status";
    $active_params[':a_status'] = $filter_status;
}
if ($date_from) {
    $active_where[] = "DATE(r.start_time) >= :a_date_from";
    $active_params[':a_date_from'] = $date_from;
}
if ($date_to) {
    $active_where[] = "DATE(r.start_time) <= :a_date_to";
    $active_params[':a_date_to'] = $date_to;
}
$active_sql_where = implode(' AND ', $active_where);

// Count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations r JOIN users u ON r.user_id = u.user_id JOIN vehicles v ON r.vehicle_id = v.vehicle_id WHERE $active_sql_where");
$stmt->execute($active_params);
$active_total = $stmt->fetchColumn();

// Fetch
$sql_active = "SELECT r.reservation_id, r.status, r.start_time, r.end_time, r.duration, s.slot_number, s.slot_type, v.plate_number, m.brand, m.model, u.first_name, u.last_name, u.coins
FROM reservations r
JOIN parking_slots s ON r.parking_slot_id = s.parking_slot_id
JOIN vehicles v ON r.vehicle_id = v.vehicle_id
JOIN Vehicle_Models m ON v.model_id = m.model_id
JOIN users u ON r.user_id = u.user_id
WHERE $active_sql_where
ORDER BY r.start_time ASC LIMIT $per_page OFFSET $active_offset";
$stmt = $pdo->prepare($sql_active);
foreach ($active_params as $k => $v)
    $stmt->bindValue($k, $v);
$stmt->execute();
$active_reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
$active_total_pages = ceil($active_total / $per_page);


// --- 3. HISTORY SECTION (Completed/Cancelled/Expired) ---
$history_page = isset($_GET['history_page']) ? max(1, intval($_GET['history_page'])) : 1;
$history_offset = ($history_page - 1) * $per_page;

$history_where = ["r.status IN ('completed', 'cancelled', 'expired', 'void')"];
$history_params = [];

if ($search) {
    $history_where[] = "(v.plate_number LIKE :h_search OR u.first_name LIKE :h_search OR u.last_name LIKE :h_search OR r.reservation_id LIKE :h_search)";
    $history_params[':h_search'] = "%$search%";
}
if ($filter_status && in_array($filter_status, ['completed', 'cancelled', 'expired', 'void'])) {
    $history_where[] = "r.status = :h_status";
    $history_params[':h_status'] = $filter_status;
}
if ($date_from) {
    $history_where[] = "DATE(r.start_time) >= :h_date_from";
    $history_params[':h_date_from'] = $date_from;
}
if ($date_to) {
    $history_where[] = "DATE(r.start_time) <= :h_date_to";
    $history_params[':h_date_to'] = $date_to;
}
$history_sql_where = implode(' AND ', $history_where);

// Count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations r JOIN users u ON r.user_id = u.user_id JOIN vehicles v ON r.vehicle_id = v.vehicle_id WHERE $history_sql_where");
$stmt->execute($history_params);
$history_total = $stmt->fetchColumn();

// Fetch
$sql_history = "SELECT r.reservation_id, r.status, r.start_time, r.end_time, r.duration, s.slot_number, s.slot_type, v.plate_number, m.brand, m.model, u.first_name, u.last_name
FROM reservations r
JOIN parking_slots s ON r.parking_slot_id = s.parking_slot_id
JOIN vehicles v ON r.vehicle_id = v.vehicle_id
JOIN Vehicle_Models m ON v.model_id = m.model_id
JOIN users u ON r.user_id = u.user_id
WHERE $history_sql_where
ORDER BY r.end_time DESC LIMIT $per_page OFFSET $history_offset";
$stmt = $pdo->prepare($sql_history);
foreach ($history_params as $k => $v)
    $stmt->bindValue($k, $v);
$stmt->execute();
$history_reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
$history_total_pages = ceil($history_total / $per_page);


// --- 4. PARKING SLOTS SECTION ---
$slots_page = isset($_GET['slots_page']) ? max(1, intval($_GET['slots_page'])) : 1;
$slots_offset = ($slots_page - 1) * $per_page;

$slots_where = ["1=1"];
$slots_params = [];

if ($search) {
    // Basic search on slot number + basic types
    // Since we join, we might want to search owner name too? For now keep it simple to match original intent or enhance.
    // Original only searched slot_number. Let's keep it safe or enhance if desired.
    // Matching existing behavior:
    $slots_where[] = "parking_slots.slot_number LIKE :s_search";
    $slots_params[':s_search'] = "%$search%";
}
if ($filter_type) {
    $slots_where[] = "parking_slots.slot_type = :s_type";
    $slots_params[':s_type'] = $filter_type;
}
// Note: section-slots.php uses 'status' input for status. Checked above.
if ($filter_status && in_array($filter_status, ['available', 'reserved', 'occupied', 'unavailable'])) {
    $slots_where[] = "parking_slots.slot_status = :s_status";
    $slots_params[':s_status'] = $filter_status;
}
$slots_sql_where = implode(' AND ', $slots_where);

// Count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM parking_slots WHERE $slots_sql_where");
$stmt->execute($slots_params);
$slots_total = $stmt->fetchColumn();

// Fetch
$sql_slots = "SELECT 
                parking_slots.*,
                v.plate_number,
                CONCAT(u.first_name, ' ', u.last_name) as owner_name,
                r.start_time,
                r.end_time
              FROM parking_slots 
              LEFT JOIN reservations r ON parking_slots.parking_slot_id = r.parking_slot_id 
                  AND r.status IN ('confirmed', 'ongoing') 
                  AND (r.status = 'ongoing' OR (r.start_time <= NOW() AND r.end_time >= NOW()))
              LEFT JOIN vehicles v ON r.vehicle_id = v.vehicle_id 
              LEFT JOIN users u ON r.user_id = u.user_id
              WHERE $slots_sql_where 
              ORDER BY parking_slots.slot_number ASC 
              LIMIT $per_page OFFSET $slots_offset";
$stmt = $pdo->prepare($sql_slots);
foreach ($slots_params as $k => $v)
    $stmt->bindValue($k, $v);
$stmt->execute();
$all_slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
$slots_total_pages = ceil($slots_total / $per_page);

// Helper for slot color class
// Helper for slot color class
function getSlotColorClass($status)
{
    switch (strtolower($status)) {
        case 'available':
            return 'border-success';
        case 'reserved':
            return 'border-warning';
        case 'occupied':
            return 'border-danger';
        case 'unavailable':
            return 'border-secondary';
        default:
            return 'border-secondary';
    }
}

// Helper for status badge class
function getBadgeClass($status)
{
    switch (strtolower($status)) {
        case 'confirmed':
        case 'completed':
        case 'available':
            return 'badge badge-success';
        case 'pending':
        case 'reserved':
            return 'badge badge-warning';
        case 'occupied':
        case 'cancelled':
        case 'expired':
        case 'void':
            return 'badge badge-danger';
        case 'ongoing':
            return 'badge badge-primary';
        case 'unavailable':
        case 'maintenance':
            return 'badge badge-secondary';
        default:
            return 'badge badge-secondary';
    }
}

// Helper for pagination range
function getPaginationRange($current, $total, $max = 5)
{
    $start = max(1, $current - floor($max / 2));
    $end = min($total, $start + $max - 1);
    if ($end - $start + 1 < $max) {
        $start = max(1, $end - $max + 1);
    }
    return [$start, $end];
}
