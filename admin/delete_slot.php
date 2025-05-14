<?php
require_once '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get and decode JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'No ID provided']);
    exit;
}

$id = filter_var($data['id'], FILTER_VALIDATE_INT);
if ($id === false) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

try {
    // Check if the slot exists and is not occupied
    $stmt = $pdo->prepare("SELECT slot_status FROM parking_slots WHERE parking_slot_id = ?");
    $stmt->execute([$id]);
    $slot = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$slot) {
        echo json_encode(['success' => false, 'message' => 'Slot not found']);
        exit;
    }
    
    if ($slot['slot_status'] === 'occupied') {
        echo json_encode(['success' => false, 'message' => 'Cannot delete an occupied slot']);
        exit;
    }
    
    // Delete the slot
    $stmt = $pdo->prepare("DELETE FROM parking_slots WHERE parking_slot_id = ?");
    $result = $stmt->execute([$id]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Slot deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete slot']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
