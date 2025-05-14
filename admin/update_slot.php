<?php
require_once '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$parking_slot_id = filter_var($_POST['parking_slot_id'], FILTER_VALIDATE_INT);
$slot_number = filter_var($_POST['slot_number'], FILTER_SANITIZE_STRING);
$slot_type = filter_var($_POST['slot_type'], FILTER_SANITIZE_STRING);
$slot_status = filter_var($_POST['slot_status'], FILTER_SANITIZE_STRING);

// Validate inputs
if (!$parking_slot_id || !$slot_number || !$slot_type || !$slot_status) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit;
}

// Validate slot_type
$valid_types = ['two_wheeler', 'standard', 'compact'];
if (!in_array($slot_type, $valid_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid slot type']);
    exit;
}

// Validate slot_status
$valid_statuses = ['available', 'reserved', 'occupied'];
if (!in_array($slot_status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid slot status']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE parking_slots SET slot_number = ?, slot_type = ?, slot_status = ? WHERE parking_slot_id = ?");
    $result = $stmt->execute([$slot_number, $slot_type, $slot_status, $parking_slot_id]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Slot updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update slot']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
