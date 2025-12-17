<?php
// about.php
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
  <title>About Us - EasyPark</title>
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
    }

    .glass-card:hover {
      transform: translateY(-5px);
      background: rgba(255, 255, 255, 0.12);
      border-color: rgba(240, 165, 0, 0.5);
    }

    .card-icon {
      font-size: 2.5rem;
      color: var(--primary);
      margin-bottom: 1.5rem;
    }

    .text-muted-light {
      color: rgba(255, 255, 255, 0.7);
    }

    /* Stats Section */
    .stat-item {
      text-align: center;
      margin-bottom: 2rem;
    }

    .stat-number {
      font-size: 3rem;
      font-weight: 800;
      color: var(--primary);
      line-height: 1;
    }

    .stat-label {
      font-size: 1rem;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: rgba(255, 255, 255, 0.6);
      margin-top: 0.5rem;
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
      <h1 class="display-title">About Us</h1>
      <p class="lead text-white-50">Driving the future of smart parking.</p>
    </div>
  </header>

  <div class="container py-5">

    <!-- Intro Section -->
    <div class="row align-items-center mb-5">
      <div class="col-lg-6 mb-4 mb-lg-0">
        <h2 class="text-warning font-weight-bold mb-3">Who We Are</h2>
        <p class="lead text-muted-light">EasyPark is a cutting-edge parking management solution born from a simple idea:
          parking shouldn't be a hassle.</p>
        <p class="text-muted-light">We leverage technology to connect drivers with available parking spots in real-time,
          reducing congestion, saving time, and making cities smarter. Founded in 2024, we are a team of passionate
          developers and urban planners dedicated to improving your daily drive.</p>
      </div>
      <div class="col-lg-6">
        <div class="glass-card d-flex align-items-center justify-content-center"
          style="min-height: 300px; background: rgba(255,255,255,0.05);">
          <!-- Placeholder for an image or graphic -->
          <i class="fas fa-building fa-5x text-white-50"></i>
        </div>
      </div>
    </div>

    <!-- Mission & Vision -->
    <div class="row mb-5">
      <div class="col-md-6 mb-4">
        <div class="glass-card text-center">
          <i class="fas fa-rocket card-icon"></i>
          <h3 class="font-weight-bold mb-3">Our Mission</h3>
          <p class="text-muted-light">To eliminate the stress of parking by providing a seamless, reliable, and
            user-friendly platform that saves time and fuel for every driver.</p>
        </div>
      </div>
      <div class="col-md-6 mb-4">
        <div class="glass-card text-center">
          <i class="fas fa-eye card-icon"></i>
          <h3 class="font-weight-bold mb-3">Our Vision</h3>
          <p class="text-muted-light">To become the world's leading smart parking ecosystem, creating greener, more
            efficient cities where traffic caused by parking searches is a thing of the past.</p>
        </div>
      </div>
    </div>

    <!-- Stats Counter -->
    <div class="row py-5 border-top border-bottom" style="border-color: rgba(255,255,255,0.1) !important;">
      <div class="col-md-4 stat-item">
        <div class="stat-number">10k+</div>
        <div class="stat-label">Happy Drivers</div>
      </div>
      <div class="col-md-4 stat-item">
        <div class="stat-number">50+</div>
        <div class="stat-label">Parking Lots</div>
      </div>
      <div class="col-md-4 stat-item">
        <div class="stat-number">24/7</div>
        <div class="stat-label">Support</div>
      </div>
    </div>

    <div class="text-center mt-5">
      <a href="index.php" class="btn btn-outline-light px-5 py-2">Back to Home</a>
    </div>

  </div>

  <?php include 'footer.php'; ?>

  <script src="js/jquery.min.js"></script>
  <script src="js/popper.min.js"></script>
  <script src="js/bootstrap.bundle.min.js"></script>

</body>

</html>