<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'client') {
  header("Location: login.php?msg=login_required");
  exit();
}
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/constants.php';
$user_id = $_SESSION['user_id'];
// Fetch user's vehicles with brand/model/type
$stmt = $pdo->prepare('SELECT v.vehicle_id, v.plate_number, m.brand, m.model, m.type FROM vehicles v JOIN Vehicle_Models m ON v.model_id = m.model_id WHERE v.user_id = ?');
$stmt->execute([$user_id]);
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Fetch vehicle IDs with active or pending reservations (not cancelled/completed, and end_time > NOW or status = 'pending')
$active_vehicle_ids = [];
$stmt = $pdo->prepare('SELECT vehicle_id FROM reservations WHERE user_id = ? AND status IN ("pending", "confirmed", "ongoing")');
$stmt->execute([$user_id]);
foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $vid) {
  $active_vehicle_ids[$vid] = true;
}
// Check if user has any active or pending reservation
$user_has_active_reservation = false;
$stmt = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE user_id = ? AND status IN ("pending", "confirmed", "ongoing")');
$stmt->execute([$user_id]);
if ($stmt->fetchColumn() > 0) {
  $user_has_active_reservation = true;
}
$selected_vehicle_id = isset($_POST['vehicle_id']) ? $_POST['vehicle_id'] : ($vehicles[0]['vehicle_id'] ?? null);
// If selected vehicle is not allowed, set to null
if ($selected_vehicle_id && isset($active_vehicle_ids[$selected_vehicle_id])) {
  $selected_vehicle_id = null;
}
$selected_vehicle_type = null;
if ($selected_vehicle_id) {
  foreach ($vehicles as $veh) {
    if ($veh['vehicle_id'] == $selected_vehicle_id) {
      $selected_vehicle_type = $veh['type'];
      break;
    }
  }
}
// Handle reservation submission
$reservation_success = false;
$reservation_error = '';
// Step 2: If a slot is selected for reservation, show the reservation form
$show_reservation_form = false;
$selected_slot = null;
if (isset($_POST['reserve_slot_id']) && $selected_vehicle_id) {
  $slot_id = $_POST['reserve_slot_id'];
  // Fetch slot info using correct columns
  $stmt = $pdo->prepare('SELECT * FROM parking_slots WHERE parking_slot_id = ? AND slot_type = ?');
  $stmt->execute([$slot_id, $selected_vehicle_type]);
  $selected_slot = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($selected_slot) {
    $show_reservation_form = true;
  } else {
    $reservation_error = 'Selected slot is invalid.';
  }
}

if (isset($_POST['review_reservation']) && $selected_vehicle_id && isset($_POST['slot_id'])) {
  $slot_id = $_POST['slot_id'];
  // Fetch slot info for confirmation step (regardless of status, since user already picked it)
  $stmt = $pdo->prepare('SELECT * FROM parking_slots WHERE parking_slot_id = ? AND slot_type = ?');
  $stmt->execute([$slot_id, $selected_vehicle_type]);
  $selected_slot = $stmt->fetch(PDO::FETCH_ASSOC);
  // Repopulate reservation details from POST for confirmation step
  $duration_type = $_POST['duration_type'] ?? '';
  $start_datetime = $_POST['start_datetime'] ?? '';
  $end_datetime = $_POST['end_datetime'] ?? '';
  $payment_method = $_POST['payment_method'] ?? '';
  $price = $_POST['price'] ?? '';
  $duration_value = $_POST['duration_value'] ?? '';
}

// Step 2: Handle reservation confirmation
if (isset($_POST['confirm_reservation']) && $selected_vehicle_id) {
  $slot_id = $_POST['slot_id'];
  $duration_type = $_POST['duration_type'];
  $start_datetime = $_POST['start_datetime'];
  $end_datetime = $_POST['end_datetime'];
  $payment_method = $_POST['payment_method'];
  // Map UI value to DB enum
  // Map UI value to DB enum
  $method = 'wallet';
  $price = floatval($_POST['price']);
  $duration_value = intval($_POST['duration_value']);
  // Double-check slot is still available
  // Double-check slot exists
  $stmt = $pdo->prepare('SELECT * FROM parking_slots WHERE parking_slot_id = ? AND slot_type = ?');
  $stmt->execute([$slot_id, $selected_vehicle_type]);
  $slot = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($slot) {
    // Prevent double booking: check for overlapping reservations for this slot (Confirmed/Ongoing ONLY)
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE parking_slot_id = ? AND status IN ("confirmed", "ongoing") AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?) OR (start_time >= ? AND end_time <= ?))');
    $stmt->execute([
      $slot_id,
      $end_datetime,
      $start_datetime, // overlap at start
      $end_datetime,
      $start_datetime, // overlap at end
      $start_datetime,
      $end_datetime  // fully within
    ]);
    $overlap_count = $stmt->fetchColumn();

    // Prevent double booking: check for overlapping reservations for this vehicle (Confirmed/Ongoing ONLY)
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE vehicle_id = ? AND status IN ("confirmed", "ongoing") AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?) OR (start_time >= ? AND end_time <= ?))');
    $stmt->execute([
      $selected_vehicle_id,
      $end_datetime,
      $start_datetime,
      $end_datetime,
      $start_datetime,
      $start_datetime,
      $end_datetime
    ]);
    $vehicle_overlap = $stmt->fetchColumn();

    // Prevent double booking: check for any overlapping reservations for this user (any vehicle) - Optional, but good practice
    // We strictly enforce this for confirmed/ongoing. Pending overlap for same user is arguably okay if they are shopping around, but let's allow it for now.
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE user_id = ? AND status IN ("confirmed", "ongoing") AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?) OR (start_time >= ? AND end_time <= ?))');
    $stmt->execute([
      $user_id,
      $end_datetime,
      $start_datetime,
      $end_datetime,
      $start_datetime,
      $start_datetime,
      $end_datetime
    ]);
    $user_overlap = $stmt->fetchColumn();

    // Check if slot is physically occupied right now
    if ($slot['slot_status'] === 'occupied') {
      $reservation_error = 'This slot is currently occupied. Please choose another slot.';
      $show_reservation_form = true;
    } elseif ($slot['slot_status'] === 'unavailable') {
      $reservation_error = 'This slot is currently under maintenance. Please choose another slot.';
      $show_reservation_form = true;
    } elseif ($overlap_count > 0) {
      $reservation_error = 'This slot is already reserved for the selected time range. Please choose a different time or slot.';
      $show_reservation_form = true;
    } elseif ($vehicle_overlap > 0) {
      $reservation_error = 'This vehicle already has a reservation that overlaps with the selected time range.';
      $show_reservation_form = true;
    } elseif ($user_overlap > 0) {
      $reservation_error = 'You already have a reservation that overlaps with the selected time range. Please complete or cancel your current booking before making a new one.';
      $show_reservation_form = true;
    } else {
      // Check user balance
      $stmt = $pdo->prepare('SELECT coins FROM users WHERE user_id = ?');
      $stmt->execute([$user_id]);
      $user_balance = $stmt->fetchColumn();

      if ($user_balance < $price) {
        $reservation_error = "Insufficient Balance. You have " . number_format($user_balance, 2) . " coins, but this booking costs " . number_format($price, 2) . " coins. <a href='wallet.php' class='text-warning font-weight-bold'>Top Up Now</a>";
        $show_reservation_form = true;
      } else {
        $pdo->beginTransaction();
        // REMOVED: Static update of parking_slots status. Status is now dynamic based on reservations.
        // $pdo->prepare('UPDATE parking_slots SET slot_status = "reserved" WHERE parking_slot_id = ?')->execute([$slot_id]);
        $pdo->prepare('UPDATE parking_slots SET slot_status = "reserved" WHERE parking_slot_id = ?')->execute([$slot_id]);
        $pdo->prepare('UPDATE users SET coins = coins - ? WHERE user_id = ?')->execute([$price, $user_id]);
        $pdo->prepare("INSERT INTO coin_transactions (user_id, amount, transaction_type, description) VALUES (?, ?, 'payment', 'Reservation Payment')")->execute([$user_id, -$price]);

        // Insert reservation (with duration)
        $pdo->prepare('INSERT INTO reservations (user_id, vehicle_id, parking_slot_id, start_time, end_time, duration, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())')->execute([
          $user_id,
          $selected_vehicle_id,
          $slot_id,
          $start_datetime,
          $end_datetime,
          $duration_value
        ]);
        $reservation_id = $pdo->lastInsertId();
        // Insert payment record with correct columns (Wallet payment)
        $pdo->prepare('INSERT INTO payments (reservation_id, user_id, amount, status, method, payment_date) VALUES (?, ?, ?, ?, ?, NOW())')->execute([
          $reservation_id,
          $user_id,
          $price,
          'successful',
          'coins'
        ]);
        $pdo->commit();
        $reservation_success = true;

        // Notification
        require_once 'includes/notifications.php';
        $slot_num = $selected_slot['slot_number'];
        sendNotification($pdo, $user_id, 'Reservation Confirmed', "Your booking for slot $slot_num is confirmed.", 'success', 'bookings.php');

        logActivity($pdo, $user_id, 'client', 'reservation_created', "User booked slot $slot_num (ID: $reservation_id)");

        $show_reservation_form = false;
      }
    }
  } else {
    $reservation_error = 'Selected slot is no longer available.';
  }
}
// Pagination setup
$slots_per_page = 6;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $slots_per_page;
$total_slots = 0;
// Fetch available slots for selected vehicle type
$available_slots = [];
if ($selected_vehicle_type) {
  // Get total count for pagination
  $stmt = $pdo->prepare('SELECT COUNT(*) FROM parking_slots WHERE slot_type = ?');
  $stmt->execute([$selected_vehicle_type]);
  $total_slots = $stmt->fetchColumn();
  // Fetch slots for current page (Show ALL slots, not just available ones, to allow future bookings)
  $stmt = $pdo->prepare('SELECT * FROM parking_slots WHERE slot_type = ? LIMIT ? OFFSET ?');
  $stmt->bindValue(1, $selected_vehicle_type);
  $stmt->bindValue(2, $slots_per_page, PDO::PARAM_INT);
  $stmt->bindValue(3, $offset, PDO::PARAM_INT);
  $stmt->execute();
  $available_slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// Get user profile pic for navbar
$stmt = $pdo->prepare('SELECT image FROM users WHERE user_id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$profilePic = (!empty($user['image']) && file_exists('images/' . $user['image'])) ? 'images/' . $user['image'] : 'images/default.jpg';

// Step indicator for breadcrumb
$current_step = 1;
if ($show_reservation_form && $selected_slot) {
  $current_step = 2;
}
if (isset($_POST['review_reservation']) && $selected_vehicle_id && $selected_slot) {
  $current_step = 3;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title>Reserve Parking Slot - EasyPark</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="icon" href="images/favicon.png" type="image/png">
  <script src="js/ef9baa832e.js"></script>
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary-color: #f0a500;
      --secondary-color: #1a1a1a;
      --text-color: #e0e0e0;
      --bg-overlay: rgba(30, 30, 30, 0.85);
      --card-bg: rgba(45, 45, 45, 0.9);
      --success-color: #28a745;
      --danger-color: #dc3545;
      --warning-color: #ffc107;
    }

    body {
      font-family: 'Outfit', sans-serif;
      background: url('images/bg-car.jpg') no-repeat center center fixed;
      background-size: cover;
      color: var(--text-color);
      min-height: 100vh;
    }

    /* Fixed Glass Overlay */
    .glass-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.7);
      z-index: -1;
    }

    /* Navbar adjustment */
    .navbar {
      background: rgba(0, 0, 0, 0.9) !important;
      backdrop-filter: blur(10px);
    }

    /* Cards */
    .custom-card {
      background: var(--card-bg);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 15px;
      backdrop-filter: blur(10px);
      box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
      margin-bottom: 2rem;
      padding: 2rem;
    }

    /* Form Controls */
    .form-control {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: #fff;
      border-radius: 8px;
    }

    .form-control:focus {
      background: rgba(255, 255, 255, 0.1);
      border-color: var(--primary-color);
      color: #fff;
      box-shadow: none;
    }

    /* Fix dropdown option visibility */
    .form-control option {
      background: #333;
      color: #fff;
    }

    label {
      font-weight: 500;
      color: #ccc;
      margin-bottom: 0.5rem;
    }

    /* Breadcrumbs */
    .breadcrumb-custom {
      background: transparent;
      padding: 0;
      margin-bottom: 2rem;
    }

    .breadcrumb-item {
      color: #888;
      font-weight: 500;
    }

    .breadcrumb-item.active {
      color: var(--primary-color);
      font-weight: 700;
    }

    .breadcrumb-item+.breadcrumb-item::before {
      color: #555;
    }

    /* Buttons */
    .btn-warning {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
      color: #000;
      font-weight: 600;
    }

    .btn-warning:hover {
      background-color: #d18f00;
      border-color: #d18f00;
    }

    /* Slot Cards */
    .slot-card {
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 10px;
      padding: 1.5rem;
      text-align: center;
      transition: all 0.3s ease;
      background: rgba(255, 255, 255, 0.02);
      height: 100%;
      cursor: default;
    }

    .slot-card.available {
      border-color: rgba(40, 167, 69, 0.3);
      cursor: pointer;
    }

    .slot-card.available:hover {
      background: rgba(40, 167, 69, 0.1);
      transform: translateY(-5px);
      box-shadow: 0 5px 15px rgba(40, 167, 69, 0.2);
    }

    .slot-card.occupied {
      opacity: 0.6;
      border-color: rgba(220, 53, 69, 0.3);
    }

    .slot-card.reserved {
      opacity: 0.6;
      border-color: rgba(255, 193, 7, 0.3);
    }

    .slot-title {
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }

    .slot-status {
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 1px;
      font-weight: 600;
    }

    .badge-custom {
      padding: 0.5em 1em;
      border-radius: 20px;
    }

    /* Pagination */
    .page-link {
      background-color: transparent;
      border-color: rgba(255, 255, 255, 0.1);
      color: var(--primary-color);
    }

    .page-link:hover {
      background-color: rgba(255, 255, 255, 0.05);
      border-color: rgba(255, 255, 255, 0.2);
      color: #fff;
    }

    .page-item.active .page-link {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
      color: #000;
    }

    .page-item.disabled .page-link {
      background-color: transparent;
      color: #555;
      border-color: rgba(255, 255, 255, 0.05);
    }
  </style>
</head>

<body>
  <!-- Fixed Background Overlay -->
  <div class="glass-overlay"></div>

  <?php include 'includes/client_navbar.php'; ?>

  <div class="container py-5">

    <!-- Title Section -->
    <div class="text-center mb-5">
      <h1 class="display-4 font-weight-bold text-white mb-2">Reserve Parking</h1>
      <p class="lead text-white-50">Secure your spot in just a few clicks</p>
    </div>

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb breadcrumb-custom justify-content-center">
        <li class="breadcrumb-item<?= $current_step === 1 ? ' active' : '' ?>">1. Select Vehicle</li>
        <li class="breadcrumb-item<?= $current_step === 2 ? ' active' : '' ?>">2. Select Slot & Details</li>
        <li class="breadcrumb-item<?= $current_step === 3 ? ' active' : '' ?>">3. Confirmation</li>
      </ol>
    </nav>

    <!-- Notifications -->
    <?php if ($reservation_success): ?>
      <div class="alert alert-success text-center shadow-lg border-0 mb-4">
        <i class="fa fa-check-circle mr-2"></i> Reservation successful!
        <a href="bookings.php" class="alert-link">View My Bookings</a>
      </div>
    <?php elseif ($reservation_error): ?>
      <div class="alert alert-danger text-center shadow-lg border-0 mb-4">
        <i class="fa fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($reservation_error) ?>
      </div>
    <?php endif; ?>

    <!-- Step 1: Vehicle Selection -->
    <div class="custom-card">
      <form method="post">
        <div class="form-group mb-0">
          <label for="vehicle_id" class="text-primary text-uppercase small" style="letter-spacing: 1px;">Vehicle To
            Park</label>
          <select name="vehicle_id" id="vehicle_id" class="form-control form-control-lg" onchange="this.form.submit()"
            required <?= $user_has_active_reservation ? 'disabled' : '' ?>>
            <?php foreach ($vehicles as $veh): ?>
              <?php
              $is_active = isset($active_vehicle_ids[$veh['vehicle_id']]);
              ?>
              <option value="<?= $veh['vehicle_id'] ?>" <?= $veh['vehicle_id'] == $selected_vehicle_id ? 'selected' : '' ?>
                <?= $is_active ? 'disabled' : '' ?>>
                <?= htmlspecialchars($veh['brand'] . ' ' . $veh['model'] . ' (' . $veh['type'] . ') - ' . $veh['plate_number']) ?>
                <?= $is_active ? ' (Busy)' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if ($user_has_active_reservation): ?>
            <small class="text-warning mt-2 d-block">
              <i class="fa fa-info-circle mr-1"></i> You currently have an active reservation. Please complete it before
              booking another.
            </small>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- Main Content Area based on Selection -->
    <?php if ($selected_vehicle_id && empty($active_vehicle_ids[$selected_vehicle_id]) && !$user_has_active_reservation): ?>

      <?php if ($current_step === 3): ?>
        <!-- Step 3: Confirmation -->
        <div class="custom-card">
          <form method="post">
            <h4 class="text-primary mb-4 border-bottom border-secondary pb-2">Confirm Your Reservation</h4>

            <div class="row">
              <div class="col-md-6 mb-3">
                <small class="text-muted d-block uppercase">Slot</small>
                <span class="h5">Slot <?= htmlspecialchars($selected_slot['slot_number']) ?> <small
                    class="text-muted">(<?= htmlspecialchars($selected_slot['slot_type']) ?>)</small></span>
              </div>
              <div class="col-md-6 mb-3">
                <small class="text-muted d-block uppercase">Vehicle</small>
                <span class="h5">
                  <?php foreach ($vehicles as $veh) {
                    if ($veh['vehicle_id'] == $selected_vehicle_id) {
                      echo htmlspecialchars($veh['brand'] . ' ' . $veh['model']);
                      break;
                    }
                  } ?>
                </span>
              </div>
              <div class="col-md-6 mb-3">
                <small class="text-muted d-block uppercase">Start Time</small>
                <span class="text-white"><?= htmlspecialchars($_POST['start_datetime']) ?></span>
              </div>
              <div class="col-md-6 mb-3">
                <small class="text-muted d-block uppercase">End Time</small>
                <span class="text-white"><?= htmlspecialchars($_POST['end_datetime']) ?></span>
              </div>
              <div class="col-md-6 mb-3">
                <small class="text-muted d-block uppercase">Duration</small>
                <span class="text-white"><?= htmlspecialchars($_POST['duration_value']) ?>
                  <?= htmlspecialchars($_POST['duration_type']) ?>(s)</span>
              </div>
              <div class="col-md-6 mb-3">
                <small class="text-muted d-block uppercase">Total Price</small>
                <span class="h3 text-warning"><i class="fas fa-coins"></i> <?= htmlspecialchars($_POST['price']) ?></span>
              </div>
            </div>

            <div class="alert alert-warning border-0 mt-3">
              <i class="fa fa-info-circle mr-1"></i> Please ensure all details are correct. Payment will be processed
              immediately upon confirmation.
            </div>

            <!-- Hidden fields -->
            <input type="hidden" name="slot_id" value="<?= $selected_slot['parking_slot_id'] ?>">
            <input type="hidden" name="vehicle_id" value="<?= $selected_vehicle_id ?>">
            <input type="hidden" name="duration_type" value="<?= htmlspecialchars($_POST['duration_type']) ?>">
            <input type="hidden" name="start_datetime" value="<?= htmlspecialchars($_POST['start_datetime']) ?>">
            <input type="hidden" name="end_datetime" value="<?= htmlspecialchars($_POST['end_datetime']) ?>">
            <input type="hidden" name="payment_method" value="<?= htmlspecialchars($_POST['payment_method']) ?>">
            <input type="hidden" name="price" value="<?= htmlspecialchars($_POST['price']) ?>">
            <input type="hidden" name="duration_value" value="<?= htmlspecialchars($_POST['duration_value']) ?>">

            <div class="mt-4 row">
              <div class="col-6">
                <button type="submit" name="confirm_reservation" class="btn btn-warning btn-block py-3 shadow">Confirm
                  Reservation</button>
              </div>
              <div class="col-6">
                <a href="reservations.php" class="btn btn-outline-light btn-block py-3">Cancel</a>
              </div>
            </div>
          </form>
        </div>

      <?php elseif ($show_reservation_form && $selected_slot): ?>
        <!-- Step 2b: Reservation Details Form -->
        <div class="custom-card">
          <form method="post">
            <input type="hidden" name="slot_id" value="<?= $selected_slot['parking_slot_id'] ?>">
            <input type="hidden" name="vehicle_id" value="<?= $selected_vehicle_id ?>">

            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary pb-3">
              <h4 class="mb-0 text-white">Reserve Slot <span
                  class="text-primary"><?= htmlspecialchars($selected_slot['slot_number']) ?></span></h4>
              <span class="badge badge-secondary"><?= htmlspecialchars($selected_slot['slot_type']) ?></span>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Duration Type</label>
                  <select name="duration_type" id="duration_type" class="form-control" required onchange="updatePrice()">
                    <option value="hour">Per Hour</option>
                    <option value="day">Per Day</option>
                  </select>
                </div>
              </div>
            </div>
            <!-- Payment Method Removed: Defaults to Coins -->
            <div class="col-md-6">
              <div class="form-group">
                <label>Payment</label>
                <input type="text" class="form-control" value="My Wallet (Coins)" readonly>
                <input type="hidden" name="payment_method" value="coins">
              </div>
            </div>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Start Date & Time</label>
              <input type="datetime-local" name="start_datetime" id="start_datetime" class="form-control" required
                onchange="updatePrice()" min="<?= date('Y-m-d\TH:i') ?>">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>End Date & Time</label>
              <input type="datetime-local" name="end_datetime" id="end_datetime" class="form-control" required
                onchange="updatePrice()" min="<?= date('Y-m-d\TH:i') ?>">
            </div>
          </div>
        </div>

        <hr class="border-secondary my-4">

        <div class="row align-items-end">
          <div class="col-md-6">
            <div class="form-group mb-0">
              <label>Estimated Price</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text bg-dark border-secondary text-white">Coins</span>
                </div>
                <input type="text" name="price" id="price" class="form-control text-warning font-weight-bold"
                  style="font-size: 1.5rem;" readonly required>
                <div class="input-group-append">
                  <span class="input-group-text bg-dark border-secondary text-warning"><i class="fas fa-coins"></i></span>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-6 text-right">
            <input type="hidden" name="duration_value" id="duration_value">
            <a href="reservations.php" class="btn btn-link text-muted mr-3">Cancel</a>
            <button type="submit" name="review_reservation" class="btn btn-warning px-5 py-3 shadow">Next:
              Review</button>
          </div>
        </div>
        </form>
      </div>

      <script>
        // JS for price calculation
        const rates = <?= json_encode(constant('SLOT_RATES')) ?>;
        const slotType = "<?= $selected_slot['slot_type'] ?>";

        function updatePrice() {
          const durationType = document.getElementById('duration_type').value;
          const start = document.getElementById('start_datetime').value;
          const end = document.getElementById('end_datetime').value;
          let price = 0;
          let durationVal = 0;

          if (start && end && rates[slotType]) {
            const startDate = new Date(start);
            const endDate = new Date(end);
            let diff = (endDate - startDate) / 1000; // seconds

            if (diff > 0) {
              if (durationType === 'hour') {
                const hours = Math.ceil(diff / 3600);
                price = rates[slotType]['hour'] * hours;
                durationVal = hours;
              } else {
                const days = Math.ceil(diff / 86400);
                price = rates[slotType]['day'] * days;
                durationVal = days;
              }
            }
          }
          document.getElementById('price').value = price > 0 ? price.toFixed(2) : '';
          document.getElementById('duration_value').value = durationVal > 0 ? durationVal : '';
        }

        document.getElementById('duration_type').addEventListener('change', updatePrice);
        document.getElementById('start_datetime').addEventListener('change', updatePrice);
        document.getElementById('end_datetime').addEventListener('change', updatePrice);
      </script>

    <?php else: ?>
      <!-- Step 2: Slot Selection Grid -->
      <h4 class="text-white mb-3">Available Slots for <span class="text-primary">
          <?php
          foreach ($vehicles as $veh) {
            if ($veh['vehicle_id'] == $selected_vehicle_id) {
              echo htmlspecialchars($veh['brand'] . ' ' . $veh['model']); // Shortened for cleaner header
              break;
            }
          }
          ?>
        </span></h4>

      <?php if (count($available_slots) > 0): ?>
        <form method="post">
          <input type="hidden" name="vehicle_id" value="<?= $selected_vehicle_id ?>">

          <?php
          $has_active_reservation_check = false;
          // Re-check just in case (normally caught by parent 'if', but safe to keep logic consistent)
          // Logic already handled at top of file $user_has_active_reservation
          // Using the bool from top of file
          ?>

          <div class="row">
            <?php foreach ($available_slots as $slot): ?>
              <?php
              $status = $slot['slot_status'];
              $cardClass = ($status === 'available') ? 'available' : (($status === 'reserved' || $status === 'occupied') ? 'occupied' : 'reserved');
              // Use 'reserved' style for maintenance/unavailable for simplicity or add specific class
              if ($status === 'unavailable')
                $cardClass = 'reserved';
              ?>
              <div class="col-lg-2 col-md-3 col-6 mb-4">
                <div class="slot-card <?= $cardClass ?>">
                  <div class="slot-title text-white"><?= htmlspecialchars($slot['slot_number']) ?></div>
                  <div class="slot-status mb-3 <?= ($status === 'available') ? 'text-success' : 'text-danger' ?>">
                    <?= ucfirst(($status === 'unavailable') ? 'Maintenance' : $status) ?>
                  </div>

                  <button type="submit" name="reserve_slot_id" value="<?= $slot['parking_slot_id'] ?>"
                    class="btn btn-sm btn-block <?= ($status === 'available') ? 'btn-outline-success' : 'btn-outline-secondary' ?>"
                    <?= ($status !== 'available' || $user_has_active_reservation) ? 'disabled' : '' ?>>
                    <?= ($status === 'available') ? 'Select' : 'Unavailable' ?>
                  </button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </form>

        <?php

        // Pagination controls
        $total_pages = ceil($total_slots / $slots_per_page);
        $window = 2;
        if ($total_pages > 1):
          ?>
          <div class="mt-5">
            <nav aria-label="Slot pagination" class="mb-3">
              <ul class="pagination justify-content-center">

                <!-- First -->
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                  <a class="page-link" href="?vehicle_id=<?= $selected_vehicle_id ?>&page=1" title="First Page">
                    <i class="fa fa-angle-double-left"></i>
                  </a>
                </li>

                <!-- Prev -->
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                  <a class="page-link" href="?vehicle_id=<?= $selected_vehicle_id ?>&page=<?= $page - 1 ?>" title="Previous">
                    <i class="fa fa-chevron-left"></i>
                  </a>
                </li>

                <!-- Windowed Pages -->
                <?php
                $start = max(1, $page - $window);
                $end = min($total_pages, $page + $window);

                if ($start > 1) {
                  echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }

                for ($i = $start; $i <= $end; $i++):
                  ?>
                  <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?vehicle_id=<?= $selected_vehicle_id ?>&page=<?= $i ?>"><?= $i ?></a>
                  </li>
                <?php endfor;

                if ($end < $total_pages) {
                  echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                ?>

                <!-- Next -->
                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                  <a class="page-link" href="?vehicle_id=<?= $selected_vehicle_id ?>&page=<?= $page + 1 ?>" title="Next">
                    <i class="fa fa-chevron-right"></i>
                  </a>
                </li>

                <!-- Last -->
                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                  <a class="page-link" href="?vehicle_id=<?= $selected_vehicle_id ?>&page=<?= $total_pages ?>"
                    title="Last Page">
                    <i class="fa fa-angle-double-right"></i>
                  </a>
                </li>
              </ul>
            </nav>

            <!-- Jump to Page -->
            <form action="" method="GET" class="form-inline justify-content-center">
              <input type="hidden" name="vehicle_id" value="<?= $selected_vehicle_id ?>">
              <div class="input-group input-group-sm">
                <input type="number" name="page" class="form-control bg-dark border-secondary text-white" min="1"
                  max="<?= $total_pages ?>" placeholder="Page" style="width: 70px;">
                <div class="input-group-append">
                  <button class="btn btn-outline-warning" type="submit">Go</button>
                </div>
              </div>
            </form>
          </div>
        <?php endif; ?>

      <?php else: ?>
        <div class="text-center py-5">
          <i class="fa fa-ban fa-3x text-muted mb-3"></i>
          <h5 class="text-muted">No slots available for this vehicle type.</h5>
        </div>
      <?php endif; ?>

    <?php endif; ?> <!-- End Steps -->

  <?php else: ?>
    <!-- No Vehicle Selected or Locked out -->
    <?php if (count($vehicles) === 0): ?>
      <div class="custom-card text-center">
        <h3 class="text-warning mb-3">No Vehicles Found</h3>
        <p class="text-white-50">You need to add a vehicle to your profile before making a reservation.</p>
        <a href="profile.php" class="btn btn-primary mt-3">Go to Profile</a>
      </div>
    <?php elseif (count($active_vehicle_ids) === count($vehicles)): ?>
      <div class="custom-card text-center">
        <i class="fa fa-car fa-3x text-warning mb-3"></i>
        <h4 class="text-white">All Vehicles Busy</h4>
        <p class="text-white-50">All your registered vehicles currently have pending or active reservations.</p>
        <a href="bookings.php" class="btn btn-outline-light mt-3">Manage Bookings</a>
      </div>
    <?php endif; ?>

  <?php endif; ?>

  </div>

  <script src="js/jquery.min.js"></script>
  <script src="js/bootstrap.bundle.min.js"></script>

</body>

</html>