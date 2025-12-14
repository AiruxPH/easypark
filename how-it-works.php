<?php
// how-it-works.php
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
  <title>How It Works - EasyPark</title>
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
    }
  </style>
</head>

<body class="bg-car">
  <?php include 'includes/client_navbar.php'; ?>
  <div class="container py-5">
    <h2 class="text-warning mb-4">How EasyPark Works</h2>
    <div class="bg-dark text-light p-4 rounded mb-4">
      <ol class="lead">
        <li><b>Register/Login:</b> Create an account or log in to your EasyPark account.</li>
        <li><b>Add Your Vehicle:</b> Register your vehicle(s) in your profile for quick selection.</li>
        <li><b>Reserve a Slot:</b> Choose your vehicle, select an available parking slot, set your time, and review the
          price.</li>
        <li><b>Confirm & Pay:</b> Review your reservation details and confirm. Pay on arrival or via available online
          methods.</li>
        <li><b>Park & Enjoy:</b> Arrive at your reserved time, park in your slot, and enjoy hassle-free parking!</li>
        <li><b>Manage Bookings:</b> View, cancel, or check the status of your bookings anytime from your dashboard.</li>
      </ol>
    </div>
    <div class="row">
      <div class="col-md-6 mb-4">
        <div class="card bg-dark text-light h-100">
          <div class="card-body">
            <h5 class="card-title text-warning">Why Use EasyPark?</h5>
            <ul>
              <li>Real-time slot availability</li>
              <li>Prevents double booking</li>
              <li>Easy online management</li>
              <li>Secure and user-friendly</li>
            </ul>
          </div>
        </div>
      </div>
      <div class="col-md-6 mb-4">
        <div class="card bg-dark text-light h-100">
          <div class="card-body">
            <h5 class="card-title text-warning">Need Help?</h5>
            <p>Contact our support team at <a href="mailto:support@easypark.com"
                class="text-warning">support@easypark.com</a> or visit the <a href="faq.php"
                class="text-warning">FAQ</a> page.</p>
          </div>
        </div>
      </div>
    </div>
    <a href="index.php" class="btn btn-secondary mt-4">Back to Home</a>
  </div>
  <?php include 'footer.php'; ?>
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