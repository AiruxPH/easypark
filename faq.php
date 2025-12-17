<?php
// faq.php
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
  <title>FAQ - EasyPark</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- CSS -->
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="icon" href="images/favicon.png" type="image/png">
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

    /* FAQ Accent Styles */
    .accordion .card {
      background: var(--glass);
      backdrop-filter: blur(10px);
      border: 1px solid var(--glass-border);
      border-radius: 12px !important;
      margin-bottom: 1rem;
      overflow: hidden;
    }

    .accordion .card-header {
      background: rgba(0, 0, 0, 0.2);
      border-bottom: 0;
      padding: 0;
    }

    .accordion .btn-link {
      color: #fff;
      font-weight: 600;
      font-size: 1.1rem;
      text-decoration: none;
      display: block;
      width: 100%;
      text-align: left;
      padding: 1.25rem;
      transition: color 0.3s ease;
    }

    .accordion .btn-link:hover,
    .accordion .btn-link:focus {
      color: var(--primary);
      text-decoration: none;
    }

    .accordion .btn-link i {
      float: right;
      transition: transform 0.3s;
    }

    .accordion .btn-link[aria-expanded="true"] i {
      transform: rotate(180deg);
      color: var(--primary);
    }

    .card-body {
      color: rgba(255, 255, 255, 0.8);
      line-height: 1.6;
      border-top: 1px solid rgba(255, 255, 255, 0.05);
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
      <h1 class="display-title">Frequently Asked Questions</h1>
      <p class="lead text-white-50">Got questions? We've got answers.</p>
    </div>
  </header>

  <div class="container py-5" style="max-width: 900px;">

    <div class="accordion" id="faqAccordion">

      <!-- FAQ 1 -->
      <div class="card">
        <div class="card-header" id="faq1">
          <h5 class="mb-0">
            <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapse1"
              aria-expanded="true" aria-controls="collapse1">
              How do I reserve a parking slot?
              <i class="fas fa-chevron-down"></i>
            </button>
          </h5>
        </div>
        <div id="collapse1" class="collapse show" aria-labelledby="faq1" data-parent="#faqAccordion">
          <div class="card-body">
            It's simple! Log in to your account, make sure your vehicle is added, then go to the "Reserve" page. pick an
            available slot, choose your duration, and confirm. You're all set!
          </div>
        </div>
      </div>

      <!-- FAQ 2 -->
      <div class="card">
        <div class="card-header" id="faq2">
          <h5 class="mb-0">
            <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#collapse2"
              aria-expanded="false" aria-controls="collapse2">
              Can I cancel or change my reservation?
              <i class="fas fa-chevron-down"></i>
            </button>
          </h5>
        </div>
        <div id="collapse2" class="collapse" aria-labelledby="faq2" data-parent="#faqAccordion">
          <div class="card-body">
            Yes, life happens. You can cancel your reservation from the "My Bookings" page. Please note that
            cancellations must be made before the reservation start time to avoid any penalties.
          </div>
        </div>
      </div>

      <!-- FAQ 3 -->
      <div class="card">
        <div class="card-header" id="faq3">
          <h5 class="mb-0">
            <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#collapse3"
              aria-expanded="false" aria-controls="collapse3">
              How does the EasyPark Wallet work?
              <i class="fas fa-chevron-down"></i>
            </button>
          </h5>
        </div>
        <div id="collapse3" class="collapse" aria-labelledby="faq3" data-parent="#faqAccordion">
          <div class="card-body">
            The EasyPark Wallet is a convenient way to pay for your parking. You can top up your wallet using your
            credit card or other payment methods. When you book a slot, the fee is automatically deducted from your
            balance.
          </div>
        </div>
      </div>

      <!-- FAQ 4 -->
      <div class="card">
        <div class="card-header" id="faq4">
          <h5 class="mb-0">
            <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#collapse4"
              aria-expanded="false" aria-controls="collapse4">
              Is my payment information secure?
              <i class="fas fa-chevron-down"></i>
            </button>
          </h5>
        </div>
        <div id="collapse4" class="collapse" aria-labelledby="faq4" data-parent="#faqAccordion">
          <div class="card-body">
            Absolutely. We take security seriously. All payment transactions are encrypted and processed through secure
            gateways. We do not store your sensitive credit card details on our servers.
          </div>
        </div>
      </div>

      <!-- FAQ 5 -->
      <div class="card">
        <div class="card-header" id="faq5">
          <h5 class="mb-0">
            <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#collapse5"
              aria-expanded="false" aria-controls="collapse5">
              What happens if I arrive late?
              <i class="fas fa-chevron-down"></i>
            </button>
          </h5>
        </div>
        <div id="collapse5" class="collapse" aria-labelledby="faq5" data-parent="#faqAccordion">
          <div class="card-body">
            Your spot is reserved for you for the entire duration you booked. However, if you do not check in within a
            certain grace period, the system might mark it as a "No Show". It's best to modify your booking if you know
            you'll be significantly delayed.
          </div>
        </div>
      </div>

      <!-- FAQ 6 -->
      <div class="card">
        <div class="card-header" id="faq6">
          <h5 class="mb-0">
            <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#collapse6"
              aria-expanded="false" aria-controls="collapse6">
              How do I contact support?
              <i class="fas fa-chevron-down"></i>
            </button>
          </h5>
        </div>
        <div id="collapse6" class="collapse" aria-labelledby="faq6" data-parent="#faqAccordion">
          <div class="card-body">
            We're here to help 24/7! You can reach our support team by emailing <a href="mailto:support@easypark.com"
              class="text-warning">support@easypark.com</a>. We usually respond within an hour.
          </div>
        </div>
      </div>

    </div>

    <div class="text-center mt-5">
      <p class="text-white-50">Still have questions?</p>
      <a href="mailto:support@easypark.com" class="btn btn-outline-light px-4">Contact Support</a>
    </div>

  </div>

  <?php include 'footer.php'; ?>

  <script src="js/jquery.min.js"></script>
  <script src="js/popper.min.js"></script>
  <script src="js/bootstrap.bundle.min.js"></script>

</body>

</html>