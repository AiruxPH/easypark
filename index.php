<?php
session_start();
if (isset($_SESSION['user_id'])) {
  header("Location: dashboard.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title>EasyPark - Smart Parking Solutions</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="EasyPark - Reserve your parking spot anytime, anywhere. Real-time availability.">

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
      height: 100vh;
      min-height: 600px;
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
      font-size: clamp(3rem, 5vw, 5rem);
      font-weight: 800;
      margin-bottom: 1rem;
      line-height: 1.1;
      background: linear-gradient(to bottom right, #ffffff, #f0a500);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      text-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }

    .lead-text {
      font-size: clamp(1.1rem, 2vw, 1.5rem);
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
      <h1 class="display-title">Park Smarter.<br>Not Harder.</h1>
      <p class="lead-text">Join thousands of happy drivers. Experience real-time availability, seamless bookings, and
        secure payments with EasyPark.</p>
      <a href="login.php" class="btn btn-glow"><i class="fas fa-car mr-2"></i> Reserve A Spot</a>
    </div>

    <div class="scroll-indicator">
      <i class="fas fa-chevron-down fa-2x text-white-50"></i>
    </div>
  </header>

  <!-- Features Section -->
  <section class="features-section">
    <div class="container">
      <div class="row">
        <!-- Feature 1 -->
        <div class="col-md-4 mb-4">
          <div class="glass-card text-center">
            <div class="feature-icon"><i class="fas fa-clock"></i></div>
            <h4 class="feature-title">Real-Time Availability</h4>
            <p class="text-white-50 small mb-0">Don't guess. Know exactly which spots are free before you arrive. Save
              time and fuel.</p>
          </div>
        </div>

        <!-- Feature 2 -->
        <div class="col-md-4 mb-4">
          <div class="glass-card text-center">
            <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
            <h4 class="feature-title">Secure & Cashless</h4>
            <p class="text-white-50 small mb-0">Top up your wallet and pay instantly. Your transactions are encrypted
              and 100% secure.</p>
          </div>
        </div>

        <!-- Feature 3 -->
        <div class="col-md-4 mb-4">
          <div class="glass-card text-center">
            <div class="feature-icon"><i class="fas fa-mobile-alt"></i></div>
            <h4 class="feature-title">Manage on the Go</h4>
            <p class="text-white-50 small mb-0">Extend bookings, view history, and manage your vehicles from any device,
              anywhere.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <?php include 'footer.php'; ?>

  <script src="js/jquery.min.js"></script>
  <script src="js/popper.min.js"></script>
  <script src="js/bootstrap.bundle.min.js"></script>

  <?php if (isset($_GET['msg']) && $_GET['msg'] == 'loggedout'): ?>
    <script>
      $(document).ready(function () {
        // Optional: Use a nice toast instead of alert, but keeping it simple for now or using Bootstrap toast if available
        // alert("You have been logged out successfully."); 
        // Creating a small toast dynamically
        $('body').append(`
                <div class="position-fixed p-3" style="z-index: 5; top: 80px; right: 20px;">
                    <div id="logoutToast" class="toast hide text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true" data-delay="3000">
                        <div class="toast-body">
                            <i class="fas fa-check-circle mr-2"></i> You have loaded out successfully.
                        </div>
                    </div>
                </div>
            `);
        $('#logoutToast').toast('show');
      });
    </script>
  <?php endif; ?>

</body>

</html>