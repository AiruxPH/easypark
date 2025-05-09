<?php
include 'db.php';

if (empty($_POST['username']) || empty($_POST['password'])) {
    header("Location: register.php?error=empty");
    exit();
}

$username = $_POST['username'];
$password = hash('sha256', $_POST['password']);
$user_type = "staff";

$sql_check = "SELECT user_id FROM users WHERE username = ?";
$stmt = $conn->prepare($sql_check);
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    header("Location: register.php?error=exists");
    exit();
}
$stmt->close();

$sql_insert = "INSERT INTO users (username, password, user_type) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql_insert);
$stmt->bind_param("sss", $username, $password, $user_type);

if ($stmt->execute()) {
    header("Location: register.php?success=1");
} else {
    header("Location: register.php?error=insert");
}
exit();
?>
