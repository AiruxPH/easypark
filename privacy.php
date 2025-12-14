<?php
// privacy.php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
if ($is_logged_in) {
  require_once 'includes/db.php';
  $user_id = $_SESSION['user_id'];
  $stmt = $pdo->prepare('SELECT image FROM users WHERE user_id = ?');
  $stmt->execute([$user_id]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);
  $profilePic = (!empty($user['image']) && file_exists('images/' . $user['image']) ? 'images/' . $user['image'] : 'images/default.jpg');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title>Privacy Policy - EasyPark</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="css/font-awesome.min.css">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <style>
    .bg-image-dark {
      background-image: url('images/nav-bg.jpg');
      background-size: 100% auto;
      background-position: top left;
      background-repeat: repeat-y;
    }

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
    <h2 class="text-warning mb-4">Privacy Policy</h2>
    <div class="bg-dark text-light p-4 rounded mb-4">
      <p class="lead">Your privacy is important to us. This policy explains how EasyPark collects, uses, and protects
        your information:</p>
      <ul>
        <li>We collect only necessary information for account creation and reservation management.</li>
        <li>Your data is stored securely and is not shared with third parties except as required by law.</li>
        <li>We use cookies for session management and user experience improvement.</li>
        <li>You may request deletion of your account and data at any time.</li>
        <li>By using EasyPark, you consent to this privacy policy.</li>
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