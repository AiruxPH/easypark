<?php
session_start();

if (isset($_SESSION['user_id'])) {
  header("Location: dashboard.php");
  exit();
}

require_once 'db.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];

    if (!$email) {
        $message = "⚠️ Invalid email address.";
    } elseif (empty($password)) {
        $message = "⚠️ Password is required.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if (password_verify($password, $user['password'])) {
                    // Start session and redirect based on user type
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_type'] = $user['user_type'];
                    if ($user['user_type'] === 'admin') {
                        header('Location: dashboard.php');
                    } elseif ($user['user_type'] === 'staff') {
                        header('Location: dashboard.php');
                    } else {
                        header('Location: dashboard.php');
                    }
                    exit;
                } else {
                    $message = "❌ Invalid email or password.";
                }
            } else {
                $message = "❌ No account found with this email.";
            }
        } catch (\PDOException $e) {
            error_log("Database error during login: " . $e->getMessage());
            $message = "❌ A database error occurred. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>EASYPARK - Login</title>
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/font-awesome.min.css"/>
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
    <!-- Website Name/Logo -->
    <div class="text-center mb-4">
      <h1 class="display-5 font-weight-bold text-warning">EASYPARK</h1>
      <p class="text-muted small">Your Smart Parking Solution</p>
    </div>

    <!-- Error/Success Message -->
    <?php if ($message): ?>
      <div class="alert alert-warning small mb-4"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Login Form -->
    <form action="index.php" method="POST">
      <div class="form-group">
        <label for="email" class="small font-weight-bold text-muted">
          Email Address <span class="text-danger">*</span>
        </label>
        <input
          type="email"
          name="email"
          id="email"
          required
          placeholder="Email address"
          class="form-control"
          autocomplete="off"
        />
      </div>
      <div class="form-group">
        <label for="password" class="small font-weight-bold text-muted">
          Password <span class="text-danger">*</span>
        </label>
        <div class="position-relative">
          <input
            type="password"
            name="password"
            id="password"
            required
            placeholder="••••••••"
            class="form-control"
            autocomplete="new-password"
          />
          <button type="button" onclick="togglePassword()" class="btn btn-link position-absolute" style="right: 0; top: 0; padding: 6px 12px;">
            <i class="fas fa-eye text-muted" id="toggleIcon"></i>
          </button>
        </div>
      </div>
      <button type="submit" class="btn btn-warning btn-block text-white font-weight-bold">
        Login
      </button>
    </form>

    <!-- Forgot Password Link -->
    <div class="text-center mt-4">
      <a href="forgot_password.php" class="text-primary">Forgot your password?</a>
    </div>

    <!-- Register Link -->
    <div class="text-center mt-3 text-muted">
      Don't have an account? <a href="register.php" class="text-primary">Sign up here</a>
    </div>

    <div class="text-center mt-3 text-muted">
   <a href="index.php" class="text-primary">Go back to home</a>
    </div>
  </div>

  <script src="js/jquery.slim.min.js"></script>
  <script src="js/bootstrap.bundle.min.js"></script>
  <script src="js/ef9baa832e.js"></script>
  <script>
    function togglePassword() {
      const pwd = document.getElementById('password');
      const icon = document.getElementById('toggleIcon');
      if (pwd.type === "password") {
        pwd.type = "text";
        icon.classList.replace('fa-eye', 'fa-eye-slash');
      } else {
        pwd.type = "password";
        icon.classList.replace('fa-eye-slash', 'fa-eye');
      }
    }
  </script>
</body>
</html>