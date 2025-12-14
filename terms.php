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
  <title>Terms of Service - EasyPark</title>
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
    <h2 class="text-warning mb-4">Terms of Service</h2>
    <div class="bg-dark text-light p-4 rounded mb-4">
      <p class="lead">By using EasyPark, you agree to the following terms and conditions:</p>
      <ul>
        <li>Reservations are subject to availability and confirmation.</li>
        <li>Users must provide accurate information and comply with parking rules.</li>
        <li>EasyPark is not liable for loss or damage to vehicles or belongings.</li>
        <li>Payments are non-refundable once a reservation is confirmed, except as required by law.</li>
        <li>Users are responsible for timely arrival and departure.</li>
        <li>EasyPark reserves the right to update these terms at any time.</li>
      </ul>
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