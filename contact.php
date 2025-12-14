<?php
// contact.php
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
  <title>Contact Us - EasyPark</title>
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
    <h2 class="text-warning mb-4">Contact Us</h2>
    <div class="bg-dark text-light p-4 rounded mb-4">
      <p class="lead">Have questions, feedback, or need help? Reach out to us!</p>
      <ul class="list-unstyled">
        <li><b>Email:</b> <a href="mailto:support@easypark.com" class="text-warning">support@easypark.com</a></li>
        <li><b>Phone:</b> <a href="tel:+1234567890" class="text-warning">+1 234 567 890</a></li>
        <li><b>Address:</b> 123 EasyPark St, Parking City, Country</li>
      </ul>
    </div>
    <div class="card bg-dark text-light mb-4">
      <div class="card-body">
        <h5 class="card-title text-warning">Send Us a Message</h5>
        <form>
          <div class="form-group">
            <label for="name">Name</label>
            <input type="text" class="form-control" id="name" placeholder="Your Name" required>
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" class="form-control" id="email" placeholder="Your Email" required>
          </div>
          <div class="form-group">
            <label for="message">Message</label>
            <textarea class="form-control" id="message" rows="4" placeholder="Your Message" required></textarea>
          </div>
          <button type="submit" class="btn btn-warning">Send Message</button>
        </form>
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