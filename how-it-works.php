<?php
// how-it-works.php
session_start();
require_once 'includes/db.php';
$is_logged_in = isset($_SESSION['user_id']);

// Profile pic logic if needed
$profilePic = 'images/default.jpg';
if ($is_logged_in) {
  $user_id = $_SESSION['user_id'];
  $stmt = $pdo->prepare('SELECT image FROM users WHERE user_id = ?');
  $stmt->execute([$user_id]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!empty($user['image']) && file_exists('images/' . $user['image'])) {
    $profilePic = 'images/' . $user['image'];
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title>How It Works - EasyPark</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- CSS -->
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="icon" href="images/favicon.png" type="image/png">
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
      background-color: var(--dark);
      color: #fff;
      overflow-x: hidden;
    }

    /* Reuse some Hero Styles for header, but smaller */
    .page-header {
      position: relative;
      height: 40vh;
      min-height: 300px;
      background: url('images/bg-car.jpg') no-repeat center center/cover;
      background-attachment: fixed;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .header-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: radial-gradient(circle at center, rgba(0, 0, 0, 0.5) 0%, rgba(0, 0, 0, 0.9) 100%);
    }

    .header-content {
      position: relative;
      z-index: 2;
      text-align: center;
    }

    .display-title {
      font-size: clamp(2rem, 4vw, 3.5rem);
      font-weight: 800;
      background: linear-gradient(to bottom right, #ffffff, #f0a500);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 1rem;
    }

    /* Glass Cards */
    .glass-card {
      background: var(--glass);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border: 1px solid var(--glass-border);
      border-radius: 20px;
      padding: 2rem;
      height: 100%;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .glass-card:hover {
      transform: translateY(-5px);
      background: rgba(255, 255, 255, 0.12);
      border-color: rgba(240, 165, 0, 0.5);
    }

    .step-number {
      position: absolute;
      top: -15px;
      right: -15px;
      font-size: 5rem;
      font-weight: 900;
      color: rgba(255, 255, 255, 0.05);
      z-index: 0;
    }

    .step-icon {
      font-size: 2rem;
      color: var(--primary);
      margin-bottom: 1rem;
      position: relative;
      z-index: 1;
    }

    .step-title {
      font-weight: 700;
      font-size: 1.25rem;
      margin-bottom: 0.5rem;
      color: #fff;
      position: relative;
      z-index: 1;
    }

    .step-desc {
      color: #aaa;
      font-size: 0.95rem;
      position: relative;
      z-index: 1;
    }

    /* Navbar override */
    #navbar {
      background: rgba(0, 0, 0, 0.6) !important;
      backdrop-filter: blur(10px);
    }
  </style>
</head>

<body>

  <?php include 'includes/client_navbar.php'; ?>

  <!-- Page Header -->
  <header class="page-header">
    <div class="header-overlay"></div>
    <div class="header-content">
      <h1 class="display-title">Parking Made Simple</h1>
      <p class="lead text-white-50">Follow these easy steps to secure your spot.</p>
    </div>
  </header>

  <div class="container py-5">
    <div class="row">
      <!-- Step 1 -->
      <div class="col-md-4 mb-4">
        <div class="glass-card">
          <span class="step-number">01</span>
          <i class="fas fa-user-plus step-icon"></i>
          <h4 class="step-title">Register & Login</h4>
          <p class="step-desc">Create your account in seconds. Log in to access all smart parking features tailored for
            you.</p>
        </div>
      </div>
      <!-- Step 2 -->
      <div class="col-md-4 mb-4">
        <div class="glass-card">
          <span class="step-number">02</span>
          <i class="fas fa-car step-icon"></i>
          <h4 class="step-title">Add Your Vehicle</h4>
          <p class="step-desc">Save your vehicle details in your profile. It makes booking faster for next time.</p>
        </div>
      </div>
      <!-- Step 3 -->
      <div class="col-md-4 mb-4">
        <div class="glass-card">
          <span class="step-number">03</span>
          <i class="fas fa-calendar-check step-icon"></i>
          <h4 class="step-title">Reserve a Slot</h4>
          <p class="step-desc">Browse real-time availability. Pick your preferred spot, set the time, and lock it in.
          </p>
        </div>
      </div>
      <!-- Step 4 -->
      <div class="col-md-4 mb-4">
        <div class="glass-card">
          <span class="step-number">04</span>
          <i class="fas fa-credit-card step-icon"></i>
          <h4 class="step-title">Confirm & Pay</h4>
          <p class="step-desc">Use your secure wallet to pay instantly. No cash, no hassel, just smooth transactions.
          </p>
        </div>
      </div>
      <!-- Step 5 -->
      <div class="col-md-4 mb-4">
        <div class="glass-card">
          <span class="step-number">05</span>
          <i class="fas fa-parking step-icon"></i>
          <h4 class="step-title">Park & Enjoy</h4>
          <p class="step-desc">Drive in at your reserved time. Your spot is waiting for you. Simple as that.</p>
        </div>
      </div>
      <!-- Step 6 -->
      <div class="col-md-4 mb-4">
        <div class="glass-card">
          <span class="step-number">06</span>
          <i class="fas fa-history step-icon"></i>
          <h4 class="step-title">Manage Anytime</h4>
          <p class="step-desc">Need to cancel or check history? Your dashboard gives you full control 24/7.</p>
        </div>
      </div>
    </div>

    <!-- Additional Info Section -->
    <div class="row mt-5">
      <div class="col-md-6 mb-4">
        <div class="glass-card" style="background: rgba(240, 165, 0, 0.05); border-color: rgba(240, 165, 0, 0.2);">
          <i class="fas fa-shield-alt step-icon"></i>
          <h4 class="step-title">Why use EasyPark?</h4>
          <ul class="list-unstyled text-white-50 mt-3">
            <li class="mb-2"><i class="fas fa-check text-success mr-2"></i> Guaranteed spot reservation</li>
            <li class="mb-2"><i class="fas fa-check text-success mr-2"></i> Secure, cashless payments</li>
            <li class="mb-2"><i class="fas fa-check text-success mr-2"></i> 24/7 Support availability</li>
          </ul>
        </div>
      </div>
      <div class="col-md-6 mb-4">
        <div class="glass-card">
          <i class="fas fa-headset step-icon"></i>
          <h4 class="step-title">Need Assistance?</h4>
          <p class="step-desc">Our support team is here to help you get started or resolve any issues.</p>
          <div class="mt-4">
            <a href="faq.php" class="btn btn-outline-light mr-2">Visit FAQ</a>
            <a href="mailto:support@easypark.com" class="btn btn-primary">Email Support</a>
          </div>
        </div>
      </div>
    </div>

  </div>

  <?php include 'footer.php'; ?>

  <script src="js/jquery.min.js"></script>
  <script src="js/popper.min.js"></script>
  <script src="js/bootstrap.bundle.min.js"></script>

</body>

</html>