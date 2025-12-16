<?php
session_start();

include 'includes/db.php';

$username = $_POST['username'];
$password = $_POST['password'];

$password_hashed = hash('sha256', $password);

$sql = "SELECT * FROM users WHERE username = ? AND password = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $username, $password_hashed);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $user = $result->fetch_assoc();
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_type'] = $user['user_type'];


    header("Location: /login_crud/pet_crud/index.php");
    exit();
} else {
    header("Location: index.php?error=1");
    exit();
}
?>