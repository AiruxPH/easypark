<?php
// staff/reset_password_form.php
require_once '../db.php';
$token = $_GET['token'] ?? '';
$show_form = false;
$message = '';
if ($token) {
    $stmt = $pdo->prepare('SELECT user_id, reset_token_expires FROM users WHERE reset_token = ? AND user_type = "staff"');
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && strtotime($user['reset_token_expires']) > time()) {
        $show_form = true;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $new = $_POST['new_password'] ?? '';
            $confirm = $_POST['confirm_new_password'] ?? '';
            if (!$new || !$confirm) {
                $message = '<div class="text-danger">Please fill all fields.</div>';
            } elseif ($new !== $confirm) {
                $message = '<div class="text-danger">Passwords do not match.</div>';
            } else {
                $hashed = password_hash($new, PASSWORD_DEFAULT);
                $pdo->prepare('UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE user_id = ?')
                    ->execute([$hashed, $user['user_id']]);
                $message = '<div class="text-success">Password reset successful! You may now <a href="../login.php">login</a>.</div>';
                $show_form = false;
            }
        }
    } else {
        $message = '<div class="text-danger">Invalid or expired reset link.</div>';
    }
} else {
    $message = '<div class="text-danger">No reset token provided.</div>';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Reset Password | EasyPark</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/font-awesome.min.css">
    <link rel="icon" href="../images/favicon.png" type="image/png">
    <style>
        body {
            background: #232526;
            min-height: 100vh;
            color: #fff;
        }

        .reset-container {
            background: #2c2f33;
            border-radius: 1rem;
            padding: 2rem;
            max-width: 400px;
            margin: 5vh auto;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.12);
        }
    </style>
</head>

<body>
    <div class="reset-container">
        <h3 class="text-warning mb-3"><i class="fa fa-unlock-alt"></i> Reset Password</h3>
        <?php echo $message; ?>
        <?php if ($show_form): ?>
            <form method="POST">
                <div class="form-group mb-3">
                    <label>New Password</label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
                <div class="form-group mb-3">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_new_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-warning w-100">Reset Password</button>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>