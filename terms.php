<?php
// terms.php
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
  <title>Terms & Conditions - EasyPark</title>
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
      font-size: 1.4rem;
      margin-bottom: 1rem;
      color: var(--primary);
      display: flex;
      align-items: center;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      padding-bottom: 0.8rem;
    }

    .section-title i {
      margin-right: 15px;
      color: #fff;
    }

    .term-list {
      list-style: none;
      padding-left: 0;
    }

    .term-list li {
      position: relative;
      padding-left: 1.5rem;
      margin-bottom: 0.8rem;
      color: rgba(255, 255, 255, 0.7);
    }

    .term-list li:before {
      content: "\f0da";
      /* FontAwesome Chevron Right */
      font-family: "Font Awesome 5 Free";
      font-weight: 900;
      position: absolute;
      left: 0;
      color: var(--primary);
    }

    strong {
      color: #fff;
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
      <h1 class="display-title">Terms & Conditions</h1>
      <p class="lead text-white-50">Please read our parking rules carefully.</p>
    </div>
  </header>

  <div class="container py-5" style="max-width: 900px;">

    <p class="lead mb-5 text-center px-lg-5">
      By accessing or using the EasyPark platform, you agree to be bound by these policies.
    </p>

    <div class="glass-card">
      <h3 class="section-title"><i class="fas fa-coins"></i> 1. EasyPark Coins & Payments</h3>
      <ul class="term-list">
        <li><strong>Virtual Currency:</strong> "EasyPark Coins" are used exclusively for services within our platform.
        </li>
        <li><strong>No Cash Value:</strong> Coins cannot be exchanged for cash and are non-transferable.</li>
        <li><strong>Non-Refundable:</strong> All Coin top-ups are final. Please verify amounts before purchasing.</li>
        <li><strong>Balance Responsibility:</strong> Users must maintain sufficient balance for reservations and
          potential penalties.</li>
      </ul>
    </div>

    <div class="glass-card">
      <h3 class="section-title"><i class="fas fa-calendar-check"></i> 2. Reservations</h3>
      <ul class="term-list">
        <li><strong>Binding Agreement:</strong> A confirmed reservation is a commitment to occupy the slot for the
          booked time.</li>
        <li><strong>Cancellations:</strong> Must be made <em>before</em> the scheduled start time for a refund.</li>
        <li><strong>Fraud:</strong> We reserve the right to void reservations found to be fraudulent.</li>
      </ul>
    </div>

    <div class="glass-card">
      <h3 class="section-title"><i class="fas fa-exclamation-triangle"></i> 3. Overstay & Penalties</h3>
      <ul class="term-list">
        <li><strong>Strict End Times:</strong> You must vacate your slot by the scheduled End Time.</li>
        <li><strong>Automatic Penalties:</strong> Overstaying triggers automatic charges deducted from your wallet.</li>
        <li><strong>Debt:</strong> If penalties exceed your balance, your account will be frozen until the debt is
          settled.</li>
      </ul>
    </div>

    <div class="glass-card">
      <h3 class="section-title"><i class="fas fa-user-check"></i> 4. Responsibilities</h3>
      <ul class="term-list">
        <li><strong>Accuracy:</strong> You must provide accurate vehicle details (License Plate). Inaccurate info may
          lead to towing.</li>
        <li><strong>Proper Parking:</strong> Park only in your assigned slot. Do not obstruct others.</li>
        <li><strong>Liability:</strong> Park at your own risk. EasyPark is not liable for theft or damage within the
          facility.</li>
      </ul>
    </div>

    <div class="text-center mt-5">
      <p class="text-white-50">Last Updated: December 2024</p>
      <a href="index.php" class="btn btn-outline-light px-4">Accept & Return Home</a>
    </div>

  </div>

  <?php include 'footer.php'; ?>

  <script src="js/jquery.min.js"></script>
  <script src="js/popper.min.js"></script>
  <script src="js/bootstrap.bundle.min.js"></script>

</body>

</html>