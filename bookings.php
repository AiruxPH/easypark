<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'client') {
  header("Location: index.php");
  exit();
}
require_once 'includes/db.php';
require_once 'includes/constants.php';
$user_id = $_SESSION['user_id'];

// Fetch all bookings for this user
// Fetch all bookings for this user
// Aggregating payments to prevent duplicates on extension (GROUP BY reservation_id)
$sql = "SELECT r.reservation_id, r.start_time, r.end_time, r.duration, r.status, s.slot_number, s.slot_type, v.plate_number, m.brand, m.model, 
        SUM(p.amount) as amount, 
        SUBSTRING_INDEX(GROUP_CONCAT(p.status ORDER BY p.payment_date DESC), ',', 1) as payment_status, 
        SUBSTRING_INDEX(GROUP_CONCAT(p.method ORDER BY p.payment_date DESC), ',', 1) as method, 
        MAX(p.payment_date) as payment_date,
        GROUP_CONCAT(CONCAT(p.amount, '|', p.payment_date, '|', p.status, '|', p.method) ORDER BY p.payment_date DESC SEPARATOR ';;') as payment_history
FROM reservations r
JOIN parking_slots s ON r.parking_slot_id = s.parking_slot_id
JOIN vehicles v ON r.vehicle_id = v.vehicle_id
JOIN Vehicle_Models m ON v.model_id = m.model_id
LEFT JOIN payments p ON r.reservation_id = p.reservation_id
WHERE r.user_id = ?
GROUP BY r.reservation_id, r.start_time, r.end_time, r.duration, r.status, s.slot_number, s.slot_type, v.plate_number, m.brand, m.model
ORDER BY r.start_time DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Get user profile pic and balance
$stmt = $pdo->prepare('SELECT image, coins FROM users WHERE user_id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$profilePic = (!empty($user['image']) && file_exists('images/' . $user['image'])) ? 'images/' . $user['image'] : 'images/default.jpg';
$user_balance = floatval($user['coins'] ?? 0);
?>
<script>
  // Pass balance to JS global scope
  window.USER_BALANCE = <?= $user_balance ?>;
</script>
<!DOCTYPE html>
<html lang="en">

<head>
  <title>My Bookings - EasyPark</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="icon" href="images/favicon.png" type="image/png">
  <style>
    .bg-car {
      background-image: url('images/bg-car.jpg');
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      background-attachment: fixed;
      min-height: 100vh;
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

    /* Glassmorphism Table */
    .glass-panel {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border-radius: 15px;
      border: 1px solid rgba(255, 255, 255, 0.2);
      box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
    }

    .table-glass {
      color: #fff;
      margin-bottom: 0;
    }

    .table-glass thead th {
      border-bottom: 2px solid rgba(255, 255, 255, 0.2);
      border-top: none;
      color: #f0a500;
      /* Primary/Warning color */
      font-weight: 600;
      cursor: pointer;
    }

    .table-glass td,
    .table-glass th {
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      vertical-align: middle;
    }

    .table-glass tbody tr:hover {
      background: rgba(255, 255, 255, 0.05);
    }

    /* Modal Upgrade */
    .modal-glass .modal-content {
      background: rgba(30, 30, 30, 0.95);
      backdrop-filter: blur(15px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 20px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
      color: #fff;
    }

    .modal-glass .modal-header {
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      padding: 1.5rem;
    }

    .modal-glass .modal-footer {
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      padding: 1rem 1.5rem;
    }

    .modal-glass .close {
      color: #fff;
      opacity: 0.7;
      text-shadow: none;
      transition: opacity 0.3s;
    }

    .modal-glass .close:hover {
      opacity: 1;
    }

    /* Detail Grid */
    .detail-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.5rem;
    }

    .detail-item {
      margin-bottom: 0.5rem;
    }

    .detail-label {
      font-size: 0.85rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: #aaa;
      display: block;
      margin-bottom: 0.2rem;
    }

    .detail-value {
      font-size: 1.1rem;
      font-weight: 500;
      color: #fff;
    }

    .btn-action {
      margin: 2px;
    }
  </style>
</head>

<body class="bg-car">
  <?php include 'includes/client_navbar.php'; ?>
  <div class="container py-5">
    <?php
    $hasOverdue = false;
    $overdueCount = 0;
    $current_time = new DateTime();
    foreach ($bookings as $b) {
      if ($b['status'] === 'ongoing' && new DateTime($b['end_time']) < $current_time) {
        $hasOverdue = true;
        $overdueCount++;
      }
    }
    ?>

    <?php if ($hasOverdue): ?>
      <div class="alert alert-danger border-danger shadow-lg mb-4 d-flex align-items-center" role="alert"
        style="background: rgba(220, 53, 69, 0.2); backdrop-filter: blur(10px);">
        <i class="fas fa-exclamation-triangle fa-2x mr-3 text-danger"></i>
        <div>
          <h5 class="alert-heading font-weight-bold mb-1">Overdue Booking Detected!</h5>
          <p class="mb-0">You have <strong><?= $overdueCount ?></strong> active booking(s) that have exceeded the time
            limit. Overstay penalties are being applied.</p>
        </div>
      </div>
    <?php endif; ?>

    <h2 class="text-white mb-4" style="text-shadow: 0 2px 4px rgba(0,0,0,0.8);">My Bookings</h2>

    <?php
    // Determine default filter based on priority: Ongoing > Confirmed > Pending
    $defaultFilter = '';
    $hasOngoing = false;
    $hasConfirmed = false;
    $hasPending = false;

    foreach ($bookings as $b) {
      if ($b['status'] === 'ongoing')
        $hasOngoing = true;
      if ($b['status'] === 'confirmed')
        $hasConfirmed = true;
      if ($b['status'] === 'pending')
        $hasPending = true;
    }

    if ($hasOngoing)
      $defaultFilter = 'ongoing';
    elseif ($hasConfirmed)
      $defaultFilter = 'confirmed';
    elseif ($hasPending)
      $defaultFilter = 'pending';
    ?>

    <!-- Filter Panel -->
    <div class="glass-panel p-3 mb-4">
      <div class="d-flex flex-wrap align-items-center justify-content-between">
        <div class="form-inline mb-2 mb-md-0">
          <label for="statusFilter" class="mr-2 font-weight-bold text-white">Filter by Status:</label>
          <select id="statusFilter" class="form-control form-control-sm mr-3 bg-dark text-white border-secondary">
            <option value="">All</option>
            <option value="ongoing">Ongoing</option>
            <option value="pending">Pending</option>
            <option value="confirmed">Confirmed</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
            <option value="expired">Expired</option>
            <option value="void">Void</option>
          </select>
        </div>
        <div class="form-inline">
          <input type="text" id="searchInput"
            class="form-control form-control-sm mr-2 bg-dark text-white border-secondary"
            placeholder="Search bookings...">
          <button class="btn btn-sm btn-outline-light" id="clearSearch">Clear</button>
        </div>
      </div>
    </div>

    <!-- Table Panel -->
    <div class="glass-panel p-0 overflow-hidden">
      <div class="table-responsive">
        <table class="table table-glass align-middle text-center" id="bookingsTable">
          <thead>
            <tr>
              <th>Ref #</th>
              <th>Slot</th>
              <th>Vehicle</th>
              <th>Start</th>
              <th>End</th>
              <th>Duration</th>
              <th>Res. Status</th>
              <th>Amount</th>
              <th>Pay Status</th>
              <th>Method</th>
              <th>Pay Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($bookings) === 0): ?>
              <tr>
                <td colspan="12" class="text-center py-5 text-white-50">No bookings found.</td>
              </tr>
            <?php else:
              foreach ($bookings as $b):
                $isConfirmed = ($b['status'] === 'confirmed' || $b['status'] === 'ongoing');
                $now = date('Y-m-d H:i:s');
                $showTimer = $isConfirmed && $b['end_time'] > $now && $b['start_time'] <= $now;

                // Inject Rate into booking data for JS usage
                $hour_rate = 0;
                $day_rate = 0;
                if (defined('SLOT_RATES') && isset(SLOT_RATES[$b['slot_type']])) {
                  $hour_rate = SLOT_RATES[$b['slot_type']]['hour'] ?? 0;
                  $day_rate = SLOT_RATES[$b['slot_type']]['day'] ?? ($hour_rate * 24);
                }
                $b['hour_rate'] = $hour_rate;
                $b['day_rate'] = $day_rate;

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
                  <td class="font-weight-bold opacity-75"><?= htmlspecialchars($b['reservation_id']) ?></td>
                  <td><?= htmlspecialchars($b['slot_number']) ?> <small>(<?= htmlspecialchars($b['slot_type']) ?>)</small>
                  </td>
                  <td>
                    <div style="line-height:1.2;">
                      <small class="d-block text-white-50"><?= htmlspecialchars($b['brand'] . ' ' . $b['model']) ?></small>
                      <span><?= htmlspecialchars($b['plate_number']) ?></span>
                    </div>
                  </td>
                  <td class="small"><?= htmlspecialchars($b['start_time']) ?></td>
                  <td class="small"><?= htmlspecialchars($b['end_time']) ?></td>
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
                        if ($hours > 0)
                          $durationText .= ' ' . $hours . ' hour' . ($hours > 1 ? 's' : '');
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
                      if ($interval->days > 0)
                        $parts[] = $interval->days . ' day' . ($interval->days > 1 ? 's' : '');
                      if ($interval->h > 0)
                        $parts[] = $interval->h . ' hour' . ($interval->h > 1 ? 's' : '');
                      if ($interval->i > 0)
                        $parts[] = $interval->i . ' minute' . ($interval->i > 1 ? 's' : '');
                      if ($interval->days == 0 && $interval->h == 0 && $interval->i == 0 && $interval->s > 0)
                        $parts[] = $interval->s . ' second' . ($interval->s > 1 ? 's' : '');
                      $remaining = $parts ? implode(' ', $parts) . ' left' : '';
                      ?>
                      <?php
                      // Calculate rate for overstay penalty
                      $rate = 0;
                      if (defined('SLOT_RATES') && isset(SLOT_RATES[$b['slot_type']]['hour'])) {
                        $rate = SLOT_RATES[$b['slot_type']]['hour'];
                      }
                      $timerEnd = $b['end_time'];
                      ?>
                      <div class="mt-1">
                        <span id="timer-<?= $b['reservation_id'] ?>" class="timer badge badge-info"
                          data-end="<?= $timerEnd ?>" data-rate="<?= $rate ?>">Checking...</span>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php
                    $status = $b['status'];
                    $badge = 'secondary';

                    // Check for Overdue
                    $isOverdue = ($status === 'ongoing' && $b['end_time'] < date('Y-m-d H:i:s'));

                    if ($isOverdue) {
                      $status = 'OVERDUE';
                      $badge = 'danger';
                    } elseif ($status === 'pending') {
                      $badge = 'warning';
                    } elseif ($status === 'confirmed' || $status === 'ongoing') {
                      $badge = 'success';
                    } elseif ($status === 'cancelled' || $status === 'void') {
                      $badge = 'danger';
                    } elseif ($status === 'completed') {
                      $badge = 'primary';
                    } elseif ($status === 'expired') {
                      $badge = 'dark';
                    }
                    ?>
                    <span class="badge badge-<?= $badge ?> text-uppercase"><?= htmlspecialchars($status) ?></span>
                  </td>
                  <td><span class="text-warning font-weight-bold">🪙</span> <?= number_format($b['amount'], 2) ?></td>
                  <td>
                    <?php
                    $pay = $b['payment_status'];
                    $payBadge = 'secondary';
                    if ($pay === 'pending')
                      $payBadge = 'warning';
                    elseif ($pay === 'successful')
                      $payBadge = 'success';
                    elseif ($pay === 'failed' || $pay === 'refunded')
                      $payBadge = 'danger';
                    ?>
                    <span class="badge badge-<?= $payBadge ?>"><?= $pay ? htmlspecialchars($pay) : 'N/A' ?></span>
                  </td>
                  <td class="small"><?= htmlspecialchars(ucfirst($b['method'])) ?></td>
                  <td class="small"><?= htmlspecialchars($b['payment_date']) ?></td>
                  <td class="text-nowrap">
                    <!-- View Button -->
                    <button class="btn btn-sm btn-info btn-action action-view" title="View Details">
                      <i class="fa fa-eye"></i>
                    </button>

                    <?php
                    $status = $b['status'];
                    // Show Cancel for pending or confirmed
                    if ($status === 'pending' || $status === 'confirmed'):
                      ?>
                      <button class="btn btn-sm btn-danger btn-action action-cancel" data-id="<?= $b['reservation_id'] ?>"
                        title="Cancel Booking">
                        <i class="fa fa-times"></i>
                      </button>
                    <?php elseif ($status === 'ongoing'): ?>
                      <button class="btn btn-sm btn-success btn-action action-complete" data-id="<?= $b['reservation_id'] ?>"
                        title="Complete Booking">
                        <i class="fa fa-check"></i>
                      </button>
                    <?php endif; ?>

                    <?php if ($status === 'confirmed' || $status === 'ongoing'): ?>
                      <button class="btn btn-sm btn-warning btn-action action-extend" data-id="<?= $b['reservation_id'] ?>"
                        data-booking='<?= $rowData ?>' title="Extend Booking">
                        <i class="fa fa-clock"></i>
                      </button>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <a href="dashboard.php" class="btn btn-secondary mt-4">Go back to Home</a>
  </div>
  <!-- Reservation Details Modal -->
  <div class="modal fade modal-glass" id="bookingModal" tabindex="-1" role="dialog" aria-labelledby="bookingModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document"> <!-- Default width -->
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title font-weight-bold text-warning" id="bookingModalLabel"><i class="fa fa-ticket mr-2"></i>
            Reservation Details</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body p-4" id="modalBodyContent">
          <!-- Details will be injected here -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary rounded-pill px-4" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
  <!-- Action Confirmation Modal -->
  <div class="modal fade modal-glass" id="actionModal" tabindex="-1" role="dialog" aria-labelledby="actionModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content"> <!-- Keeping internal bg-dark logic or overriding with CSS above -->
        <!-- The CSS above targets .modal-glass .modal-content so it overrides bg-dark if applied correctly -->
        <div class="modal-header">
          <h5 class="modal-title text-warning" id="actionModalLabel">Confirm Action</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body p-4" id="actionModalBody">
          <!-- Confirmation text injected here -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary rounded-pill" data-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary rounded-pill px-4" id="actionModalConfirmBtn">Confirm</button>
        </div>
      </div>
    </div>
  </div>
  </div>
  </div>

  <!-- EXTEND MODAL -->
  <div class="modal fade modal-glass" id="extendModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title text-warning"><i class="fas fa-clock mr-2"></i> Extend Booking</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body p-4">
          <input type="hidden" id="extendResId">
          <p class="text-white-50 mb-3">Add more time to your parking session.</p>

          <div class="form-group">
            <label class="text-white font-weight-bold">Extend Duration</label>
            <select id="extendDuration" class="form-control bg-dark text-white border-secondary">
              <option value="0.5">30 Minutes</option>
              <option value="1">1 Hour</option>
              <option value="2">2 Hours</option>
              <option value="3">3 Hours</option>
              <option value="4">4 Hours</option>
              <option value="5">5 Hours</option>
              <option value="24" class="font-weight-bold">1 Day (24 Hours)</option>
              <option value="48" class="font-weight-bold">2 Days (48 Hours)</option>
              <option value="72" class="font-weight-bold">3 Days (72 Hours)</option>
            </select>
          </div>

          <div class="pricing-box p-3 rounded" style="background: rgba(255,255,255,0.05);">
            <div class="d-flex justify-content-between mb-1">
              <span class="text-white-50">Current End Time:</span>
              <span class="text-white" id="extendCurrentEnd">-</span>
            </div>
            <div class="d-flex justify-content-between mb-1">
              <span class="text-white-50">New End Time:</span>
              <span class="text-success font-weight-bold" id="extendNewEnd">-</span>
            </div>
            <div class="d-flex justify-content-between border-top border-secondary pt-2 mt-2">
              <span class="text-white">Additional Cost:</span>
              <span class="text-warning font-weight-bold" id="extendCost">-</span>
            </div>
            <small class="text-white-50 d-block mt-1 text-right">Based on rate: 🪙<span
                id="extendRate"></span>/hr</small>
          </div>

          <div id="extendError" class="alert alert-danger mt-3 d-none"></div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary rounded-pill" data-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary rounded-pill px-4" id="btnConfirmExtend">Pay & Extend</button>
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
      timers.forEach(function (timer) {
        // Parse as UTC to avoid timezone issues
        const end = new Date(timer.getAttribute('data-end').replace(' ', 'T'));
        const now = new Date();
        const diffInSeconds = Math.floor((end.getTime() - now.getTime()) / 1000);

        if (diffInSeconds > 0) {
          // Future end time (Standard count down)
          let diff = diffInSeconds;
          const h = Math.floor(diff / 3600);
          diff %= 3600;
          const m = Math.floor(diff / 60);
          const s = diff % 60;
          timer.textContent = `${h}h ${m}m ${s}s left`;
          timer.classList.remove('text-danger', 'font-weight-bold');
        } else {
          // Overdue logic (Count up)
          let diff = Math.abs(diffInSeconds);
          const h = Math.floor(diff / 3600);
          diff %= 3600;
          const m = Math.floor(diff / 60);
          const s = diff % 60;

          // Calculate estimated penalty (assuming generic rate or need to pass rate)
          // For simplicity in JS display, we just show time. 
          // Ideally we pass rate via data attribute.
          const ratePerHour = parseFloat(timer.getAttribute('data-rate')) || 0;
          // Fee is charged per started hour of overstay (1s over = 1 hour charge)
          const overstayHours = Math.ceil(Math.abs(diffInSeconds) / 3600);
          const penalty = (overstayHours * ratePerHour).toFixed(2);

          timer.innerHTML = `<span class="text-danger font-weight-bold">OVERDUE: ${h}h ${m}m ${s}s</span><br><small class="text-danger">Est. Penalty: 🪙${penalty}</small>`;
          // Do NOT reload or auto-complete. Let it tick.
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
      // Helper for badges
      const getStatusBadge = (status) => {
        const map = {
          'pending': 'warning', 'confirmed': 'success', 'ongoing': 'success',
          'completed': 'primary', 'cancelled': 'danger', 'expired': 'dark', 'void': 'danger', 'overdue': 'danger'
        };
        const color = map[status] || 'secondary';
        return `<span class="badge badge-${color} text-uppercase px-2 py-1 small">${status}</span>`;
      };

      let html = `
        <div class="row mb-2">
            <div class="col-12 d-flex justify-content-between align-items-center border-bottom border-secondary pb-2">
                <h5 class="mb-0 text-white">Ref: <span class="text-warning">#${booking.reservation_id}</span></h5>
                ${getStatusBadge(booking.status)}
            </div>
        </div>

        <div class="row">
            <!-- Left Column: Slot & Vehicle -->
            <div class="col-6 border-right border-secondary pr-2">
                <h6 class="small text-uppercase text-white-50 mb-2"><i class="fas fa-parking mr-1"></i> Parking</h6>
                
                <div class="d-flex align-items-center mb-2 p-2 rounded" style="background: rgba(255,255,255,0.05);">
                    <div class="text-center w-100">
                        <span class="h3 font-weight-bold text-success d-block mb-0" style="line-height:1;">${booking.slot_number}</span>
                        <small class="text-white-50" style="font-size:0.75rem">${booking.slot_type}</small>
                    </div>
                </div>

                <h6 class="small text-uppercase text-white-50 mt-3 mb-2"><i class="fas fa-car mr-1"></i> Vehicle</h6>
                <div class="pl-1">
                    <p class="mb-0 text-white font-weight-bold small">${booking.brand} ${booking.model}</p>
                    <small class="text-white-50">${booking.plate_number}</small>
                </div>
            </div>

            <!-- Right Column: Timing & Payment -->
            <div class="col-6 pl-3">
                 <h6 class="small text-uppercase text-white-50 mb-2"><i class="fas fa-clock mr-1"></i> Timing</h6>
                 <div class="pl-1 mb-3 small">
                    <div class="mb-1">
                        <span class="text-white-50">Start:</span> <span class="text-white">${formatDateTime(booking.start_time)}</span>
                    </div>
                    <div class="mb-1">
                        <span class="text-white-50">End:</span> <span class="text-white">${formatDateTime(booking.end_time)}</span>
                    </div>
                    <div>
                         <span class="text-white-50">Dur:</span> <span class="text-info">${booking.duration} hrs</span>
                    </div>
                 </div>

                 <h6 class="small text-uppercase text-white-50 mb-2"><i class="fas fa-receipt mr-1"></i> Payment</h6>
                 <div class="p-2 rounded small mb-2" style="background: rgba(255,255,255,0.05);">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-white-50">Total Paid</span>
                        <span class="text-warning font-weight-bold">🪙 ${Number(booking.amount).toFixed(2)}</span>
                    </div>
                 </div>

                 <!-- Payment History Table -->
                 <div class="table-responsive rounded" style="background: rgba(255,255,255,0.02); max-height: 120px; overflow-y: auto;">
                    <table class="table table-sm table-borderless mb-0 small text-white-50">
                        <thead class="border-bottom border-secondary" style="background: rgba(0,0,0,0.2);">
                            <tr>
                                <th class="py-1 pl-2">Date</th>
                                <th class="py-1">Amt</th>
                                <th class="py-1 pr-2 text-right">Method</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${(() => {
                                if (!booking.payment_history) return '<tr><td colspan="3" class="text-center py-2">No history</td></tr>';
                                const rows = booking.payment_history.split(';;');
                                return rows.map(r => {
                                    const cols = r.split('|');
                                    // cols: 0=amount, 1=date, 2=status, 3=method
                                    if (cols.length < 2) return '';
                                    const amt = parseFloat(cols[0]).toFixed(2);
                                    const date = new Date(cols[1].replace(' ', 'T')).toLocaleDateString('en-US', {month:'short', day:'numeric'});
                                    const method = cols[3] || 'N/A';
                                    return `
                                        <tr>
                                            <td class="py-1 pl-2">${date}</td>
                                            <td class="py-1 text-white">🪙${amt}</td>
                                            <td class="py-1 pr-2 text-right text-capitalize">${method}</td>
                                        </tr>
                                    `;
                                }).join('');
                            })()}
                        </tbody>
                    </table>
                 </div>
            </div>
        </div>
      `;

      // Timer Section (if active)
      if (booking.status === 'confirmed' || booking.status === 'ongoing') {
        html += `
            <div class="row mt-3">
                <div class="col-12 text-center pt-2 border-top border-secondary">
                    <small class="text-white-50 d-block mb-1">Time Remaining</small>
                    <h4 class="text-info font-weight-bold mb-0" id="modalTimer">Loading...</h4>
                </div>
            </div>
        `;
      }

      modalBodyContent.innerHTML = html;

      // ... (Timer Logic remains same) ...
      if (timerInterval) clearInterval(timerInterval);
      if (booking.status === 'confirmed' || booking.status === 'ongoing') {
        function updateModalTimer() {
          const end = new Date(booking.end_time.replace(' ', 'T'));
          const now = new Date();
          let diff = Math.floor((end.getTime() - now.getTime()) / 1000); // Seconds

          if (diff > 0) {
            const h = Math.floor(diff / 3600);
            diff %= 3600;
            const m = Math.floor(diff / 60);
            const s = diff % 60;
            const el = document.getElementById('modalTimer');
            if (el) el.textContent = `${h}h ${m}m ${s}s`;
          } else {
            const el = document.getElementById('modalTimer');
            if (el) {
              el.textContent = 'Expired / Overdue';
              el.classList.remove('text-info');
              el.classList.add('text-danger');
            }
            clearInterval(timerInterval);
          }
        }
        updateModalTimer();
        timerInterval = setInterval(updateModalTimer, 1000);
      }
    }

    // Action: View Details
    $(document).on('click', '.action-view', function (e) {
      e.stopPropagation();
      const row = $(this).closest('tr');
      const booking = JSON.parse(row.attr('data-booking'));
      showBookingDetails(booking);
      $(bookingModal).modal('show');
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

    // Set default filter from PHP logic if applicable
    const defaultFilterVal = "<?= $defaultFilter ?>";
    if (defaultFilterVal) {
      statusFilter.value = defaultFilterVal;
    }

    // Initial filter run
    filterAndSearchRows();

    statusFilter.addEventListener('change', filterAndSearchRows);
    searchInput.addEventListener('input', filterAndSearchRows);
    clearSearch.addEventListener('click', function () {
      searchInput.value = '';
      filterAndSearchRows();
    });

    // Sorting
    let sortCol = null, sortAsc = true;
    table.querySelectorAll('thead th').forEach((th, idx) => {
      th.addEventListener('click', function () {
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

    let actionType = null;
    let actionReservationId = null;
    const actionModal = $('#actionModal');
    const actionModalBody = document.getElementById('actionModalBody');
    const actionModalConfirmBtn = document.getElementById('actionModalConfirmBtn');

    $(document).on('click', '.action-cancel', function (e) {
      e.stopPropagation();
      actionType = 'cancel';
      actionReservationId = $(this).data('id');
      actionModalBody.innerHTML = 'Are you sure you want to <span class="text-danger font-weight-bold">cancel</span> this booking? This action cannot be undone.';
      actionModalConfirmBtn.className = 'btn btn-danger';
      actionModalConfirmBtn.textContent = 'Yes, Cancel';
      actionModal.modal('show');
    });

    $(document).on('click', '.action-complete', function (e) {
      e.stopPropagation();
      actionType = 'complete';
      actionReservationId = $(this).data('id');

      // Get booking data from row
      const row = $(this).closest('tr');
      const booking = JSON.parse(row.attr('data-booking'));

      const now = new Date();
      const end = new Date(booking.end_time.replace(' ', 'T'));

      let bodyHtml = 'Mark this booking as <span class="text-success font-weight-bold">complete</span>?';

      // Check for overstay
      if (now > end) {
        const diffInSeconds = Math.floor((now.getTime() - end.getTime()) / 1000);
        const overHours = Math.ceil(diffInSeconds / 3600);
        const rate = parseFloat(booking.rate) || 0;
        const penalty = (overHours * rate).toFixed(2);

        bodyHtml = `
            <div class="alert alert-danger">
                <h6 class="font-weight-bold"><i class="fas fa-exclamation-triangle"></i> Overdue Warning</h6>
                <p class="mb-1">This booking is <strong>overdue</strong> by approximately <strong>${overHours} hour(s)</strong>.</p>
                <p class="mb-0">A deduction of <strong>🪙${penalty} coins</strong> will be applied to your wallet.</p>
            </div>
            Mark as complete and pay penalty?
          `;
      }

      actionModalBody.innerHTML = bodyHtml;
      actionModalConfirmBtn.className = 'btn btn-success';
      actionModalConfirmBtn.textContent = 'Yes, Complete';
      actionModal.modal('show');
    });

    actionModalConfirmBtn.onclick = function () {
      if (!actionType || !actionReservationId) return;
      actionModalConfirmBtn.disabled = true;
      actionModalConfirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';
      $.ajax({
        url: 'update_reservation_status.php',
        method: 'POST',
        data: {
          reservation_id: actionReservationId,
          action: actionType
        },
        success: function (resp) {
          actionModal.modal('hide');
          actionModalConfirmBtn.disabled = false;
          actionModalConfirmBtn.innerHTML = actionType === 'cancel' ? 'Yes, Cancel' : 'Yes, Complete';
          location.reload();
        },
        error: function () {
          actionModalBody.innerHTML = '<span class="text-danger">An error occurred. Please try again.</span>';
          actionModalConfirmBtn.disabled = false;
          actionModalConfirmBtn.innerHTML = actionType === 'cancel' ? 'Yes, Cancel' : 'Yes, Complete';
        }
      });
    };


    // ---------------------------------------------------------
    // EXTEND LOGIC
    // ---------------------------------------------------------
    const extendModal = $('#extendModal');
    const extendDuration = document.getElementById('extendDuration');
    const extendBtn = document.getElementById('btnConfirmExtend');
    let extendBookingData = null;

    $(document).on('click', '.action-extend', function (e) {
      e.stopPropagation();
      const btn = $(this);
      extendBookingData = btn.data('booking'); // Object

      if (typeof extendBookingData === 'string') {
        extendBookingData = JSON.parse(extendBookingData);
      }

      $('#extendResId').val(extendBookingData.reservation_id);
      $('#extendRate').text(`${extendBookingData.hour_rate}/hr | ${extendBookingData.day_rate}/day`);
      $('#extendCurrentEnd').text(formatDateTime(extendBookingData.end_time));

      updateExtendCalculations();
      $('#extendError').addClass('d-none');
      extendBtn.disabled = false;
      extendBtn.innerHTML = 'Pay & Extend';

      extendModal.modal('show');
    });

    extendDuration.addEventListener('change', updateExtendCalculations);

    function updateExtendCalculations() {
      if (!extendBookingData) return;

      const hoursToAdd = parseFloat(extendDuration.value);
      const hourRate = parseFloat(extendBookingData.hour_rate) || 0;
      const dayRate = parseFloat(extendBookingData.day_rate) || (hourRate * 24);

      // Mixed Rate Calculation
      const days = Math.floor(hoursToAdd / 24);
      const remHours = hoursToAdd - (days * 24);
      const cost = (days * dayRate) + (remHours * hourRate);

      $('#extendCost').html('🪙 ' + cost.toFixed(2));
      $('#extendRate').text(`${dayRate}/day | ${hourRate}/hr`); // Update rate display dynamically if needed, or keep static

      // New End Time
      const currentEnd = new Date(extendBookingData.end_time.replace(' ', 'T'));
      const newEnd = new Date(currentEnd.getTime() + (hoursToAdd * 60 * 60 * 1000));
      $('#extendNewEnd').text(newEnd.toLocaleString());

      // VALIDATION: Check User Balance
      const balance = window.USER_BALANCE || 0;
      const errorDiv = $('#extendError');
      const submitBtn = $('#btnConfirmExtend');

      if (cost > balance) {
        errorDiv.removeClass('d-none').html(`<i class="fas fa-exclamation-circle"></i> Insufficient Balance. You have <strong>🪙${balance.toFixed(2)}</strong>, but this extension costs <strong>🪙${cost.toFixed(2)}</strong>.`);
        submitBtn.prop('disabled', true);
      } else {
        errorDiv.addClass('d-none');
        submitBtn.prop('disabled', false);
      }
    }

    extendBtn.addEventListener('click', function () {
      const rId = $('#extendResId').val();
      const hrs = extendDuration.value;

      extendBtn.disabled = true;
      extendBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';
      $('#extendError').addClass('d-none');

      $.ajax({
        url: 'update_reservation_status.php',
        method: 'POST',
        data: {
          action: 'extend',
          reservation_id: rId,
          duration: hrs
        },
        dataType: 'json',
        success: function (resp) {
          if (resp.success) {
            location.reload();
          } else {
            $('#extendError').text(resp.message || 'Failed to extend.').removeClass('d-none');
            extendBtn.disabled = false;
            extendBtn.innerHTML = 'Pay & Extend';
          }
        },
        error: function () {
          $('#extendError').text('System error. Please try again.').removeClass('d-none');
          extendBtn.disabled = false;
          extendBtn.innerHTML = 'Pay & Extend';
        }
      });
    });

  </script>
</body>

</html>