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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="icon" href="images/favicon.png" type="image/png" />
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #f0a500;
      --dark: #1a1a1a;
      --glass: rgba(255, 255, 255, 0.08);
      --glass-border: rgba(255, 255, 255, 0.1);
    }

    body {
      font-family: 'Outfit', sans-serif;
      min-height: 100vh;
      background: url('images/bg-car.jpg') no-repeat center center/cover;
      position: relative;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    body::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: radial-gradient(circle at center, rgba(0, 0, 0, 0.4) 0%, rgba(0, 0, 0, 0.85) 100%);
      z-index: 0;
    }

    .glass-card {
      position: relative;
      z-index: 1;
      width: 100%;
      max-width: 450px;
      background: rgba(30, 30, 30, 0.6);
      backdrop-filter: blur(15px);
      -webkit-backdrop-filter: blur(15px);
      border: 1px solid var(--glass-border);
      border-radius: 20px;
      padding: 3rem;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
    }

    .form-control {
      background: rgba(0, 0, 0, 0.3);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: #fff;
      border-radius: 10px;
      height: 50px;
      padding-left: 15px;
    }

    .form-control:focus {
      background: rgba(0, 0, 0, 0.5);
      border-color: var(--primary);
      box-shadow: 0 0 0 0.2rem rgba(240, 165, 0, 0.25);
      color: #fff;
    }

    .btn-warning {
      background: var(--primary);
      border: none;
      color: #000;
      font-weight: 700;
      border-radius: 10px;
      height: 50px;
      transition: all 0.3s ease;
    }

    .btn-warning:hover {
      background: #e09b00;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(240, 165, 0, 0.3);
    }

    .text-primary {
      color: var(--primary) !important;
    }

    .text-muted {
      color: rgba(255, 255, 255, 0.6) !important;
    }
  </style>
</head>

<body class="p-4">

  <div class="glass-card">
    <div class="text-center mb-5">
      <h1 class="font-weight-bold text-white mb-0 h3">RESET PASSWORD</h1>
      <p class="text-muted small">Recover access to your account.</p>
    </div>

    <?php if ($message): ?>
      <div class="alert alert-warning mb-4"><?= $message ?></div>
    <?php endif; ?>

    <?php if ($step == 1): ?>
      <form action="forgot_password.php" method="POST">
        <input type="hidden" name="step" value="1">
        <div class="form-group mb-4">
          <label for="email" class="small font-weight-bold text-muted mb-2">EMAIL ADDRESS <span
              class="text-danger">*</span></label>
          <input type="email" name="email" id="email" required placeholder="name@example.com" class="form-control"
            value="<?= htmlspecialchars($email) ?>" />
        </div>
        <div class="form-group mb-4">
          <label for="security_word" class="small font-weight-bold text-muted mb-2">SECURITY WORD <span
              class="text-danger">*</span></label>
          <input type="text" name="security_word" id="security_word" required placeholder="Verification answer"
            class="form-control" />
        </div>
        <button type="submit" class="btn btn-warning btn-block text-black font-weight-bold mt-4">Verify Identity</button>
      </form>

    <?php elseif ($step == 2): ?>
      <form action="forgot_password.php" method="POST">
        <input type="hidden" name="step" value="2">
        <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
        <div class="form-group mb-4">
          <label for="new_password" class="small font-weight-bold text-muted mb-2">NEW PASSWORD <span
              class="text-danger">*</span></label>
          <div class="position-relative">
            <input type="password" name="new_password" id="new_password" required placeholder="New password"
              class="form-control" />
            <button type="button" onclick="togglePassword('new_password', 'toggleNew')"
              class="btn btn-link position-absolute text-white-50" style="right: 10px; top: 5px;">
              <i class="fas fa-eye" id="toggleNew"></i>
            </button>
          </div>
        </div>
        <div class="form-group mb-4">
          <label for="confirm_password" class="small font-weight-bold text-muted mb-2">CONFIRM PASSWORD <span
              class="text-danger">*</span></label>
          <div class="position-relative">
            <input type="password" name="confirm_password" id="confirm_password" required placeholder="Confirm password"
              class="form-control" />
            <button type="button" onclick="togglePassword('confirm_password', 'toggleConfirm')"
              class="btn btn-link position-absolute text-white-50" style="right: 10px; top: 5px;">
              <i class="fas fa-eye" id="toggleConfirm"></i>
            </button>
          </div>
        </div>
        <button type="submit" class="btn btn-warning btn-block text-black font-weight-bold mt-4">Reset Password</button>
      </form>

    <?php elseif ($step == 3): ?>
      <div class="text-center mt-3">
        <a href="login.php" class="btn btn-warning px-5">Back to Login</a>
      </div>
    <?php endif; ?>

    <div class="text-center mt-4 border-top border-secondary pt-3">
      <a href="index.php" class="small text-white-50"><i class="fas fa-arrow-left mr-1"></i> Back to Home</a>
    </div>
  </div>

  <script src="js/jquery.min.js"></script>
  <script src="js/bootstrap.bundle.min.js"></script>
  <script>
    function togglePassword(inputId, iconId) {
      const pwd = document.getElementById(inputId);
      const icon = document.getElementById(iconId);
      if (pwd.type === "password") {
        pwd.type = "text";
        icon.classList.replace('fa-eye', 'fa-eye-slash');
        icon.classList.add('text-primary');
      } else {
        pwd.type = "password";
        icon.classList.replace('fa-eye-slash', 'fa-eye');
        icon.classList.remove('text-primary');
      }
    }
  </script>
</body>

</html>