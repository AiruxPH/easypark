<?php
session_start(); // Start the session
require_once 'db.php';

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
    $firstName   = filter_var(trim($_POST['first_name']), FILTER_SANITIZE_STRING);
    $middleName  = trim($_POST['middle_name']);
    $lastName    = trim($_POST['last_name']);
    $phone       = trim($_POST['phone']);
    $countryCode = $_POST['country_code'];
    $fullPhone   = $countryCode . $phone;
    $email       = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password    = $_POST['password'];
    $confirmPwd  = $_POST['confirm_password'];
    $user_type   = 'client';
    $securityWord = trim($_POST['security_word']);

    if (!$email) {
        $message = "⚠️ Invalid email address.";
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    body { 
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      background-color: #6c757d;
      position: relative;
      overflow-x: hidden;
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

    .form-container {
      max-width: 500px;
      max-height: 80vh;
      overflow-y: auto;
      background-color: rgba(255, 255, 255, 0.9);
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      backdrop-filter: blur(4px);
    }

    .password-strength {
      height: 8px;
      transition: width 0.3s ease-in-out, background-color 0.3s ease-in-out;
    }
  </style>
</head>
<body class="d-flex align-items-center justify-content-center p-4">
  <img class="bg-image" src="bg-car.jpg" alt="parking bg" />

  <div class="form-container p-4 p-md-5">
    <h1 class="h2 font-weight-bold text-warning mb-4 text-center">Register</h1>

    <?php if ($message): ?>
      <div class="alert alert-warning"><?= $message ?></div>
    <?php endif; ?>

    <form id="registerForm" action="register.php" method="POST" novalidate>
      <div class="form-row">
        <div class="form-group col-md-6">
          <label>First Name <span class="text-danger">*</span></label>
          <input type="text" name="first_name" id="first_name" required placeholder="John" class="form-control">
        </div>
        <div class="form-group col-md-6">
          <label>Middle Name <small class="text-muted">(optional)</small></label>
          <input type="text" name="middle_name" id="middle_name" placeholder="L." class="form-control">
        </div>
      </div>

      <div class="form-group">
        <label>Last Name <span class="text-danger">*</span></label>
        <input type="text" name="last_name" id="last_name" required placeholder="Doe" class="form-control">
      </div>

      <div class="form-group">
        <label>Phone Number <small class="text-muted">(optional)</small></label>
        <div class="input-group">
          <select name="country_code" id="country_code" class="form-control" style="max-width: 120px;">
            <option value="+63">🇵🇭 +63</option>
            <option value="+1">🇺🇸 +1</option>
            <option value="+44">🇬🇧 +44</option>
            <option value="+61">🇦🇺 +61</option>
          </select>
          <input type="text" name="phone" id="phone" placeholder="9123456789" class="form-control"
                 oninput="this.value = this.value.replace(/[^0-9]/g, '')" maxlength="11">
        </div>
      </div>

      <div class="form-group">
        <label>Email Address <span class="text-danger">*</span></label>
        <input type="email" name="email" id="email" required placeholder="you@example.com" class="form-control">
      </div>

      <div class="form-group">
        <label>
          Password <span class="text-danger">*</span>
          <small class="text-muted cursor-pointer" title="8+ chars, upper/lowercase, number, symbol">?</small>
        </label>
        <div class="input-group">
          <input type="password" name="password" id="password" required placeholder="••••••••" class="form-control">
          <div class="input-group-append">
            <button type="button" onclick="togglePassword()" class="btn btn-outline-secondary">
              <i class="fas fa-eye" id="toggleIcon"></i>
            </button>
          </div>
        </div>
        <div class="progress mt-2" style="height: 8px;">
          <div id="password-strength-bar" class="password-strength"></div>
        </div>
        <small id="password-strength-text" class="mt-1"></small>
      </div>

      <div class="form-group">
        <label>Confirm Password <span class="text-danger">*</span></label>
        <div class="input-group">
          <input type="password" name="confirm_password" id="confirm_password" required placeholder="••••••••" class="form-control">
          <div class="input-group-append">
            <button type="button" onclick="togglePassword2()" class="btn btn-outline-secondary">
              <i class="fas fa-eye" id="toggleIcon2"></i>
            </button>
          </div>
        </div>
      </div>

      <div class="form-group">
        <label>Security Word <span class="text-danger">*</span></label>
        <input type="text" name="security_word" id="security_word" required placeholder="e.g., your favorite color" class="form-control">
        <small class="text-muted">This will be used to verify your identity if you forget your password.</small>
      </div>

      <button type="submit" class="btn btn-warning btn-block text-white font-weight-bold">
        Register
      </button>

      <p class="text-center mt-3">
        Already have an account? <a href="login.php" class="text-primary">Sign in</a>
      </p>

      <div class="text-center mt-3 text-muted">
   <a href="index.php" class="text-primary">Go back to home</a>
    </div>
    </form>
  </div>

  <script src="js/jquery.min.js"></script>
  <script src="js/bootstrap.min.js"></script>
  <script src="js/ef9baa832e.js"></script>
  
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
</script>

<script>
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
    strengthBar.style.backgroundColor = "#f87171"; // Red for weak
    strengthText.textContent = "Weak password";
    strengthText.className = "text-sm font-medium text-danger";
  } else if (strength === 2) {
    strengthBar.style.backgroundColor = "#fb923c"; // Orange for good
    strengthText.textContent = "Good password";
    strengthText.className = "text-sm font-medium text-warning";
  } else if (strength === 3) {
    strengthBar.style.backgroundColor = "#facc15"; // Yellow for medium
    strengthText.textContent = "Medium password";
    strengthText.className = "text-sm font-medium text-warning";
  } else if (strength === 4) {
    strengthBar.style.backgroundColor = "#34d399"; // Green for strong
    strengthText.textContent = "Strong password";
    strengthText.className = "text-sm font-medium text-success";
  }
});

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

function togglePassword2() {
  const pwd = document.getElementById('confirm_password');
  const icon = document.getElementById('toggleIcon2');
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
