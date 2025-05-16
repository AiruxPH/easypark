<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'client') {
    header("Location: index.php");
    exit();
}
require_once 'db.php';
$user_id = $_SESSION['user_id'];

// Handle search and sorting
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'reservation_id';
$order = isset($_GET['order']) && strtolower($_GET['order']) === 'desc' ? 'DESC' : 'ASC';
$allowedSort = ['reservation_id','start_datetime','end_datetime','duration','slot_number','amount','payment_status'];
if (!in_array($sort, $allowedSort)) $sort = 'reservation_id';

// Build query
$sql = "SELECT r.*, p.amount, p.payment_status, p.method, p.payment_date, s.slot_number, s.slot_type, v.plate_number, m.brand, m.model
FROM reservations r
JOIN parking_slots s ON r.parking_slot_id = s.parking_slot_id
JOIN vehicles v ON r.vehicle_id = v.vehicle_id
JOIN Vehicle_Models m ON v.model_id = m.model_id
LEFT JOIN payments p ON r.reservation_id = p.reservation_id
WHERE r.user_id = ?";
$params = [$user_id];
if ($search !== '') {
    $sql .= " AND r.reservation_id = ?";
    $params[] = $search;
}
$sql .= " ORDER BY $sort $order";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Get user profile pic for navbar
$stmt = $pdo->prepare('SELECT image FROM users WHERE user_id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$profilePic = (!empty($user['image']) && file_exists('images/' . $user['image'])) ? 'images/' . $user['image'] : 'images/default.jpg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>My Bookings - EasyPark</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="css/font-awesome.min.css">
<link rel="stylesheet" href="css/bootstrap.min.css" >
<style>
.bg-image-dark { background-image: url('nav-bg.jpg'); background-size: 100% auto; background-position: top left; background-repeat: repeat-y; }
.bg-car { background-image: url('bg-car.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat; }
#navbar { transition: background 1s ease-in-out; }
.scrolled { background: rgba(0, 0, 0, 0.3); }
.navbar-dark .navbar-brand, .navbar-dark .navbar-nav .nav-link { color: #fff; }
.navbar-dark .navbar-brand:hover, .navbar-dark .navbar-nav .nav-link:hover { color: #ccc; }
.navbar-nav .nav-item { margin-right: 15px; }
.table thead th { cursor: pointer; }
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
<li class="nav-item"><a class="nav-link" href="reservations.php">Reserve</a></li>
<li class="nav-item"><a class="nav-link active" href="bookings.php">My Bookings</a></li>
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
<h2 class="text-warning mb-4">My Bookings</h2>
<form class="form-inline mb-3" method="get">
  <input type="text" name="search" class="form-control mr-2" placeholder="Search by Reference #" value="<?= htmlspecialchars($search) ?>">
  <button type="submit" class="btn btn-primary">Search</button>
  <a href="bookings.php" class="btn btn-secondary ml-2">Reset</a>
</form>
<div class="table-responsive bg-dark rounded p-3">
<table class="table table-hover table-dark table-bordered">
  <thead>
    <tr>
      <th onclick="sortTable('reservation_id')">Ref # <?= $sort=='reservation_id' ? ($order=='ASC'?'▲':'▼') : '' ?></th>
      <th onclick="sortTable('slot_number')">Slot <?= $sort=='slot_number' ? ($order=='ASC'?'▲':'▼') : '' ?></th>
      <th>Vehicle</th>
      <th onclick="sortTable('start_datetime')">Start <?= $sort=='start_datetime' ? ($order=='ASC'?'▲':'▼') : '' ?></th>
      <th onclick="sortTable('end_datetime')">End <?= $sort=='end_datetime' ? ($order=='ASC'?'▲':'▼') : '' ?></th>
      <th onclick="sortTable('duration')">Duration <?= $sort=='duration' ? ($order=='ASC'?'▲':'▼') : '' ?></th>
      <th onclick="sortTable('amount')">Amount <?= $sort=='amount' ? ($order=='ASC'?'▲':'▼') : '' ?></th>
      <th onclick="sortTable('payment_status')">Payment <?= $sort=='payment_status' ? ($order=='ASC'?'▲':'▼') : '' ?></th>
      <th>Method</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
    <?php if (count($bookings) === 0): ?>
      <tr><td colspan="10" class="text-center">No bookings found.</td></tr>
    <?php else: foreach ($bookings as $b): ?>
      <tr>
        <td><?= htmlspecialchars($b['reservation_id']) ?></td>
        <td><?= htmlspecialchars($b['slot_number']) ?> (<?= htmlspecialchars($b['slot_type']) ?>)</td>
        <td><?= htmlspecialchars($b['brand'].' '.$b['model'].' - '.$b['plate_number']) ?></td>
        <td><?= htmlspecialchars($b['start_datetime']) ?></td>
        <td><?= htmlspecialchars($b['end_datetime']) ?></td>
        <td><?= htmlspecialchars($b['duration']) ?></td>
        <td>₱<?= number_format($b['amount'],2) ?></td>
        <td><?= htmlspecialchars(ucfirst($b['payment_status'])) ?></td>
        <td><?= htmlspecialchars(ucfirst($b['method'])) ?></td>
        <td>
          <!-- Cancel button only if reservation is in the future and not already cancelled/paid -->
          <?php if (strtotime($b['start_datetime']) > time() && $b['payment_status'] !== 'paid'): ?>
            <form method="post" action="bookings.php" style="display:inline;">
              <input type="hidden" name="cancel_id" value="<?= $b['reservation_id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Cancel this booking?');">Cancel</button>
            </form>
          <?php else: ?>
            <span class="text-muted">-</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; endif; ?>
  </tbody>
</table>
</div>
<a href="dashboard.php" class="btn btn-secondary mt-4">Go back to Home</a>
</div>
<script src="js/jquery.min.js"></script>
<script src="js/popper.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
<script>
function sortTable(col) {
  const url = new URL(window.location.href);
  let order = url.searchParams.get('order') === 'ASC' ? 'DESC' : 'ASC';
  if (url.searchParams.get('sort') !== col) order = 'ASC';
  url.searchParams.set('sort', col);
  url.searchParams.set('order', order);
  window.location.href = url.toString();
}
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
