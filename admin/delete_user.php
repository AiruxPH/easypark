<?php
require_once '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get and decode JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No user ID provided']);
    exit;
}

$user_id = filter_var($data['user_id'], FILTER_VALIDATE_INT);
if ($user_id === false) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

try {
    // Check if user exists and is not the last admin
    $stmt = $pdo->prepare("SELECT user_type FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    if ($user['user_type'] === 'admin') {
        // Count remaining admins
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'admin'");
        if ($stmt->fetchColumn() <= 1) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete the last admin user']);
            exit;
        }
    }
    
    // Delete user
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $result = $stmt->execute([$user_id]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
