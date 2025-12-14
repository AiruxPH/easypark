<?php
session_start();
require_once 'includes/db.php';

$message = '';
$step = 1;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['step']) && $_POST['step'] == 1) {
    // Step 1: Verify email and security word
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $security_word = trim($_POST['security_word']);
    if (!$email) {
      $message = "⚠️ Invalid email address.";
    } elseif (empty($security_word)) {
      $message = "⚠️ Security word is required.";
    } else {
      $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND security_word = ?");
      $stmt->execute([$email, $security_word]);
      if ($stmt->rowCount() > 0) {
        $step = 2;
      } else {
        $message = "❌ Email or security word is incorrect.";
      }
    }
  } elseif (isset($_POST['step']) && $_POST['step'] == 2) {
    // Step 2: Set new password
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    if (!$email) {
      $message = "⚠️ Invalid email address.";
      $step = 1;
    } elseif (empty($new_password) || empty($confirm_password)) {
      $message = "⚠️ Please enter and confirm your new password.";
      $step = 2;
    } elseif ($new_password !== $confirm_password) {
      $message = "❌ Passwords do not match.";
      $step = 2;
    } else {
      // Update password (plain, for demo only)
      $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
      $stmt->execute([$new_password, $email]);
      $message = "✅ Password reset successful! You can now <a href='login.php'>login</a>.";
      $step = 3;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Forgot Password - EASYPARK</title>
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/font-awesome.min.css" />
  <style>
    body {
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      background-color: rgba(0, 0, 0, 0.5);
      position: relative;
      overflow: hidden;
    }

    .bg-image {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      opacity: 0.3;
      z-index: -1;
    }

    .form-control:focus {
      border-color: #ffc107;
      box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
    }

    .btn-warning {
      background-color: #ffc107;
      border-color: #ffc107;
    }

    .btn-warning:hover {
      background-color: #6610f2;
      border-color: #6610f2;
    }
  </style>
</head>

<body class="d-flex align-items-center justify-content-center p-4">
  <img class="bg-image" src="bg-car.jpg" alt="parking bg" />
  <div class="card bg-white shadow-lg p-4" style="max-width: 400px; background: rgba(255, 255, 255, 0.9) !important;">
    <div class="text-center mb-4">
      <h1 class="display-5 font-weight-bold text-warning">Forgot Password</h1>
      <p class="text-muted small">Reset your EASYPARK account password</p>
    </div>
    <?php if ($message): ?>
      <div class="alert alert-warning small mb-4"><?= $message ?></div>
    <?php endif; ?>
    <?php if ($step == 1): ?>
      <form action="forgot_password.php" method="POST">
        <input type="hidden" name="step" value="1">
        <div class="form-group">
          <label for="email" class="small font-weight-bold text-muted">
            Email Address <span class="text-danger">*</span>
          </label>
          <input type="email" name="email" id="email" required placeholder="Email address" class="form-control"
            value="<?= htmlspecialchars($email) ?>" />
        </div>
        <div class="form-group">
          <label for="security_word" class="small font-weight-bold text-muted">
            Security Word <span class="text-danger">*</span>
          </label>
          <input type="text" name="security_word" id="security_word" required placeholder="Your security word"
            class="form-control" />
        </div>
        <button type="submit" class="btn btn-warning btn-block text-white font-weight-bold">Verify</button>
      </form>
    <?php elseif ($step == 2): ?>
      <form action="forgot_password.php" method="POST">
        <input type="hidden" name="step" value="2">
        <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
        <div class="form-group">
          <label for="new_password" class="small font-weight-bold text-muted">
            New Password <span class="text-danger">*</span>
          </label>
          <input type="password" name="new_password" id="new_password" required placeholder="New password"
            class="form-control" />
        </div>
        <div class="form-group">
          <label for="confirm_password" class="small font-weight-bold text-muted">
            Confirm Password <span class="text-danger">*</span>
          </label>
          <input type="password" name="confirm_password" id="confirm_password" required placeholder="Confirm password"
            class="form-control" />
        </div>
        <button type="submit" class="btn btn-warning btn-block text-white font-weight-bold">Reset Password</button>
      </form>
    <?php elseif ($step == 3): ?>
      <div class="text-center mt-3">
        <a href="login.php" class="btn btn-success">Back to Login</a>
      </div>
    <?php endif; ?>
    <div class="text-center mt-3 text-muted">
      <a href="index.php" class="text-primary">Go back to home</a>
    </div>
  </div>
  <script src="js/jquery.slim.min.js"></script>
  <script src="js/bootstrap.bundle.min.js"></script>
  <script src="js/ef9baa832e.js"></script>
</body>

</html>