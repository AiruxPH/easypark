<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'client') {
    header("Location: index.php");
    exit();
}
require_once 'db.php';
$user_id = $_SESSION['user_id'];
// Fetch user's vehicles with brand/model/type
$stmt = $pdo->prepare('SELECT v.vehicle_id, v.plate_number, m.brand, m.model, m.type FROM vehicles v JOIN Vehicle_Models m ON v.model_id = m.model_id WHERE v.user_id = ?');
$stmt->execute([$user_id]);
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
if (isset($_POST['reserve_slot_id']) && $selected_vehicle_id) {
    $slot_id = $_POST['reserve_slot_id'];
    // Double-check slot is available and compatible
    $stmt = $pdo->prepare('SELECT * FROM parking_slots WHERE parking_slot_id = ? AND slot_status = "available" AND slot_type = ?');
    $stmt->execute([$slot_id, $selected_vehicle_type]);
    $slot = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($slot) {
        // Reserve: update slot status, insert reservation (assume reservations table exists)
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE parking_slots SET slot_status = "reserved" WHERE parking_slot_id = ?')->execute([$slot_id]);
        $pdo->prepare('INSERT INTO reservations (user_id, vehicle_id, parking_slot_id, reserved_at) VALUES (?, ?, ?, NOW())')->execute([$user_id, $selected_vehicle_id, $slot_id]);
        $pdo->commit();
        $reservation_success = true;
    } else {
        $reservation_error = 'Selected slot is no longer available.';
    }
}
// Fetch available slots for selected vehicle type
$available_slots = [];
if ($selected_vehicle_type) {
    $stmt = $pdo->prepare('SELECT * FROM parking_slots WHERE slot_status = "available" AND slot_type = ?');
    $stmt->execute([$selected_vehicle_type]);
    $available_slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// Get user profile pic for navbar
$stmt = $pdo->prepare('SELECT image FROM users WHERE user_id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$profilePic = (!empty($user['image']) && file_exists('images/' . $user['image'])) ? 'images/' . $user['image'] : 'images/default.jpg';
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
<li class="nav-item"><a class="nav-link" href="#">How It Works</a></li>
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
<option value="<?= $veh['vehicle_id'] ?>" <?= $veh['vehicle_id'] == $selected_vehicle_id ? 'selected' : '' ?>>
<?= htmlspecialchars($veh['brand'] . ' ' . $veh['model'] . ' (' . $veh['type'] . ') - ' . $veh['plate_number']) ?>
</option>
<?php endforeach; ?>
</select>
</div>
</form>
<?php if ($selected_vehicle_id): ?>
<h4 class="text-light">Available Slots for <span class="text-warning"><?php
foreach ($vehicles as $veh) {
    if ($veh['vehicle_id'] == $selected_vehicle_id) {
        echo htmlspecialchars($veh['brand'] . ' ' . $veh['model'] . ' (' . $veh['type'] . ')');
        break;
    }
}
?></span>:</h4>
<?php if (count($available_slots) > 0): ?>
<form method="post">
<input type="hidden" name="vehicle_id" value="<?= $selected_vehicle_id ?>">
<div class="row">
<?php foreach ($available_slots as $slot): ?>
<div class="col-md-4 mb-3">
<div class="card bg-dark text-light">
<div class="card-body">
<h5 class="card-title">Slot <?= htmlspecialchars($slot['slot_number']) ?></h5>
<p class="card-text">Type: <?= htmlspecialchars($slot['slot_type']) ?></p>
<button type="submit" name="reserve_slot_id" value="<?= $slot['parking_slot_id'] ?>" class="btn btn-warning btn-block" onclick="return confirm('Reserve this slot?');">Reserve</button>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
</form>
<?php else: ?>
<div class="alert alert-info mt-3">No available slots for this vehicle type.</div>
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
