<?php
require_once '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate and sanitize input
$user_id = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
$first_name = filter_var($_POST['first_name'], FILTER_SANITIZE_STRING);
$last_name = filter_var($_POST['last_name'], FILTER_SANITIZE_STRING);
$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
$password = $_POST['password'];
$user_type = filter_var($_POST['user_type'], FILTER_SANITIZE_STRING);

// Validate required fields
if (!$user_id || !$first_name || !$last_name || !$email || !$user_type) {
    echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
    exit;
}

// Validate user type
$valid_types = ['user', 'staff', 'admin'];
if (!in_array($user_type, $valid_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid user type']);
    exit;
}

try {
    // Check if email exists for other users
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND user_id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit;
    }

    // Update user
    if (!empty($password)) {
        // Update with new password
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, password = ?, user_type = ? WHERE user_id = ?");
        $result = $stmt->execute([$first_name, $last_name, $email, $password, $user_type, $user_id]);
    } else {
        // Update without changing password
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, user_type = ? WHERE user_id = ?");
        $result = $stmt->execute([$first_name, $last_name, $email, $user_type, $user_id]);
    }
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update user']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
