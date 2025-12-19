<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit();
}

//if the user in not a client, redirect to index.php
if ($_SESSION['user_type'] != 'client' && $_SESSION['user_type'] == 'admin') {
  header("Location: /admin/index.php");
  exit();
}
//if the user is a staff member, redirect to staff-dashboard.php
if ($_SESSION['user_type'] != 'client' && $_SESSION['user_type'] == 'staff') {
  header("Location: /staff/staff-dashboard.php");
  exit();
}

// Fetches user stats
require_once 'includes/db.php';
$user_id = $_SESSION['user_id'];

// 1. Fetch User Name & Wallet Balance
$stmt = $pdo->prepare('SELECT first_name, coins FROM users WHERE user_id = ?');
$stmt->execute([$user_id]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);
$user_name = $user_data['first_name'];
$user_coins = $user_data['coins'];

// 2. Fetch Active Bookings Count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE user_id = ? AND status IN ('confirmed', 'ongoing')");
$stmt->execute([$user_id]);
$active_bookings_count = $stmt->fetchColumn();

// 3. Check for Critical Session (Ongoing or Immediate Upcoming)
$stmt = $pdo->prepare("SELECT r.*, p.slot_number, p.slot_type 
    FROM reservations r 
    JOIN parking_slots p ON r.parking_slot_id = p.parking_slot_id 
    WHERE r.user_id = ? 
    AND r.status IN ('ongoing', 'confirmed') 
    AND r.end_time > NOW() 
    ORDER BY 
        CASE WHEN r.status = 'ongoing' THEN 1 ELSE 2 END, 
        r.start_time ASC 
    LIMIT 1");
$stmt->execute([$user_id]);
$current_session = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <title>EasyPark Dashboard</title>
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

    /* Hero Section */
    .hero-section {
      position: relative;
      /* Subtract navbar height for better centering */
      height: calc(90vh - 76px);
      min-height: 450px;
      background: url('images/bg-car.jpg') no-repeat center center/cover;
      background-attachment: fixed;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .hero-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: radial-gradient(circle at center, rgba(0, 0, 0, 0.4) 0%, rgba(0, 0, 0, 0.85) 100%);
    }

    .hero-content {
      position: relative;
      z-index: 2;
      text-align: center;
      padding: 2rem;
      max-width: 900px;
      animation: fadeInUp 1s ease-out;
      margin-top: -2rem;
      /* Nudge up */
    }

    @media (max-width: 768px) {
      .hero-section {
        height: auto;
        min-height: 70vh;
        padding-top: 3rem;
        padding-bottom: 3rem;
      }

      .hero-content {
        margin-top: 0;
        padding: 1rem;
      }
    }

    /* Typography */
    .display-title {
      font-size: clamp(2.5rem, 4vw, 4rem);
      font-weight: 800;
      margin-bottom: 1rem;
      line-height: 1.1;
      background: linear-gradient(to bottom right, #ffffff, #f0a500);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      text-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }

    .lead-text {
      font-size: clamp(1.1rem, 2vw, 1.4rem);
      color: rgba(255, 255, 255, 0.85);
      margin-bottom: 2.5rem;
      font-weight: 300;
      max-width: 700px;
      margin-left: auto;
      margin-right: auto;
    }

    /* CTA Button */
    .btn-glow {
      background: var(--primary);
      color: #000;
      padding: 1rem 3.5rem;
      border-radius: 50px;
      font-size: 1.1rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
      border: none;
      transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      box-shadow: 0 0 20px rgba(240, 165, 0, 0.4);
      position: relative;
      overflow: hidden;
    }

    .btn-glow:hover {
      transform: translateY(-3px) scale(1.02);
      box-shadow: 0 10px 40px rgba(240, 165, 0, 0.6);
      color: #000;
    }

    /* Features Grid */
    .features-section {
      position: relative;
      padding: 6rem 0;
      background: linear-gradient(to bottom, #0f0f0f, #1a1a1a);
      border-top: 1px solid rgba(255, 255, 255, 0.05);
    }

    .glass-card {
      background: var(--glass);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border: 1px solid var(--glass-border);
      border-radius: 24px;
      padding: 2.5rem;
      height: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      transition: all 0.4s ease;
      position: relative;
      overflow: hidden;
    }

    .glass-card:hover {
      transform: translateY(-10px);
      background: rgba(255, 255, 255, 0.12);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
      border-color: rgba(240, 165, 0, 0.5);
    }

    .feature-icon {
      font-size: 2.5rem;
      background: rgba(240, 165, 0, 0.1);
      width: 80px;
      height: 80px;
      line-height: 80px;
      border-radius: 50%;
      color: var(--primary);
      margin: 0 auto 1.5rem auto;
      transition: all 0.4s ease;
    }

    .glass-card:hover .feature-icon {
      background: var(--primary);
      color: #000;
      transform: scale(1.1) rotate(5deg);
    }

    .feature-title {
      font-size: 1.25rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
      color: #fff;
    }

    .stat-text {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--primary);
      margin-bottom: 0.5rem;
      text-shadow: 0 2px 10px rgba(240, 165, 0, 0.3);
    }

    /* Current Session Widget */
    .session-widget {
      background: rgba(240, 165, 0, 0.15);
      border: 1px solid rgba(240, 165, 0, 0.3);
      border-radius: 20px;
      padding: 2rem;
      margin-bottom: 2rem;
      backdrop-filter: blur(10px);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
      text-align: left;
      animation: fadeInUp 1s ease-out;
      width: 100%;
      max-width: 600px;
      margin-left: auto;
      margin-right: auto;
    }

    .session-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      padding-bottom: 0.5rem;
    }

    .session-badge {
      background: var(--primary);
      color: #000;
      padding: 5px 12px;
      border-radius: 20px;
      font-weight: 700;
      font-size: 0.8rem;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    /* Animations */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(40px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes float {
      0% {
        transform: translateY(0px) translateX(-50%);
      }

      50% {
        transform: translateY(-10px) translateX(-50%);
      }

      100% {
        transform: translateY(0px) translateX(-50%);
      }
    }

    .scroll-indicator {
      position: absolute;
      bottom: 40px;
      left: 50%;
      transform: translateX(-50%);
      animation: float 2s infinite ease-in-out;
      opacity: 0.7;
    }

    #navbar {
      background: rgba(0, 0, 0, 0.6) !important;
      backdrop-filter: blur(10px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    /* Preloader */
    #preloader {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: #0f0f0f;
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
  </style>
</head>

<body>

  <!-- Preloader -->
  <div id="preloader">
    <div class="loader-logo">EASYPARK</div>
    <div class="car-loader">
      <div class="car-bar"></div>
    </div>
    <p class="text-white-50 mt-2 small letter-spacing-1">STARTING ENGINE...</p>
  </div>

  <?php include 'includes/client_navbar.php'; ?>

  <!-- Hero Section -->
  <header class="hero-section">
    <div class="hero-overlay"></div>
    <div class="hero-content">

      <!-- ALWAYS SHOW WELCOME -->
      <h1 class="display-title">Welcome Back, <?= htmlspecialchars($user_name ?? 'Driver') ?>!</h1>
      <p class="lead-text">Ready to park? Your perfect spot is just a click away.</p>

      <?php if ($current_session): ?>
        <!-- ACTIVE SESSION WIDGET -->
        <div class="session-widget mt-4">
          <div class="session-header">
            <span class="text-white-50 small"><i class="fas fa-satellite-dish mr-2 text-success"></i> LIVE STATUS</span>
            <span class="session-badge"><?= strtoupper($current_session['status']) ?></span>
          </div>
          <div class="row align-items-center">
            <div class="col-8">
              <h2 class="font-weight-bold text-white mb-0">Slot <?= htmlspecialchars($current_session['slot_number']) ?>
              </h2>
              <p class="text-white-50 mb-0"><?= htmlspecialchars(ucfirst($current_session['slot_type'])) ?> Parking</p>
            </div>
            <div class="col-4 text-right">
              <i class="fas fa-parking fa-3x text-white-50"></i>
            </div>
          </div>
          <hr class="border-secondary my-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <small class="text-white-50 d-block">Ends at</small>
              <strong class="text-white"><?= date('h:i A', strtotime($current_session['end_time'])) ?></strong>
            </div>
            <a href="bookings.php" class="btn btn-sm btn-outline-warning rounded-pill px-3">View Details</a>
          </div>
        </div>
      <?php else: ?>
        <!-- STANDARD CTA -->
        <a href="reservations.php" class="btn btn-glow"><i class="fas fa-car mr-2"></i> Book Now</a>
      <?php endif; ?>

    </div>

    <div class="scroll-indicator">
      <i class="fas fa-chevron-down fa-2x text-white-50"></i>
    </div>
  </header>

  <!-- Quick Access Section -->
  <section class="features-section">
    <div class="container">
      <div class="row">
        <!-- Feature 1: My Bookings -->
        <div class="col-md-4 mb-4">
          <a href="bookings.php" class="text-decoration-none">
            <div class="glass-card text-center">
              <div class="feature-icon"><i class="fas fa-list-alt"></i></div>
              <h4 class="feature-title">My Bookings</h4>
              <?php if ($active_bookings_count > 0): ?>
                <div class="stat-text"><?= $active_bookings_count ?> Active</div>
                <p class="text-white-50 small mb-0">View your current reservations.</p>
              <?php else: ?>
                <p class="text-white-50 small mb-0">No active bookings.</p>
              <?php endif; ?>
            </div>
          </a>
        </div>

        <!-- Feature 2: New Reservation -->
        <div class="col-md-4 mb-4">
          <a href="reservations.php" class="text-decoration-none">
            <div class="glass-card text-center">
              <div class="feature-icon"><i class="fas fa-calendar-check"></i></div>
              <h4 class="feature-title">New Reservation</h4>
              <p class="text-white-50 small mb-0">Book a new parking slot instantly.</p>
            </div>
          </a>
        </div>

        <!-- Feature 3: My Wallet -->
        <div class="col-md-4 mb-4">
          <a href="wallet.php" class="text-decoration-none">
            <div class="glass-card text-center">
              <div class="feature-icon"><i class="fas fa-wallet"></i></div>
              <h4 class="feature-title">My Wallet</h4>
              <div class="stat-text">🪙 <?= number_format($user_coins, 2) ?></div>
              <p class="text-white-50 small mb-0">Tap to Top Up</p>
            </div>
          </a>
        </div>
      </div>
    </div>
  </section>

  <?php include 'footer.php'; ?>

  <script src="js/jquery.min.js"></script>
  <script src="js/popper.min.js"></script>
  <script src="js/bootstrap.bundle.min.js"></script>

  <script>
    // Preloader Logic
    window.addEventListener('load', function () {
      const loader = document.getElementById('preloader');
      setTimeout(function () {
        loader.classList.add('hide-loader');
      }, 1200); // 1.2s delay for dashboard
    });
  </script>

</body>

</html>