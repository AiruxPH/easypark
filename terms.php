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

    #navbar {
      transition: background 1s ease-in-out;
    }

    .scrolled {
      background: rgba(0, 0, 0, 0.3);
    }

    .navbar-dark .navbar-brand,
    .navbar-dark .navbar-nav .nav-link {
      color: #fff;
    }

    .navbar-dark .navbar-brand:hover,
    .navbar-dark .navbar-nav .nav-link:hover {
      color: #ccc;
    }

    .navbar-nav .nav-item {
      margin-right: 15px;
    }
  </style>
</head>

<body class="bg-car">
  <nav id="navbar" class="navbar navbar-expand-lg bg-image-dark navbar-dark sticky-top w-100 px-3">
    <a class="navbar-brand" href="index.php">
      <h1 class="custom-size 75rem">EASYPARK</h1>
    </a>
    <button class="navbar-toggler ml-auto" type="button" data-toggle="collapse" data-target="#collapsibleNavbar">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="collapsibleNavbar">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="reservations.php">Reserve</a></li>
        <li class="nav-item"><a class="nav-link" href="how-it-works.php">How It Works</a></li>
        <li class="nav-item"><a class="nav-link" href="faq.php">FAQ</a></li>
        <li class="nav-item"><a class="nav-link active" href="terms.php">Terms of Service</a></li>
        <?php if ($is_logged_in): ?>
          <li class="nav-item"><a class="nav-link" href="bookings.php">My Bookings</a></li>
          <li class="nav-item">
            <a class="btn btn-primary d-flex align-items-center" href="profile.php" id="accountButton"
              style="padding: 0.375rem 1rem;">
              <img src="<?= htmlspecialchars($profilePic) ?>" alt="Profile"
                style="width:32px;height:32px;object-fit:cover;border-radius:50%;border:2px solid #fff;margin-right:8px;">
              My Account (<?= htmlspecialchars($_SESSION['username']) ?>)
            </a>
          </li>
        <?php else: ?>
          <li class="nav-item ml-2"><a class="nav-link btn btn-primary px-4" href="login.php">Login/Sign Up</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </nav>
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