<?php
// contact.php
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
  <title>Contact Us - EasyPark</title>
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
    }

    .form-control {
      background: rgba(0, 0, 0, 0.3);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: #fff;
      border-radius: 10px;
      padding: 1.2rem 1rem;
    }

    .form-control:focus {
      background: rgba(0, 0, 0, 0.5);
      border-color: var(--primary);
      color: #fff;
      box-shadow: none;
    }

    .contact-icon {
      width: 50px;
      height: 50px;
      background: rgba(240, 165, 0, 0.1);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary);
      margin-right: 1.5rem;
      font-size: 1.2rem;
    }

    .contact-item {
      display: flex;
      align-items: center;
      margin-bottom: 2rem;
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
      <h1 class="display-title">Get in Touch</h1>
      <p class="lead text-white-50">We'd love to hear from you. Send us a message!</p>
    </div>
  </header>

  <div class="container py-5">

    <div class="row">
      <!-- Contact Info -->
      <div class="col-lg-5 mb-4">
        <div class="glass-card">
          <h3 class="font-weight-bold mb-4 text-warning">Contact Info</h3>

          <div class="contact-item">
            <div class="contact-icon"><i class="fas fa-map-marker-alt"></i></div>
            <div>
              <h5 class="mb-1 font-weight-bold">Location</h5>
              <p class="text-white-50 mb-0">123 EasyPark St, Parking City, Country</p>
            </div>
          </div>

          <div class="contact-item">
            <div class="contact-icon"><i class="fas fa-envelope"></i></div>
            <div>
              <h5 class="mb-1 font-weight-bold">Email</h5>
              <p class="text-white-50 mb-0"><a href="mailto:support@easypark.com"
                  class="text-white-50">support@easypark.com</a></p>
            </div>
          </div>

          <div class="contact-item">
            <div class="contact-icon"><i class="fas fa-phone-alt"></i></div>
            <div>
              <h5 class="mb-1 font-weight-bold">Call Us</h5>
              <p class="text-white-50 mb-0"><a href="tel:+1234567890" class="text-white-50">+1 234 567 890</a></p>
            </div>
          </div>

          <div class="mt-5">
            <h5 class="font-weight-bold mb-3">Follow Us</h5>
            <a href="#" class="text-white mr-3 h4"><i class="fab fa-facebook"></i></a>
            <a href="#" class="text-white mr-3 h4"><i class="fab fa-twitter"></i></a>
            <a href="#" class="text-white mr-3 h4"><i class="fab fa-instagram"></i></a>
            <a href="#" class="text-white h4"><i class="fab fa-linkedin"></i></a>
          </div>
        </div>
      </div>

      <!-- Contact Form -->
      <div class="col-lg-7 mb-4">
        <div class="glass-card">
          <h3 class="font-weight-bold mb-4 text-warning">Send a Message</h3>
          <form id="contactForm">
            <div class="row">
              <div class="col-md-6 form-group mb-4">
                <label class="small text-muted font-weight-bold">YOUR NAME</label>
                <input type="text" class="form-control" name="name" required placeholder="John Doe">
              </div>
              <div class="col-md-6 form-group mb-4">
                <label class="small text-muted font-weight-bold">YOUR EMAIL</label>
                <input type="email" class="form-control" name="email" required placeholder="john@example.com">
              </div>
            </div>
            <div class="form-group mb-4">
              <label class="small text-muted font-weight-bold">SUBJECT</label>
              <input type="text" class="form-control" name="subject" required placeholder="How can we help?">
            </div>
            <div class="form-group mb-4">
              <label class="small text-muted font-weight-bold">MESSAGE</label>
              <textarea class="form-control" name="message" rows="5" required
                placeholder="Write your message here..."></textarea>
            </div>
            <button type="submit" class="btn btn-warning btn-block font-weight-bold py-3" id="submitBtn">Send
              Message</button>
          </form>
        </div>
      </div>
    </div>

  </div>

  <?php include 'footer.php'; ?>

  <!-- Toast Logic -->
  <div class="position-fixed p-3" style="z-index: 5; top: 80px; right: 20px;">
    <div id="contactToast" class="toast hide text-white border-0" role="alert" aria-live="assertive" aria-atomic="true"
      data-delay="5000">
      <div class="toast-body d-flex align-items-center">
        <i id="toastIcon" class="fas fa-check-circle mr-2"></i>
        <span id="toastMessage">Message sent successfully!</span>
      </div>
    </div>
  </div>

  <script src="js/jquery.min.js"></script>
  <script src="js/popper.min.js"></script>
  <script src="js/bootstrap.bundle.min.js"></script>

  <script>
    $(document).ready(function () {
      $('#contactForm').on('submit', function (e) {
        e.preventDefault();

        var submitBtn = $('#submitBtn');
        var originalText = submitBtn.text();
        submitBtn.prop('disabled', true).text('Sending...');

        $.ajax({
          url: 'process_contact.php',
          type: 'POST',
          data: $(this).serialize(),
          dataType: 'json',
          success: function (response) {

            // Configure Toast
            var toast = $('#contactToast');
            if (response.status === 'success') {
              toast.removeClass('bg-danger').addClass('bg-success');
              $('#toastIcon').removeClass('fa-exclamation-circle').addClass('fa-check-circle');
            } else {
              toast.removeClass('bg-success').addClass('bg-danger');
              $('#toastIcon').removeClass('fa-check-circle').addClass('fa-exclamation-circle');
            }
            $('#toastMessage').text(response.message);
            toast.toast('show');

            if (response.status === 'success') {
              $('#contactForm')[0].reset();
            }
          },
          error: function () {
            var toast = $('#contactToast');
            toast.removeClass('bg-success').addClass('bg-danger');
            $('#toastMessage').text("An error occurred. Please try again later.");
            toast.toast('show');
          },
          complete: function () {
            submitBtn.prop('disabled', false).text(originalText);
          }
        });
      });
    });
  </script>

</body>

</html>