<?php
// privacy.php
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
  <title>Privacy Policy - EasyPark</title>
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

    /* Page Header */
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
      padding: 2.5rem;
      height: 100%;
      transition: all 0.3s ease;
      margin-bottom: 2rem;
    }

    .section-title {
      font-weight: 700;
      font-size: 1.5rem;
      margin-bottom: 1rem;
      color: var(--primary);
      display: flex;
      align-items: center;
    }

    .section-title i {
      margin-right: 15px;
      background: rgba(240, 165, 0, 0.1);
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
    }

    .policy-text {
      color: rgba(255, 255, 255, 0.85);
      line-height: 1.8;
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
      <h1 class="display-title">Privacy Policy</h1>
      <p class="lead text-white-50">Your security is our top priority.</p>
    </div>
  </header>

  <div class="container py-5" style="max-width: 900px;">

    <p class="lead mb-5 text-center px-lg-5">
      Effective Date: December 2024. <br>
      We are committed to protecting your personal information. This policy outlines our practices.
    </p>

    <!-- Section 1 -->
    <div class="glass-card">
      <h3 class="section-title"><i class="fas fa-database"></i> Information We Collect</h3>
      <p class="policy-text">
        We collect information that you strictly provide to us. This includes:
      <ul class="text-white-50 mt-3">
        <li>Personal identification (Name, email address, phone number).</li>
        <li>Vehicle details (License plate, model) for reservation purposes.</li>
        <li>Transaction data (Reservation history, payment logs).</li>
      </ul>
      </p>
    </div>

    <!-- Section 2 -->
    <div class="glass-card">
      <h3 class="section-title"><i class="fas fa-tasks"></i> How We Use Your Information</h3>
      <p class="policy-text">
        Your data is used solely to facilitate the parking reservation service:
      <ul class="text-white-50 mt-3">
        <li>To process and confirm your parking reservations.</li>
        <li>To communicate booking statuses and support messages.</li>
        <li>To improve our platform's functionality and user experience.</li>
      </ul>
      </p>
    </div>

    <!-- Section 3 -->
    <div class="glass-card">
      <h3 class="section-title"><i class="fas fa-lock"></i> Data Security</h3>
      <p class="policy-text">
        We implement robust security measures to protect your data. We use encryption for sensitive data and strictly
        limit access to personal information to authorized personnel only. We do not sell or trade your data to third
        parties.
      </p>
    </div>

    <!-- Section 4 -->
    <div class="glass-card">
      <h3 class="section-title"><i class="fas fa-cookie-bite"></i> Cookies</h3>
      <p class="policy-text">
        We use cookies to maintain your session and ensuring you stay logged in while navigating the site. These are
        essential for the functionality of the dashboard and booking systems. You can disable cookies in your browser,
        but some features may not work correctly.
      </p>
    </div>

    <!-- Section 5 -->
    <div class="glass-card">
      <h3 class="section-title"><i class="fas fa-user-shield"></i> Your Rights</h3>
      <p class="policy-text">
        You have the right to:
      <ul class="text-white-50 mt-3">
        <li>Access the personal data we hold about you.</li>
        <li>Request corrections to any inaccurate data.</li>
        <li>Request the deletion of your account and data ("Right to be Forgotten").</li>
      </ul>
      To exercise these rights, please contact our support team.
      </p>
    </div>

    <div class="text-center mt-5">
      <p class="text-white-50">Questions about our privacy practices?</p>
      <a href="contact.php" class="btn btn-outline-light px-4">Contact Privacy Team</a>
    </div>

  </div>

  <?php include 'footer.php'; ?>

  <script src="js/jquery.min.js"></script>
  <script src="js/popper.min.js"></script>
  <script src="js/bootstrap.bundle.min.js"></script>

</body>

</html>