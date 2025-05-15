<?php
require_once '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate and sanitize input
$first_name = filter_var($_POST['first_name'], FILTER_SANITIZE_STRING);
$last_name = filter_var($_POST['last_name'], FILTER_SANITIZE_STRING);
$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
$password = $_POST['password'];
$user_type = filter_var($_POST['user_type'], FILTER_SANITIZE_STRING);

// Validate required fields
if (!$first_name || !$last_name || !$email || !$password || !$user_type) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Validate user type
$valid_types = ['client', 'staff', 'admin'];
if (!in_array($user_type, $valid_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid user type']);
    exit;
}

try {
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit;
    }

    // Insert new user
    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, user_type) VALUES (?, ?, ?, ?, ?)");
    $result = $stmt->execute([$first_name, $last_name, $email, $password, $user_type]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'User added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add user']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
