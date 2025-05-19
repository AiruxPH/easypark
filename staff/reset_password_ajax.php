<?php
// staff/reset_password_ajax.php
require_once '../db.php';
header('Content-Type: text/html; charset=UTF-8');

$email = trim($_POST['email'] ?? '');
$security_word = trim($_POST['security_word'] ?? '');
$new_password = trim($_POST['new_password'] ?? '');

if (!$email || !$security_word || !$new_password) {
    echo '<div class="text-danger">Please fill in all fields.</div>';
    exit;
}
$stmt = $pdo->prepare('SELECT user_id, security_word FROM users WHERE email = ? AND user_type = "staff"');
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    echo '<div class="text-danger">No staff account found with that email.</div>';
    exit;
}
if (strcasecmp($user['security_word'], $security_word) !== 0) {
    echo '<div class="text-danger">Incorrect security word.</div>';
    exit;
}
// Update password directly (no hash, as requested)
$pdo->prepare('UPDATE users SET password = ? WHERE user_id = ?')
    ->execute([$new_password, $user['user_id']]);
echo '<div class="text-success">Password has been reset. You may now log in.</div>';
