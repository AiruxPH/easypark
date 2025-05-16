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
$user_id = $_SESSION['user_id'];

// Fetch all bookings for this user
$sql = "SELECT r.reservation_id, r.start_time, r.end_time, r.duration, r.status, s.slot_number, s.slot_type, v.plate_number, m.brand, m.model, p.amount, p.status AS payment_status, p.method, p.payment_date
FROM reservations r
JOIN parking_slots s ON r.parking_slot_id = s.parking_slot_id
JOIN vehicles v ON r.vehicle_id = v.vehicle_id
JOIN Vehicle_Models m ON v.model_id = m.model_id
LEFT JOIN payments p ON r.reservation_id = p.reservation_id
WHERE r.user_id = ?
ORDER BY r.start_time DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
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
<h2 class="text-warning mb-4">My Bookings</h2>
<div class="mb-3 p-3 rounded shadow-sm" style="background:#fff; color:#222; border:1px solid #ddd;">
  <div class="d-flex flex-wrap align-items-center justify-content-between">
    <div class="form-inline mb-2 mb-md-0">
      <label for="statusFilter" class="mr-2 font-weight-bold" style="color:#222;">Filter by Status:</label>
      <select id="statusFilter" class="form-control form-control-sm mr-3">
        <option value="">All</option>
        <option value="pending">Pending</option>
        <option value="confirmed">Confirmed</option>
        <option value="completed">Completed</option>
        <option value="cancelled">Cancelled</option>
        <option value="expired">Expired</option>
        <option value="void">Void</option>
      </select>
    </div>
    <div class="form-inline">
      <input type="text" id="searchInput" class="form-control form-control-sm mr-2" placeholder="Search bookings...">
      <button class="btn btn-sm btn-outline-dark" id="clearSearch">Clear</button>
    </div>
  </div>
</div>
<div class="table-responsive bg-dark rounded p-3">
<table class="table table-hover table-dark table-bordered align-middle text-center" id="bookingsTable">
  <thead>
    <tr>
      <th>Ref #</th>
      <th>Slot</th>
      <th>Vehicle</th>
      <th>Start</th>
      <th>End</th>
      <th>Duration</th>
      <th>Reservation Status</th>
      <th>Amount</th>
      <th>Payment Status</th>
      <th>Payment Method</th>
      <th>Payment Date</th>
    </tr>
  </thead>
  <tbody>
    <?php if (count($bookings) === 0): ?>
      <tr><td colspan="11" class="text-center">No bookings found.</td></tr>
    <?php else: foreach ($bookings as $b):
      $isConfirmed = ($b['status'] === 'confirmed');
      $now = date('Y-m-d H:i:s');
      $showTimer = $isConfirmed && $b['end_time'] > $now && $b['start_time'] <= $now;
      $rowData = htmlspecialchars(json_encode($b));
      // Calculate remaining time for duration column
      $remaining = '';
      if ($isConfirmed && $b['end_time'] > $now) {
        $end = new DateTime($b['end_time']);
        $nowDT = new DateTime($now);
        $interval = $nowDT->diff($end);
        if ($interval->days > 0) {
          $remaining = $interval->days . ' day' . ($interval->days > 1 ? 's' : '');
          if ($interval->h > 0) {
            $remaining .= ' ' . $interval->h . ' hour' . ($interval->h > 1 ? 's' : '');
          }
          $remaining .= ' left';
        } elseif ($interval->h > 0) {
          $remaining = $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' left';
        } elseif ($interval->i > 0) {
          $remaining = $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' left';
        } else {
          $remaining = $interval->s . ' second' . ($interval->s > 1 ? 's' : '') . ' left';
        }
      }
    ?>
      <tr class="booking-row" data-booking='<?= $rowData ?>'>
        <td><?= htmlspecialchars($b['reservation_id']) ?></td>
        <td><?= htmlspecialchars($b['slot_number']) ?> (<?= htmlspecialchars($b['slot_type']) ?>)</td>
        <td><?= htmlspecialchars($b['brand'].' '.$b['model'].' - '.$b['plate_number']) ?></td>
        <td><?= htmlspecialchars($b['start_time']) ?></td>
        <td><?= htmlspecialchars($b['end_time']) ?></td>
        <td>
          <?php
            // Display original duration with unit
            $durationText = $b['duration'];
            // Try to infer unit (hours/days) from the value
            if (is_numeric($durationText)) {
              if ($durationText == 1) {
                $durationText .= ' hour';
              } elseif ($durationText < 24) {
                $durationText .= ' hours';
              } elseif ($durationText % 24 == 0) {
                $days = $durationText / 24;
                $durationText = $days . ' day' . ($days > 1 ? 's' : '');
              } else {
                $days = floor($durationText / 24);
                $hours = $durationText % 24;
                $durationText = $days . ' day' . ($days > 1 ? 's' : '');
                if ($hours > 0) $durationText .= ' ' . $hours . ' hour' . ($hours > 1 ? 's' : '');
              }
            }
            echo htmlspecialchars($durationText);
          ?>
          <?php if ($isConfirmed && $b['end_time'] > $now && $b['start_time'] <= $now): ?>
            <?php
              $end = new DateTime($b['end_time']);
              $nowDT = new DateTime($now);
              $interval = $nowDT->diff($end);
              $parts = [];
              if ($interval->days > 0) $parts[] = $interval->days . ' day' . ($interval->days > 1 ? 's' : '');
              if ($interval->h > 0) $parts[] = $interval->h . ' hour' . ($interval->h > 1 ? 's' : '');
              if ($interval->i > 0) $parts[] = $interval->i . ' minute' . ($interval->i > 1 ? 's' : '');
              if ($interval->days == 0 && $interval->h == 0 && $interval->i == 0 && $interval->s > 0) $parts[] = $interval->s . ' second' . ($interval->s > 1 ? 's' : '');
              $remaining = $parts ? implode(' ', $parts) . ' left' : '';
            ?>
            <?php if ($remaining): ?>
              <br><span class="badge bg-info text-dark small">Remaining: <?= $remaining ?></span>
            <?php endif; ?>
          <?php endif; ?>
        </td>
        <td>
          <?php
            $status = $b['status'];
            $badge = 'secondary';
            if ($status === 'pending') $badge = 'warning';
            elseif ($status === 'confirmed') $badge = 'success';
            elseif ($status === 'cancelled' || $status === 'void') $badge = 'danger';
            elseif ($status === 'completed') $badge = 'primary';
            elseif ($status === 'expired') $badge = 'dark';
          ?>
          <span class="badge bg-<?= $badge ?> text-uppercase"><?= htmlspecialchars($status) ?></span>
        </td>
        <td>₱<?= number_format($b['amount'],2) ?></td>
        <td>
          <?php
            $pay = $b['payment_status'];
            $payBadge = 'secondary';
            if ($pay === 'pending') $payBadge = 'warning';
            elseif ($pay === 'successful') $payBadge = 'success';
            elseif ($pay === 'failed' || $pay === 'refunded') $payBadge = 'danger';
          ?>
          <span class="badge bg-<?= $payBadge ?> text-uppercase"><?= $pay ? htmlspecialchars($pay) : 'N/A' ?></span>
        </td>
        <td><?= htmlspecialchars(ucfirst($b['method'])) ?></td>
        <td><?= htmlspecialchars($b['payment_date']) ?></td>
      </tr>
    <?php endforeach; endif; ?>
  </tbody>
</table>
</div>
<a href="dashboard.php" class="btn btn-secondary mt-4">Go back to Home</a>
</div>
<!-- Reservation Details Modal -->
<div class="modal fade" id="bookingModal" tabindex="-1" role="dialog" aria-labelledby="bookingModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content bg-dark text-light">
      <div class="modal-header">
        <h5 class="modal-title" id="bookingModalLabel">Reservation Details</h5>
        <button type="button" class="close text-light" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="modalBodyContent">
        <!-- Details will be injected here -->
      </div>
    </div>
  </div>
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

let reloadTriggered = false;
function updateTimers() {
  const timers = document.querySelectorAll('.timer');
  timers.forEach(function(timer) {
    // Parse as UTC to avoid timezone issues
    const end = new Date(timer.getAttribute('data-end').replace(' ', 'T'));
    const now = new Date();
    let diff = Math.floor((end.getTime() - now.getTime()) / 1000);
    if (diff > 0) {
      const h = Math.floor(diff / 3600);
      diff %= 3600;
      const m = Math.floor(diff / 60);
      const s = diff % 60;
      timer.textContent = `${h}h ${m}m ${s}s left`;
    } else {
      if (!timer.classList.contains('expired') && !reloadTriggered) {
        timer.textContent = 'Expired';
        timer.classList.add('expired');
        reloadTriggered = true;
        // Show spinner
        timer.innerHTML = '<span class="spinner-border spinner-border-sm text-warning" role="status"></span> Updating...';
        // Use vanilla JS for AJAX
        const reservationId = timer.id.replace('timer-', '');
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'update_reservation_status.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
          if (xhr.readyState === 4) {
            location.reload();
          }
        };
        xhr.send('reservation_id=' + encodeURIComponent(reservationId));
      }
    }
  });
}
setInterval(updateTimers, 1000);
document.addEventListener('DOMContentLoaded', updateTimers);

// Modal and row click logic
const bookingsTable = document.getElementById('bookingsTable');
const bookingModal = document.getElementById('bookingModal');
const modalBodyContent = document.getElementById('modalBodyContent');
let timerInterval = null;

function formatDateTime(dt) {
  if (!dt) return '-';
  const d = new Date(dt.replace(' ', 'T'));
  return d.toLocaleString();
}

function showBookingDetails(booking) {
  let html = `<div><strong>Ref #:</strong> ${booking.reservation_id}</div>`;
  html += `<div><strong>Slot:</strong> ${booking.slot_number} (${booking.slot_type})</div>`;
  html += `<div><strong>Vehicle:</strong> ${booking.brand} ${booking.model} - ${booking.plate_number}</div>`;
  html += `<div><strong>Start:</strong> ${formatDateTime(booking.start_time)}</div>`;
  html += `<div><strong>End:</strong> ${formatDateTime(booking.end_time)}</div>`;
  html += `<div><strong>Duration:</strong> ${booking.duration}</div>`;
  html += `<div><strong>Reservation Status:</strong> <span class='badge bg-secondary text-uppercase'>${booking.status}</span></div>`;
  html += `<div><strong>Amount:</strong> ₱${Number(booking.amount).toFixed(2)}</div>`;
  html += `<div><strong>Payment Status:</strong> <span class='badge bg-secondary text-uppercase'>${booking.payment_status || 'N/A'}</span></div>`;
  html += `<div><strong>Payment Method:</strong> ${booking.method ? booking.method.charAt(0).toUpperCase() + booking.method.slice(1) : '-'}</div>`;
  html += `<div><strong>Payment Date:</strong> ${formatDateTime(booking.payment_date)}</div>`;
  // Timer for confirmed/ongoing
  if (booking.status === 'confirmed') {
    html += `<div class='mt-3'><strong>Time Remaining:</strong> <span id='modalTimer'></span></div>`;
  }
  modalBodyContent.innerHTML = html;
  if (timerInterval) clearInterval(timerInterval);
  if (booking.status === 'confirmed') {
    function updateModalTimer() {
      const end = new Date(booking.end_time.replace(' ', 'T'));
      const now = new Date();
      let diff = Math.floor((end.getTime() - now.getTime()) / 1000);
      if (diff > 0) {
        const h = Math.floor(diff / 3600);
        diff %= 3600;
        const m = Math.floor(diff / 60);
        const s = diff % 60;
        document.getElementById('modalTimer').textContent = `${h}h ${m}m ${s}s left`;
      } else {
        document.getElementById('modalTimer').textContent = 'Expired';
        clearInterval(timerInterval);
      }
    }
    updateModalTimer();
    timerInterval = setInterval(updateModalTimer, 1000);
  }
}

bookingsTable.querySelectorAll('.booking-row').forEach(function(row) {
  row.addEventListener('click', function() {
    const booking = JSON.parse(row.getAttribute('data-booking'));
    showBookingDetails(booking);
    $(bookingModal).modal('show');
  });
});

// Sorting, filtering, and searching
const table = document.getElementById('bookingsTable');
const statusFilter = document.getElementById('statusFilter');
const searchInput = document.getElementById('searchInput');
const clearSearch = document.getElementById('clearSearch');

function normalizeText(text) {
  return (text || '').toString().toLowerCase().trim();
}

function filterAndSearchRows() {
  const status = statusFilter.value;
  const search = normalizeText(searchInput.value);
  table.querySelectorAll('tbody tr.booking-row').forEach(row => {
    const booking = JSON.parse(row.getAttribute('data-booking'));
    let show = true;
    if (status && booking.status !== status) show = false;
    if (search) {
      const values = Object.values(booking).map(v => normalizeText(v));
      if (!values.some(v => v.includes(search))) show = false;
    }
    row.style.display = show ? '' : 'none';
  });
}

statusFilter.addEventListener('change', filterAndSearchRows);
searchInput.addEventListener('input', filterAndSearchRows);
clearSearch.addEventListener('click', function() {
  searchInput.value = '';
  filterAndSearchRows();
});

// Sorting
let sortCol = null, sortAsc = true;
table.querySelectorAll('thead th').forEach((th, idx) => {
  th.addEventListener('click', function() {
    if (sortCol === idx) sortAsc = !sortAsc;
    else { sortCol = idx; sortAsc = true; }
    const rows = Array.from(table.querySelectorAll('tbody tr.booking-row'));
    rows.sort((a, b) => {
      const tdA = a.children[idx].textContent.trim().toLowerCase();
      const tdB = b.children[idx].textContent.trim().toLowerCase();
      if (!isNaN(tdA) && !isNaN(tdB)) {
        return sortAsc ? tdA - tdB : tdB - tdA;
      }
      return sortAsc ? tdA.localeCompare(tdB) : tdB.localeCompare(tdA);
    });
    rows.forEach(row => table.querySelector('tbody').appendChild(row));
  });
});
</script>
</body>
</html>
