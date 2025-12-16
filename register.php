<?php
session_start(); // Start the session
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Redirect logged-in users to their respective dashboards
if (isset($_SESSION['user_id'])) {
  if ($_SESSION['user_type'] === 'admin') {
    header('Location: admin_dashboard.php');
  } elseif ($_SESSION['user_type'] === 'staff') {
    header('Location: staff_dashboard.php');
  } else {
    header('Location: client_dashboard.php');
  }
  exit;
}
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $firstName = filter_var(trim($_POST['first_name']), FILTER_SANITIZE_STRING);
  $middleName = trim($_POST['middle_name']);
  $lastName = trim($_POST['last_name']);
  $phone = trim($_POST['phone']);
  $countryCode = $_POST['country_code'];
  $fullPhone = $countryCode . $phone;
  $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
  $password = $_POST['password'];
  $confirmPwd = $_POST['confirm_password'];
  $user_type = 'client';
  $securityWord = trim($_POST['security_word']);

  if (!$email) {
    $message = "⚠️ Invalid email address.";
  } elseif ($email === 'admin@gmail.com') {
    $message = "❌ This email address is reserved and cannot be registered.";
  } elseif ($password !== $confirmPwd) {
    $message = "❌ Passwords do not match.";
  } else {
    try {
      // Check if email already exists
      $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
      $stmt->execute([$email]);
      if ($stmt->rowCount() > 0) {
        $message = "⚠️ Email already registered.";
      } else {
        // Hash password and insert user into the database

        $insert = $pdo->prepare("
                    INSERT INTO users 
                    (first_name, middle_name, last_name, phone, email, password, user_type, security_word) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
        $insert->execute([$firstName, $middleName, $lastName, $fullPhone, $email, $password, $user_type, $securityWord]);

        $newUserId = $pdo->lastInsertId();
        logActivity($pdo, $newUserId, 'client', 'register', "New user registered: $email");

        $message = "✅ Registration successful!";
      }
    } catch (\PDOException $e) {
      error_log("Database error during registration: " . $e->getMessage());
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
  <title>EASYPARK - Register</title>
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
      overflow-x: hidden;
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
      max-width: 550px;
      /* Wider for register form */
      background: rgba(30, 30, 30, 0.6);
      backdrop-filter: blur(15px);
      -webkit-backdrop-filter: blur(15px);
      border: 1px solid var(--glass-border);
      border-radius: 20px;
      padding: 3rem;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
      margin: 2rem 0;
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

    /* Fix for select padding */
    select.form-control {
      appearance: none;
      background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white'%3e%3cpath d='M7 10l5 5 5-5z'/%3e%3c/svg%3e");
      background-repeat: no-repeat;
      background-position: right 1rem center;
      background-size: 1em;
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

    .password-strength {
      height: 5px;
      border-radius: 3px;
      transition: width 0.3s ease-in-out, background-color 0.3s ease-in-out;
    }
  </style>
</head>

<body class="p-4">

  <div class="glass-card">
    <div class="text-center mb-5">
      <h1 class="font-weight-bold text-white mb-0 h2">REGISTER</h1>
      <p class="text-muted small">Create your account to start parking.</p>
    </div>

    <?php if ($message): ?>
      <div class="alert alert-warning mb-4"><?= $message ?></div>
    <?php endif; ?>

    <form id="registerForm" action="register.php" method="POST" novalidate>
      <div class="form-row">
        <div class="form-group col-md-6 mb-3">
          <label class="small font-weight-bold text-muted mb-2">FIRST NAME <span class="text-danger">*</span></label>
          <input type="text" name="first_name" id="first_name" required placeholder="John" class="form-control">
        </div>
        <div class="form-group col-md-6 mb-3">
          <label class="small font-weight-bold text-muted mb-2">MIDDLE NAME</label>
          <input type="text" name="middle_name" id="middle_name" placeholder="L." class="form-control">
        </div>
      </div>

      <div class="form-group mb-3">
        <label class="small font-weight-bold text-muted mb-2">LAST NAME <span class="text-danger">*</span></label>
        <input type="text" name="last_name" id="last_name" required placeholder="Doe" class="form-control">
      </div>

      <div class="form-group mb-3">
        <label class="small font-weight-bold text-muted mb-2">PHONE NUMBER</label>
        <div class="input-group">
          <select name="country_code" id="country_code" class="form-control"
            style="max-width: 100px; border-top-right-radius: 0; border-bottom-right-radius: 0;">
            <option value="+63">🇵🇭 +63</option>
            <option value="+1">🇺🇸 +1</option>
            <option value="+44">🇬🇧 +44</option>
            <option value="+61">🇦🇺 +61</option>
          </select>
          <input type="text" name="phone" id="phone" placeholder="9123456789" class="form-control"
            style="border-top-left-radius: 0; border-bottom-left-radius: 0;"
            oninput="this.value = this.value.replace(/[^0-9]/g, '')" maxlength="11">
        </div>
      </div>

      <div class="form-group mb-3">
        <label class="small font-weight-bold text-muted mb-2">EMAIL ADDRESS <span class="text-danger">*</span></label>
        <input type="email" name="email" id="email" required placeholder="you@example.com" class="form-control">
      </div>

      <div class="form-group mb-3">
        <label class="small font-weight-bold text-muted mb-2">
          PASSWORD <span class="text-danger">*</span>
          <i class="fas fa-question-circle ml-1 text-white-50" title="8+ chars, upper/lowercase, number, symbol"></i>
        </label>
        <div class="position-relative">
          <input type="password" name="password" id="password" required placeholder="••••••••" class="form-control">
          <button type="button" onclick="togglePassword()" class="btn btn-link position-absolute text-white-50"
            style="right: 10px; top: 5px;">
            <i class="fas fa-eye" id="toggleIcon"></i>
          </button>
        </div>
        <div class="progress mt-2" style="height: 5px; background: rgba(255,255,255,0.1);">
          <div id="password-strength-bar" class="password-strength"></div>
        </div>
        <small id="password-strength-text" class="mt-1 d-block"></small>
      </div>

      <div class="form-group mb-3">
        <label class="small font-weight-bold text-muted mb-2">CONFIRM PASSWORD <span
            class="text-danger">*</span></label>
        <div class="position-relative">
          <input type="password" name="confirm_password" id="confirm_password" required placeholder="••••••••"
            class="form-control">
          <button type="button" onclick="togglePassword2()" class="btn btn-link position-absolute text-white-50"
            style="right: 10px; top: 5px;">
            <i class="fas fa-eye" id="toggleIcon2"></i>
          </button>
        </div>
      </div>

      <div class="form-group mb-4">
        <label class="small font-weight-bold text-muted mb-2">SECURITY WORD <span class="text-danger">*</span></label>
        <input type="text" name="security_word" id="security_word" required placeholder="e.g., your favorite color"
          class="form-control">
        <small class="text-white-50">Used to verify your identity if you forget your password.</small>
      </div>

      <button type="submit" class="btn btn-warning btn-block text-black font-weight-bold mt-4">
        Create Account
      </button>

      <div class="text-center mt-3">
        <span class="text-muted small">Already have an account?</span>
        <a href="login.php" class="text-primary font-weight-bold small ml-1">Sign in</a>
      </div>

      <div class="text-center mt-3 border-top border-secondary pt-3">
        <a href="index.php" class="small text-white-50"><i class="fas fa-arrow-left mr-1"></i> Back to Home</a>
      </div>
    </form>
  </div>

  <script src="js/jquery.min.js"></script>
  <script src="js/bootstrap.min.js"></script>

  <script>
    document.getElementById('registerForm').addEventListener('submit', function (e) {
      const requiredFields = ['first_name', 'last_name', 'email', 'password', 'confirm_password', 'security_word'];
      let errorCount = 0;

      // Validate Required Fields
      requiredFields.forEach(field => {
        const input = document.getElementById(field);
        if (!input.value.trim()) {
          input.classList.add('border-danger');
          errorCount++;
        } else {
          input.classList.remove('border-danger');
        }
      });

      // Validate Email
      const email = document.getElementById('email').value.trim();
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (email && !emailRegex.test(email)) {
        alert("❌ Please enter a valid email address.");
        e.preventDefault();
        return;
      }

      // Validate Password Match
      const password = document.getElementById('password').value.trim();
      const confirm = document.getElementById('confirm_password').value.trim();

      if (password && confirm && password !== confirm) {
        alert("❌ Passwords do not match.");
        e.preventDefault();
        return;
      }

      if (errorCount > 0) {
        alert("⚠️ Please fill in all required fields.");
        e.preventDefault();
      }
    });

    const passwordInput = document.getElementById('password');
    const strengthBar = document.getElementById('password-strength-bar');
    const strengthText = document.getElementById('password-strength-text');

    passwordInput.addEventListener('input', () => {
      const val = passwordInput.value;
      let strength = 0;

      // Rules for strength calculation
      if (val.length >= 8) strength++; // Length check
      if (/[a-z]/.test(val) && /[A-Z]/.test(val)) strength++; // Lower and Upper case check
      if (/\d/.test(val)) strength++; // Number check
      if (/[\W_]/.test(val)) strength++; // Special characters check

      // Calculate strength percentage
      let strengthPercentage = (strength / 4) * 100;
      strengthBar.style.width = `${strengthPercentage}%`;

      // Update the text and color based on strength
      if (strength === 0 || strength === 1) {
        strengthBar.style.backgroundColor = "#ff4d4d"; // Red
        strengthText.textContent = "Weak password";
        strengthText.className = "small font-weight-bold text-danger";
      } else if (strength === 2) {
        strengthBar.style.backgroundColor = "#ffa600"; // Orange
        strengthText.textContent = "Good password";
        strengthText.className = "small font-weight-bold text-warning";
      } else if (strength === 3) {
        strengthBar.style.backgroundColor = "#ffeb3b"; // Yellow
        strengthText.textContent = "Medium password";
        strengthText.className = "small font-weight-bold text-warning";
      } else if (strength === 4) {
        strengthBar.style.backgroundColor = "#4caf50"; // Green
        strengthText.textContent = "Strong password";
        strengthText.className = "small font-weight-bold text-success";
      }
    });

    function togglePassword() {
      const pwd = document.getElementById('password');
      const icon = document.getElementById('toggleIcon');
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

    function togglePassword2() {
      const pwd = document.getElementById('confirm_password');
      const icon = document.getElementById('toggleIcon2');
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