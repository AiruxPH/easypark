<?php
session_start();

if (isset($_SESSION['user_id'])) {
  if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    header("Location: admin/index.php");
    exit();
  } else {
    header("Location: dashboard.php");
    exit();
  }
}

require_once 'includes/db.php';
require_once 'includes/functions.php';

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
      $stmt = $pdo->prepare("SELECT * FROM users WHERE BINARY email = ?");
      $stmt->execute([$email]);
      if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($password === $user['password']) {  // Direct password comparison
          // Start session and redirect based on user type
          $_SESSION['user_id'] = $user['user_id'];
          $_SESSION['user_email'] = $user['email'];
          $_SESSION['user_type'] = $user['user_type'];
          $_SESSION['username'] = $user['first_name'];

          // Log Successful Login
          logActivity($pdo, $user['user_id'], $user['user_type'], 'login', 'User logged in successfully.');


          if ($user['user_type'] === 'admin') {
            header('Location: /admin/index.php');
          } elseif ($user['user_type'] === 'staff') {
            header('Location: /staff/staff-dashboard.php');
          } else {
            header('Location: dashboard.php');
          }
          exit;
        } else {
          $message = "❌ Invalid email or password.";
          // Log Failed Login (Password mismatch)
          logActivity($pdo, null, 'guest', 'login_failed', "Failed login attempt (password mismatch) for: $email");
        }
      } else {
        $message = "❌ No account found with this email.";
        // Log Failed Login (Email not found)
        logActivity($pdo, null, 'guest', 'login_failed', "Failed login attempt (email not found): $email");
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
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

    /* Overlay */
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
      max-width: 420px;
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

    /* Preloader */
    #preloader {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: #0f0f0f;
      /* Match Index Preloader */
      z-index: 9999;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      transition: opacity 0.8s ease-out;
    }

    .loader-logo {
      font-size: 3rem;
      font-weight: 800;
      color: var(--primary);
      margin-bottom: 20px;
      animation: pulse 1s infinite alternate;
    }

    .car-loader {
      position: relative;
      width: 100px;
      height: 4px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 4px;
      overflow: hidden;
    }

    .car-bar {
      position: absolute;
      top: 0;
      left: 0;
      height: 100%;
      width: 50%;
      background: var(--primary);
      animation: drive 1.5s infinite linear;
      border-radius: 4px;
    }

    @keyframes pulse {
      from {
        opacity: 0.6;
        transform: scale(0.95);
      }

      to {
        opacity: 1;
        transform: scale(1.05);
      }
    }

    @keyframes drive {
      0% {
        left: -50%;
      }

      100% {
        left: 100%;
      }
    }

    .hide-loader {
      opacity: 0;
      pointer-events: none;
    }

    /* Card Entry Animation */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .glass-card {
      /* existing styles... */
      animation: fadeInUp 0.8s ease-out;
    }
  </style>
</head>

<body class="p-4">

  <!-- Preloader -->
  <div id="preloader">
    <div class="loader-logo">EASYPARK</div>
    <div class="car-loader">
      <div class="car-bar"></div>
    </div>
    <p class="text-white-50 mt-2 small letter-spacing-1">STARTING ENGINE...</p>
  </div>

  <div class="glass-card">
    <!-- Website Name/Logo -->
    <div class="text-center mb-5">
      <h1 class="display-5 font-weight-bold text-white mb-0">EASYPARK</h1>
      <p class="text-muted small letter-spacing-1">SMART PARKING SOLUTION</p>
    </div>

    <!-- Login Form -->
    <form action="login.php" method="POST">
      <div class="form-group mb-4">
        <label for="email" class="small font-weight-bold text-muted mb-2">EMAIL ADDRESS</label>
        <div class="input-group">
          <input type="email" name="email" id="email" required placeholder="name@example.com" class="form-control"
            autocomplete="username" />
        </div>
      </div>

      <div class="form-group mb-4">
        <label for="password" class="small font-weight-bold text-muted mb-2">PASSWORD</label>
        <div class="position-relative">
          <input type="password" name="password" id="password" required placeholder="Enter your password"
            class="form-control" autocomplete="new-password" />
          <button type="button" onclick="togglePassword()" class="btn btn-link position-absolute text-white-50"
            style="right: 10px; top: 5px;">
            <i class="fas fa-eye" id="toggleIcon"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-warning btn-block mt-5">
        LOGIN
      </button>
    </form>

    <!-- Forgot Password Link -->
    <div class="text-center mt-4">
      <a href="forgot_password.php" class="small text-white-50 hover-light">Forgot your password?</a>
    </div>

    <!-- Register Link -->
    <div class="text-center mt-3">
      <span class="text-muted small">Don't have an account?</span>
      <a href="register.php" class="text-primary font-weight-bold small ml-1">Sign up</a>
    </div>

    <div class="text-center mt-4 border-top border-secondary pt-3">
      <a href="index.php" class="small text-white-50"><i class="fas fa-arrow-left mr-1"></i> Back to Home</a>
    </div>
  </div>

  <script src="js/jquery.min.js"></script>
  <script src="js/popper.min.js"></script>
  <script src="js/bootstrap.bundle.min.js"></script>
  <script>
    $(document).ready(function () {
      $('#togglePassword').click(function () {
        var input = $('#password');
        var icon = $(this).find('i');
        if (input.attr('type') === 'password') {
          input.attr('type', 'text');
          icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
          input.attr('type', 'password');
          icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
      });

      // Show error toast if message exists
      <?php if (!empty($message)): ?>
        $('body').append(`
          <div class="position-fixed p-3" style="z-index: 5; top: 20px; right: 20px;">
              <div id="errorToast" class="toast hide text-white bg-dark border-0" role="alert" aria-live="assertive" aria-atomic="true" data-delay="5000">
                  <div class="toast-body d-flex align-items-center">
                      <i class="fas fa-info-circle text-warning mr-3 fa-lg"></i> 
                      <div><?= htmlspecialchars($message) ?></div>
                  </div>
              </div>
          </div>
        `);
        $('#errorToast').toast('show');
      <?php endif; ?>

      // Handle URL messages
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('msg') === 'login_required') {
        // Create and show a info toast
        $('body').append(`
                <div class="position-fixed p-3" style="z-index: 5; top: 20px; right: 20px;">
                    <div id="loginToast" class="toast hide text-white bg-dark border-0" role="alert" aria-live="assertive" aria-atomic="true" data-delay="5000">
                        <div class="toast-body d-flex align-items-center">
                            <i class="fas fa-exclamation-circle text-warning mr-3 fa-lg"></i> 
                            <div>Please log in to access that page.</div>
                        </div>
                    </div>
                </div>
            `);
        $('#loginToast').toast('show');
      }

    });

    // Preloader Logic (Outside document.ready to ensure it captures the event or state)
    window.addEventListener('load', function () {
      const loader = document.getElementById('preloader');
      setTimeout(function () {
        if (loader) loader.classList.add('hide-loader');
      }, 800);
    });

    // Fallback: If window load already fired
    if (document.readyState === 'complete') {
      setTimeout(function () {
        const loader = document.getElementById('preloader');
        if (loader) loader.classList.add('hide-loader');
      }, 800);
    }
  </script>
</body>

</html>
```