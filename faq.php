<?php
// faq.php
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
  <title>FAQ - EasyPark</title>
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
  <?php include 'includes/client_navbar.php'; ?>
  <div class="container py-5">
    <h2 class="text-warning mb-4">Frequently Asked Questions</h2>
    <div class="accordion" id="faqAccordion">
      <div class="card bg-dark text-light mb-2">
        <div class="card-header" id="faq1">
          <h5 class="mb-0"><button class="btn btn-link text-warning" type="button" data-toggle="collapse"
              data-target="#collapse1">How do I reserve a parking slot?</button></h5>
        </div>
        <div id="collapse1" class="collapse show" data-parent="#faqAccordion">
          <div class="card-body">Login, add your vehicle, go to Reserve, select a slot, set your time, and confirm your
            reservation.</div>
        </div>
      </div>
      <div class="card bg-dark text-light mb-2">
        <div class="card-header" id="faq2">
          <h5 class="mb-0"><button class="btn btn-link text-warning" type="button" data-toggle="collapse"
              data-target="#collapse2">Can I cancel or change my reservation?</button></h5>
        </div>
        <div id="collapse2" class="collapse" data-parent="#faqAccordion">
          <div class="card-body">Yes, you can cancel or modify your reservation from the My Bookings page before your
            reservation starts.</div>
        </div>
      </div>
      <div class="card bg-dark text-light mb-2">
        <div class="card-header" id="faq3">
          <h5 class="mb-0"><button class="btn btn-link text-warning" type="button" data-toggle="collapse"
              data-target="#collapse3">What payment methods are accepted?</button></h5>
        </div>
        <div id="collapse3" class="collapse" data-parent="#faqAccordion">
          <div class="card-body">Currently, you can pay with cash on arrival. Online payment options are coming soon.
          </div>
        </div>
      </div>
      <div class="card bg-dark text-light mb-2">
        <div class="card-header" id="faq4">
          <h5 class="mb-0"><button class="btn btn-link text-warning" type="button" data-toggle="collapse"
              data-target="#collapse4">How do I contact support?</button></h5>
        </div>
        <div id="collapse4" class="collapse" data-parent="#faqAccordion">
          <div class="card-body">Email us at <a href="mailto:support@easypark.com"
              class="text-warning">support@easypark.com</a> for assistance.</div>
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