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

require_once 'includes/db.php';
$user_id = $_SESSION['user_id'];
// Optional: Fetch user details for personal greeting if needed
$stmt = $pdo->prepare('SELECT first_name FROM users WHERE user_id = ?');
$stmt->execute([$user_id]);
$user_name = $stmt->fetchColumn();
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
      height: 85vh;
      /* Slightly shorter for dashboard */
      min-height: 500px;
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
      transition: all 0.4s ease;
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
      margin-bottom: 1rem;
      color: #fff;
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
  </style>
</head>

<body>

  <?php include 'includes/client_navbar.php'; ?>

  <!-- Hero Section -->
  <header class="hero-section">
    <div class="hero-overlay"></div>
    <div class="hero-content">
      <h1 class="display-title">Welcome Back, <?= htmlspecialchars($user_name ?? 'Driver') ?>!</h1>
      <p class="lead-text">Ready to park? Your perfect spot is just a click away.</p>
      <a href="reservations.php" class="btn btn-glow"><i class="fas fa-car mr-2"></i> Book Now</a>
    </div>

    <div class="scroll-indicator">
      <i class="fas fa-chevron-down fa-2x text-white-50"></i>
    </div>
  </header>

  <!-- Quick Access Section -->
  <section class="features-section">
    <div class="container">
      <div class="row">
        <!-- Feature 1 -->
        <div class="col-md-4 mb-4">
          <a href="bookings.php" class="text-decoration-none">
            <div class="glass-card text-center">
              <div class="feature-icon"><i class="fas fa-list-alt"></i></div>
              <h4 class="feature-title">My Bookings</h4>
              <p class="text-white-50 small mb-0">View your active reservations and history.</p>
            </div>
          </a>
        </div>

        <!-- Feature 2 -->
        <div class="col-md-4 mb-4">
          <a href="reservations.php" class="text-decoration-none">
            <div class="glass-card text-center">
              <div class="feature-icon"><i class="fas fa-calendar-check"></i></div>
              <h4 class="feature-title">New Reservation</h4>
              <p class="text-white-50 small mb-0">Book a new parking slot instantly.</p>
            </div>
          </a>
        </div>

        <!-- Feature 3 -->
        <div class="col-md-4 mb-4">
          <a href="wallet.php" class="text-decoration-none">
            <div class="glass-card text-center">
              <div class="feature-icon"><i class="fas fa-wallet"></i></div>
              <h4 class="feature-title">My Wallet</h4>
              <p class="text-white-50 small mb-0">Top up and manage your payment methods.</p>
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

</body>

</html>