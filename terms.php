<?php
// terms.php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
if ($is_logged_in) {
  require_once 'includes/db.php';
  $user_id = $_SESSION['user_id'];
  $stmt = $pdo->prepare('SELECT image FROM users WHERE user_id = ?');
  $stmt->execute([$user_id]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);
  $profilePic = (!empty($user['image']) && file_exists('images/' . $user['image'])) ? 'images/' . $user['image'] : 'images/default.jpg';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title>Terms and Conditions - EasyPark</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="css/font-awesome.min.css">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <style>
    .bg-car {
      background-image: url('images/bg-car.jpg');
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      background-attachment: fixed;
    }

    .glass-panel {
      background: rgba(43, 45, 66, 0.95);
      border: 1px solid rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
    }
    
    .term-section {
        margin-bottom: 2rem;
    }
    
    .term-title {
        color: #f6c23e;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        padding-bottom: 0.5rem;
        margin-bottom: 1rem;
    }
  </style>
</head>

<body class="bg-car">
  <?php include 'includes/client_navbar.php'; ?>
  
  <div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <h2 class="text-white mb-4 font-weight-bold text-center">Terms and Conditions</h2>
            
            <div class="glass-panel text-light p-5 rounded">
              <p class="lead text-center mb-5">Welcome to EasyPark. By accessing or using our platform, you agree to be bound by these Terms and Conditions.</p>
              
              <div class="term-section">
                <h4 class="term-title">1. EasyPark Coins & Payments</h4>
                <ul>
                    <li><strong>Virtual Currency:</strong> "EasyPark Coins" (🪙) are a virtual currency used exclusively within the EasyPark platform for parking reservations and services.</li>
                    <li><strong>No Cash Value:</strong> Coins have no real-world cash value, cannot be exchanged for cash, and are non-transferable between accounts.</li>
                    <li><strong>Top-Ups:</strong> All Coin purchases ("Top-Ups") are final and non-refundable. Please verify amounts before confirming payment.</li>
                    <li><strong>Wallet Balance:</strong> Users are responsible for maintaining a sufficient Coin balance to cover reservation fees and potential penalties.</li>
                </ul>
              </div>

              <div class="term-section">
                <h4 class="term-title">2. Reservations & Cancellations</h4>
                <ul>
                    <li><strong>Booking Commitment:</strong> Confirmed reservations act as a binding agreement to occupy the selected slot for the specified duration.</li>
                    <li><strong>Cancellations:</strong> Users may cancel a reservation <em>before</em> the scheduled start time. Cancellations made after the start time may not be eligible for a full refund.</li>
                    <li><strong>Voiding:</strong> EasyPark reserves the right to void reservations that are found to be fraudulent or in violation of usage policies.</li>
                </ul>
              </div>

              <div class="term-section">
                <h4 class="term-title">3. Overstay Policy & Penalties</h4>
                <ul>
                    <li><strong>Strict Adherence:</strong> Vehicles must vacate the parking slot by the scheduled End Time.</li>
                    <li><strong>Automatic Penalties:</strong> Staying past the reserved time ("Overstay") will result in automatic penalty charges.</li>
                    <li><strong>Billing:</strong> Penalties are calculated based on the duration of the overstay and the slot rate. These charges are automatically deducted from your Coin Wallet.</li>
                    <li><strong>Debt:</strong> If penalty charges exceed your wallet balance, your account will incur a negative balance (debt) which must be settled before making new reservations.</li>
                </ul>
              </div>

              <div class="term-section">
                <h4 class="term-title">4. User Responsibilities</h4>
                <ul>
                    <li><strong>Vehicle Information:</strong> Users must provide accurate vehicle details (Plate Number, Model). Inaccurate information may lead to reservation forfeiture or towing at the owner's expense.</li>
                    <li><strong>Parking Conduct:</strong> Users must park only in their assigned slot. Improper parking blocking other slots is prohibited.</li>
                    <li><strong>Safety:</strong> EasyPark provides the platform for reservation but allows users to park at their own risk. We are not liable for theft, damage, or loss of property within the parking facility.</li>
                </ul>
              </div>

              <div class="term-section">
                <h4 class="term-title">5. Amendments</h4>
                <p>EasyPark reserves the right to modify these terms at any time. Continued use of the platform after changes constitutes acceptance of the new terms.</p>
              </div>
              
              <div class="mt-5 text-center">
                <p class="small text-muted">Last updated: <?= date('F Y') ?></p>
              </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="index.php" class="btn btn-secondary shadow-sm">Back to Home</a>
            </div>
        </div>
    </div>
  </div>

  <?php include 'includes/footer.php'; ?>
  
  <script src="js/jquery.min.js"></script>
  <script src="js/popper.min.js"></script>
  <script src="js/bootstrap.bundle.min.js"></script>
  <script>
    const navbar = document.getElementById('navbar');
    window.addEventListener('scroll', function () {
      if (window.scrollY > 100) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    });
  </script>
</body>
</html>