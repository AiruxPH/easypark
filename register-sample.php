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
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $insert = $pdo->prepare("
                    INSERT INTO users 
                    (first_name, middle_name, last_name, phone, email, password, user_type, security_word) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $insert->execute([$firstName, $middleName, $lastName, $fullPhone, $email, $hashed, $user_type, $securityWord]);
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
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
  <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-gray-500 relative overflow-hidden">
  <img class="absolute inset-0 w-full h-full object-cover opacity-30 -z-10" src="bg-car.jpg" alt="parking bg" />

  <div class="max-w-md w-full bg-white bg-opacity-90 rounded-lg shadow-md p-6 sm:p-8 backdrop-blur-sm form-container">
    <h1 class="text-4xl font-extrabold text-yellow-500 mb-6 text-center tracking-wide">Register</h1>

    <?php if ($message): ?>
      <div class="mb-4 p-3 rounded bg-yellow-100 text-yellow-800 text-sm font-medium"><?= $message ?></div>
    <?php endif; ?>

    <form id="registerForm" action="register.php" method="POST" class="space-y-4" novalidate>
  <div class="flex gap-3">
    <div class="w-1/2">
      <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
      <input type="text" name="first_name" id="first_name" required placeholder="John" class="input">
    </div>
    <div class="w-1/2">
      <label for="middle_name" class="block text-sm font-medium text-gray-700 mb-1">Middle Name <span class="text-gray-400">(optional)</span></label>
      <input type="text" name="middle_name" id="middle_name" placeholder="L." class="input">
    </div>
  </div>

  <div>
    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
    <input type="text" name="last_name" id="last_name" required placeholder="Doe" class="input">
  </div>

  <div>
  <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
    Phone Number <span class="text-gray-400">(optional)</span>
  </label>
  <div class="flex gap-2">
    <select name="country_code" id="country_code" class="input w-1/4">
      <option value="+63">🇵🇭 +63</option>
      <option value="+1">🇺🇸 +1</option>
      <option value="+44">🇬🇧 +44</option>
      <option value="+61">🇦🇺 +61</option>
      <!-- Add more countries if needed -->
    </select>
    <input
      type="text"
      name="phone"
      id="phone"
      placeholder="9123456789"
      class="input w-3/4"
      oninput="this.value = this.value.replace(/[^0-9]/g, '')"
      maxlength="11"
    />
  </div>
</div>


<div>
  <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
    Email Address <span class="text-red-500">*</span>
  </label>
  <input
    type="email"
    name="email"
    id="email"
    required
    placeholder="you@example.com"
    class="input"
  />
</div>


<div>
<label for="password" class="block text-sm font-medium text-gray-700 mb-1">
  Password <span class="text-red-500">*</span>
  <span class="text-gray-400 text-xs cursor-pointer" title="8+ chars, upper/lowercase, number, symbol">?</span>
</label>
  
  <div class="relative">
  <input
    type="password"
    name="password"
    id="password"
    required
    placeholder="••••••••"
    class="input"
  />
  <button type="button" onclick="togglePassword()" class="absolute right-2 top-2 text-sm text-gray-500 hover:text-gray-700">
    <i class="fas fa-eye" id="toggleIcon"></i>
  </button>
</div>
  
  <!-- Custom Progress Bar -->
  <div id="password-strength-bar-container" class="w-full mt-2 h-2 bg-gray-300 rounded-lg">
    <div id="password-strength-bar" class="h-full rounded-lg"></div>
  </div>

  <p id="password-strength-text" class="mt-1 text-sm font-medium"></p>


</div>






  <div>
    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password <span class="text-red-500">*</span></label>
    
    <div class="relative">
    <input type="password" name="confirm_password" id="confirm_password" required placeholder="••••••••" class="input">
  <button type="button" onclick="togglePassword2()" class="absolute right-2 top-2 text-sm text-gray-500 hover:text-gray-700">
    <i class="fas fa-eye" id="toggleIcon2"></i>
  </button>
</div>
  </div>

  <div>
  <label for="security_word" class="block text-sm font-medium text-gray-700 mb-1">
    Security Word <span class="text-red-500">*</span>
  </label>
  <input
    type="text"
    name="security_word"
    id="security_word"
    required
    placeholder="e.g., your favorite color"
    class="input"
  />
  <small class="text-xs text-gray-500">This will be used to verify your identity if you forget your password.</small>
</div>


  <div>
    <button type="submit" class="w-full bg-yellow-500 text-white font-semibold py-2 px-4 rounded hover:bg-indigo-700 transition">
      Register
    </button>
  </div>

  <p class="text-sm text-center text-gray-600">
    Already have an account? <a href="index.php" class="text-indigo-600 hover:underline">Sign in</a>
  </p>
</form>

  </div>

  <script>
    // Optional: Add password visibility toggle here if needed
  </script>

  <style>

.parent-container {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100vh;
}

.form-container {
    max-height: 80vh; /* Limit height to 80% of the viewport */
    overflow-y: auto; /* Enable vertical scrolling */
    padding: 20px;
    background-color: rgba(255, 255, 255, 0.9);
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
    .input {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    outline: none;
    transition: border 0.2s;
  }

  .input:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.3);
  }

  #password-strength-bar {
  width: 0%;
  transition: width 0.3s ease-in-out, background-color 0.3s ease-in-out;
}

#password-strength-bar-container {
  background-color: #e5e7eb; /* Gray background */
}




  </style>

<script>
document.getElementById('registerForm').addEventListener('submit', function (e) {
  const requiredFields = ['first_name', 'last_name', 'email', 'password', 'confirm_password', 'security_word'];
  let errorCount = 0;

  // Validate Required Fields
  requiredFields.forEach(field => {
    const input = document.getElementById(field);
    if (!input.value.trim()) {
      input.classList.add('border-red-500');
      errorCount++;
    } else {
      input.classList.remove('border-red-500');
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
    strengthText.className = "text-sm font-medium text-red-500";
  } else if (strength === 2) {
    strengthBar.style.backgroundColor = "#fb923c"; // Orange for good
    strengthText.textContent = "Good password";
    strengthText.className = "text-sm font-medium text-orange-500";
  } else if (strength === 3) {
    strengthBar.style.backgroundColor = "#facc15"; // Yellow for medium
    strengthText.textContent = "Medium password";
    strengthText.className = "text-sm font-medium text-yellow-500";
  } else if (strength === 4) {
    strengthBar.style.backgroundColor = "#34d399"; // Green for strong
    strengthText.textContent = "Strong password";
    strengthText.className = "text-sm font-medium text-green-500";
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
