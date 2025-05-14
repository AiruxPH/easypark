<?php
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'No ID provided']);
    exit;
}

$id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if ($id === false) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM parking_slots WHERE parking_slot_id = ?");
    $stmt->execute([$id]);
    $slot = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($slot) {
        echo json_encode($slot);
    } else {
        echo json_encode(['success' => false, 'message' => 'Slot not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
