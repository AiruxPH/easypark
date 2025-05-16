<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'client') {
    header("Location: index.php");
    exit();
}
require_once 'db.php';
require_once 'constants.php';
$user_id = $_SESSION['user_id'];
// Fetch user's vehicles with brand/model/type
$stmt = $pdo->prepare('SELECT v.vehicle_id, v.plate_number, m.brand, m.model, m.type FROM vehicles v JOIN Vehicle_Models m ON v.model_id = m.model_id WHERE v.user_id = ?');
$stmt->execute([$user_id]);
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Fetch vehicle IDs with active reservations (not cancelled/completed, and end_time > NOW)
$active_vehicle_ids = [];
$stmt = $pdo->prepare('SELECT vehicle_id FROM reservations WHERE user_id = ? AND status IN ("confirmed", "ongoing") AND end_time > NOW()');
$stmt->execute([$user_id]);
foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $vid) {
    $active_vehicle_ids[$vid] = true;
}
$selected_vehicle_id = isset($_POST['vehicle_id']) ? $_POST['vehicle_id'] : ($vehicles[0]['vehicle_id'] ?? null);
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
    $stmt = $pdo->prepare('SELECT * FROM parking_slots WHERE parking_slot_id = ? AND slot_status = "available" AND slot_type = ?');
    $stmt->execute([$slot_id, $selected_vehicle_type]);
    $selected_slot = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($selected_slot) {
        $show_reservation_form = true;
    } else {
        $reservation_error = 'Selected slot is no longer available.';
    }
}

// Step 2: Handle reservation confirmation
if (isset($_POST['confirm_reservation']) && $selected_vehicle_id) {
    $slot_id = $_POST['slot_id'];
    $duration_type = $_POST['duration_type'];
    $start_datetime = $_POST['start_datetime'];
    $end_datetime = $_POST['end_datetime'];
    $payment_method = $_POST['payment_method'];
    // Map UI value to DB enum
    $method = ($payment_method === 'cash') ? 'cash' : 'online';
    $price = floatval($_POST['price']);
    $duration_value = intval($_POST['duration_value']);
    // Double-check slot is still available
    $stmt = $pdo->prepare('SELECT * FROM parking_slots WHERE parking_slot_id = ? AND slot_status = "available" AND slot_type = ?');
    $stmt->execute([$slot_id, $selected_vehicle_type]);
    $slot = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($slot) {
        // Prevent double booking: check for overlapping reservations for this slot
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE parking_slot_id = ? AND status IN ("confirmed", "ongoing") AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?) OR (start_time >= ? AND end_time <= ?))');
        $stmt->execute([
            $slot_id,
            $end_datetime, $start_datetime, // overlap at start
            $end_datetime, $start_datetime, // overlap at end
            $start_datetime, $end_datetime  // fully within
        ]);
        $overlap_count = $stmt->fetchColumn();
        // Prevent double booking: check for overlapping reservations for this vehicle
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE vehicle_id = ? AND status IN ("confirmed", "ongoing") AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?) OR (start_time >= ? AND end_time <= ?))');
        $stmt->execute([
            $selected_vehicle_id,
            $end_datetime, $start_datetime,
            $end_datetime, $start_datetime,
            $start_datetime, $end_datetime
        ]);
        $vehicle_overlap = $stmt->fetchColumn();
        // Prevent double booking: check for any overlapping reservations for this user (any vehicle)
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE user_id = ? AND status IN ("confirmed", "ongoing") AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?) OR (start_time >= ? AND end_time <= ?))');
        $stmt->execute([
            $user_id,
            $end_datetime, $start_datetime,
            $end_datetime, $start_datetime,
            $start_datetime, $end_datetime
        ]);
        $user_overlap = $stmt->fetchColumn();
        if ($overlap_count > 0) {
            $reservation_error = 'This slot is already reserved for the selected time range. Please choose a different time or slot.';
            $show_reservation_form = true;
        } elseif ($vehicle_overlap > 0) {
            $reservation_error = 'This vehicle already has a reservation that overlaps with the selected time range.';
            $show_reservation_form = true;
        } elseif ($user_overlap > 0) {
            $reservation_error = 'You already have a reservation that overlaps with the selected time range. Please complete or cancel your current booking before making a new one.';
            $show_reservation_form = true;
        } else {
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE parking_slots SET slot_status = "reserved" WHERE parking_slot_id = ?')->execute([$slot_id]);
            // Insert reservation (with duration)
            $pdo->prepare('INSERT INTO reservations (user_id, vehicle_id, parking_slot_id, start_time, end_time, duration, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())')->execute([
                $user_id, $selected_vehicle_id, $slot_id, $start_datetime, $end_datetime, $duration_value
            ]);
            $reservation_id = $pdo->lastInsertId();
            // Insert payment record with correct columns
            $pdo->prepare('INSERT INTO payments (reservation_id, amount, status, method, payment_date) VALUES (?, ?, ?, ?, NOW())')->execute([
                $reservation_id, $price, 'pending', $method
            ]);
            $pdo->commit();
            $reservation_success = true;
            $show_reservation_form = false;
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
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM parking_slots WHERE slot_status = "available" AND slot_type = ?');
    $stmt->execute([$selected_vehicle_type]);
    $total_slots = $stmt->fetchColumn();
    // Fetch only slots for current page
    $stmt = $pdo->prepare('SELECT * FROM parking_slots WHERE slot_status = "available" AND slot_type = ? LIMIT ? OFFSET ?');
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
<link rel="stylesheet" href="css/font-awesome.min.css">
<link rel="stylesheet" href="css/bootstrap.min.css" >
<style>
// ...copy navbar/bg styles from dashboard.php for consistency...
.bg-image-dark { background-image: url('nav-bg.jpg'); background-size: 100% auto; background-position: top left; background-repeat: repeat-y; }
.bg-car { background-image: url('bg-car.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat; }
#navbar { transition: background 1s ease-in-out; }
.scrolled { background: rgba(0, 0, 0, 0.3); }
.navbar-dark .navbar-brand, .navbar-dark .navbar-nav .nav-link { color: #fff; }
.navbar-dark .navbar-brand:hover, .navbar-dark .navbar-nav .nav-link:hover { color: #ccc; }
.navbar-nav .nav-item { margin-right: 15px; }
</style>
</head>
<body class="bg-car">
<nav id="navbar" class="navbar navbar-expand-lg bg-image-dark navbar-dark sticky-top w-100 px-3">
<a class="navbar-brand" href="index.php"><h1 class="custom-size 75rem">EASYPARK</h1></a>
<button class="navbar-toggler ml-auto" type="button" data-toggle="collapse" data-target="#collapsibleNavbar">
<span class="navbar-toggler-icon"></span>
</button>
<div class="collapse navbar-collapse justify-content-end" id="collapsibleNavbar">
<ul class="navbar-nav">
<li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
<li class="nav-item"><a class="nav-link active" href="reservations.php">Reserve</a></li>
<li class="nav-item"><a class="nav-link" href="how-it-works.php">How It Works</a></li>
<li class="nav-item">
<a class="btn btn-primary d-flex align-items-center" href="profile.php" id="accountButton" style="padding: 0.375rem 1rem;">
<img src="<?= htmlspecialchars($profilePic) ?>" alt="Profile" style="width:32px;height:32px;object-fit:cover;border-radius:50%;border:2px solid #fff;margin-right:8px;">
My Account (<?php echo $_SESSION['username'] ?>)
</a>
</li>
</ul>
</div>
</nav>
<div class="container py-5">
<!-- Breadcrumb navigation -->
<nav aria-label="breadcrumb">
  <ol class="breadcrumb bg-dark text-light p-2 rounded">
    <li class="breadcrumb-item<?= $current_step === 1 ? ' active' : '' ?>">1. Select Vehicle</li>
    <li class="breadcrumb-item<?= $current_step === 2 ? ' active' : '' ?>">2. Select Slot & Details</li>
    <li class="breadcrumb-item<?= $current_step === 3 ? ' active' : '' ?>">3. Confirmation</li>
  </ol>
</nav>
<h2 class="text-warning mb-4">Reserve a Parking Slot</h2>
<?php if ($reservation_success): ?>
<div class="alert alert-success">Reservation successful!</div>
<?php elseif ($reservation_error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($reservation_error) ?></div>
<?php endif; ?>
<form method="post" class="mb-4">
<div class="form-group">
<label for="vehicle_id" class="text-light">Select Your Vehicle:</label>
<select name="vehicle_id" id="vehicle_id" class="form-control" onchange="this.form.submit()" required>
<?php foreach ($vehicles as $veh): ?>
  <?php
    $is_active = isset($active_vehicle_ids[$veh['vehicle_id']]);
    // Only disable if the reservation is still active (end_time > NOW and status is confirmed/ongoing)
  ?>
  <option value="<?= $veh['vehicle_id'] ?>" <?= $veh['vehicle_id'] == $selected_vehicle_id ? 'selected' : '' ?> <?= $is_active ? 'disabled' : '' ?>>
    <?= htmlspecialchars($veh['brand'] . ' ' . $veh['model'] . ' (' . $veh['type'] . ') - ' . $veh['plate_number']) ?>
    <?= $is_active ? ' (Currently Reserved)' : '' ?>
  </option>
<?php endforeach; ?>
</select>
<small class="form-text text-warning">Vehicles with an active reservation cannot be selected.</small>
</div>
</form>
<?php if ($selected_vehicle_id): ?>
<?php if ($current_step === 3): ?>
<!-- Step 3: Confirmation page -->
<form method="post" class="bg-dark text-light p-4 rounded">
  <h4>Confirm Your Reservation</h4>
  <dl class="row">
    <dt class="col-sm-4">Slot</dt><dd class="col-sm-8">Slot <?= htmlspecialchars($selected_slot['slot_number']) ?> (<?= htmlspecialchars($selected_slot['slot_type']) ?>)</dd>
    <dt class="col-sm-4">Vehicle</dt><dd class="col-sm-8">
      <?php foreach ($vehicles as $veh) { if ($veh['vehicle_id'] == $selected_vehicle_id) { echo htmlspecialchars($veh['brand'] . ' ' . $veh['model'] . ' (' . $veh['type'] . ') - ' . $veh['plate_number']); break; } } ?>
    </dd>
    <dt class="col-sm-4">Start</dt><dd class="col-sm-8"><?= htmlspecialchars($_POST['start_datetime']) ?></dd>
    <dt class="col-sm-4">End</dt><dd class="col-sm-8"><?= htmlspecialchars($_POST['end_datetime']) ?></dd>
    <dt class="col-sm-4">Duration</dt><dd class="col-sm-8"><?= htmlspecialchars($_POST['duration_value']) ?> <?= htmlspecialchars($_POST['duration_type']) ?>(s)</dd>
    <dt class="col-sm-4">Payment Method</dt><dd class="col-sm-8"><?= htmlspecialchars($_POST['payment_method']) ?></dd>
    <dt class="col-sm-4">Price</dt><dd class="col-sm-8">₱<?= htmlspecialchars($_POST['price']) ?></dd>
  </dl>
  <!-- Hidden fields to pass data -->
  <input type="hidden" name="slot_id" value="<?= $selected_slot['parking_slot_id'] ?>">
  <input type="hidden" name="vehicle_id" value="<?= $selected_vehicle_id ?>">
  <input type="hidden" name="duration_type" value="<?= htmlspecialchars($_POST['duration_type']) ?>">
  <input type="hidden" name="start_datetime" value="<?= htmlspecialchars($_POST['start_datetime']) ?>">
  <input type="hidden" name="end_datetime" value="<?= htmlspecialchars($_POST['end_datetime']) ?>">
  <input type="hidden" name="payment_method" value="<?= htmlspecialchars($_POST['payment_method']) ?>">
  <input type="hidden" name="price" value="<?= htmlspecialchars($_POST['price']) ?>">
  <input type="hidden" name="duration_value" value="<?= htmlspecialchars($_POST['duration_value']) ?>">
  <button type="submit" name="confirm_reservation" class="btn btn-warning">Confirm Reservation</button>
  <a href="reservations.php" class="btn btn-secondary ml-2">Cancel</a>
</form>
<?php elseif ($show_reservation_form && $selected_slot): ?>
<!-- Step 2: Reservation details form -->
<form method="post" class="bg-dark text-light p-4 rounded">
  <input type="hidden" name="slot_id" value="<?= $selected_slot['parking_slot_id'] ?>">
  <input type="hidden" name="vehicle_id" value="<?= $selected_vehicle_id ?>">
  <h4>Reserve Slot <?= htmlspecialchars($selected_slot['slot_number']) ?> (<?= htmlspecialchars($selected_slot['slot_type']) ?>)</h4>
  <div class="form-group">
    <label>Duration Type:</label>
    <select name="duration_type" id="duration_type" class="form-control" required onchange="updatePrice()">
      <option value="hour">Per Hour</option>
      <option value="day">Per Day</option>
    </select>
  </div>
  <div class="form-group">
    <label>Start Date & Time:</label>
    <input type="datetime-local" name="start_datetime" id="start_datetime" class="form-control" required onchange="updatePrice()">
  </div>
  <div class="form-group">
    <label>End Date & Time:</label>
    <input type="datetime-local" name="end_datetime" id="end_datetime" class="form-control" required onchange="updatePrice()">
  </div>
  <div class="form-group">
    <label>Payment Method:</label>
    <select name="payment_method" class="form-control" required>
      <option value="cash">Cash</option>
      <option value="gcash" disabled>GCash (Coming Soon)</option>
      <option value="credit_card" disabled>Credit Card (Coming Soon)</option>
    </select>
  </div>
  <div class="form-group">
    <label>Price:</label>
    <input type="text" name="price" id="price" class="form-control" readonly required>
  </div>
  <input type="hidden" name="duration_value" id="duration_value">
  <button type="submit" name="review_reservation" class="btn btn-warning">Review & Confirm</button>
  <a href="reservations.php" class="btn btn-secondary ml-2">Cancel</a>
</form>
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

function confirmReservation() {
  const price = document.getElementById('price').value;
  const durationType = document.getElementById('duration_type').value;
  const durationVal = document.getElementById('duration_value').value;
  const start = document.getElementById('start_datetime').value;
  const end = document.getElementById('end_datetime').value;
  let msg = `Are you sure you want to reserve this slot?\n\n`;
  msg += `Start: ${start}\nEnd: ${end}\nDuration: ${durationVal} ${durationType}(s)\nPrice: ₱${price}`;
  return confirm(msg);
}
</script>
<?php else: ?>
<h4 class="text-light">Available Slots for <span class="text-warning">
<?php
foreach ($vehicles as $veh) {
    if ($veh['vehicle_id'] == $selected_vehicle_id) {
        echo htmlspecialchars($veh['brand'] . ' ' . $veh['model'] . ' (' . $veh['type'] . ')');
        break;
    }
}
?>
</span>:</h4>
<?php if (count($available_slots) > 0): ?>
<form method="post">
<input type="hidden" name="vehicle_id" value="<?= $selected_vehicle_id ?>">
<div class="row">
<?php
$has_active_reservation = false;
$stmt = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE user_id = ? AND status IN ("confirmed", "ongoing") AND end_time > NOW()');
$stmt->execute([$user_id]);
if ($stmt->fetchColumn() > 0) {
    $has_active_reservation = true;
}
?>
<?php foreach ($available_slots as $slot): ?>
<div class="col-md-4 mb-3">
<div class="card bg-dark text-light">
<div class="card-body">
<h5 class="card-title">Slot <?= htmlspecialchars($slot['slot_number']) ?></h5>
<p class="card-text">Type: <?= htmlspecialchars($slot['slot_type']) ?></p>
<button type="submit" name="reserve_slot_id" value="<?= $slot['parking_slot_id'] ?>" class="btn btn-warning btn-block" <?= $has_active_reservation ? 'disabled title="You already have an active reservation. Complete or cancel it before reserving again."' : 'onclick="return confirm(\'Reserve this slot?\');"' ?>>Reserve</button>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
</form>
<?php
// Pagination controls
$total_pages = ceil($total_slots / $slots_per_page);
if ($total_pages > 1):
    $max_links = 5;
    $start = max(1, $page - floor($max_links/2));
    $end = min($total_pages, $start + $max_links - 1);
    if ($end - $start + 1 < $max_links) {
        $start = max(1, $end - $max_links + 1);
    }
?>
<nav aria-label="Slot pagination">
  <ul class="pagination justify-content-center">
    <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>">
      <a class="page-link" href="?vehicle_id=<?= $selected_vehicle_id ?>&page=<?= $page-1 ?>" tabindex="-1">Previous</a>
    </li>
    <?php if ($start > 1): ?>
      <li class="page-item disabled"><span class="page-link">...</span></li>
    <?php endif; ?>
    <?php for ($i = $start; $i <= $end; $i++): ?>
      <li class="page-item<?= $i == $page ? ' active' : '' ?>">
        <a class="page-link" href="?vehicle_id=<?= $selected_vehicle_id ?>&page=<?= $i ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
    <?php if ($end < $total_pages): ?>
      <li class="page-item disabled"><span class="page-link">...</span></li>
    <?php endif; ?>
    <li class="page-item<?= $page >= $total_pages ? ' disabled' : '' ?>">
      <a class="page-link" href="?vehicle_id=<?= $selected_vehicle_id ?>&page=<?= $page+1 ?>">Next</a>
    </li>
  </ul>
</nav>
<?php endif; ?>
<?php else: ?>
<div class="alert alert-info mt-3">No available slots for this vehicle type.</div>
<?php endif; ?>
<?php endif; ?>
<?php else: ?>
<div class="alert alert-warning">You have no registered vehicles. Please add one in your profile.</div>
<?php endif; ?>
<a href="dashboard.php" class="btn btn-secondary mt-4">Go back to Home</a>
</div>
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
