<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    header("Location: ../index.php");
    exit();
}
require_once '../db.php';

// Fetch staff profile info
$staff_id = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT first_name, middle_name, last_name, email, phone, image FROM users WHERE user_id = ?');
$stmt->execute([$staff_id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
if (isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    $update_stmt = $pdo->prepare('UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, phone = ? WHERE user_id = ?');
    $update_stmt->execute([$first_name, $middle_name, $last_name, $phone, $staff_id]);
    header('Location: staff-dashboard.php?profile_updated=1');
    exit();
}
// Handle profile picture upload (optional)
if (isset($_POST['upload_pic']) && isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
    $fileTmp = $_FILES['profile_pic']['tmp_name'];
    $fileName = basename($_FILES['profile_pic']['name']);
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (in_array($fileExt, $allowed)) {
        $newName = 'profile_staff_' . $staff_id . '_' . time() . '.' . $fileExt;
        $targetPath = '../images/' . $newName;
        if (move_uploaded_file($fileTmp, $targetPath)) {
            // Remove old pic if not default
            if (!empty($staff['image']) && $staff['image'] !== 'default.jpg' && file_exists('../images/' . $staff['image'])) {
                unlink('../images/' . $staff['image']);
            }
            $stmt = $pdo->prepare('UPDATE users SET image = ? WHERE user_id = ?');
            $stmt->execute([$newName, $staff_id]);
            header('Location: staff-dashboard.php?profile_updated=1');
            exit();
        }
    }
}
// Handle profile picture delete (optional)
if (isset($_POST['delete_pic'])) {
    if (!empty($staff['image']) && $staff['image'] !== 'default.jpg' && file_exists('../images/' . $staff['image'])) {
        unlink('../images/' . $staff['image']);
    }
    $stmt = $pdo->prepare('UPDATE users SET image = NULL WHERE user_id = ?');
    $stmt->execute([$staff_id]);
    header('Location: staff-dashboard.php?profile_updated=1');
    exit();
}

// Handle confirm/cancel actions
if (isset($_POST['action']) && isset($_POST['reservation_id'])) {
    $reservation_id = intval($_POST['reservation_id']);
    if ($_POST['action'] === 'confirm') {
        // Confirm reservation
        $stmt = $pdo->prepare("UPDATE reservations SET status = 'confirmed' WHERE reservation_id = ?");
        $stmt->execute([$reservation_id]);
        // Confirm payment
        $stmt = $pdo->prepare("UPDATE payments SET status = 'successful' WHERE reservation_id = ?");
        $stmt->execute([$reservation_id]);
        // Set slot to reserved
        $stmt = $pdo->prepare("UPDATE parking_slots SET slot_status = 'reserved' WHERE parking_slot_id = (SELECT parking_slot_id FROM reservations WHERE reservation_id = ?)");
        $stmt->execute([$reservation_id]);
    } elseif ($_POST['action'] === 'cancel') {
        $stmt = $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE reservation_id = ?");
        $stmt->execute([$reservation_id]);
        // Set slot to available only if there are no other active reservations for this slot
        $stmt = $pdo->prepare("SELECT parking_slot_id FROM reservations WHERE reservation_id = ?");
        $stmt->execute([$reservation_id]);
        $slot_id = $stmt->fetchColumn();
        // Exclude the just-cancelled reservation from the count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE parking_slot_id = ? AND status IN ('confirmed', 'ongoing') AND end_time > NOW() AND reservation_id != ?");
        $stmt->execute([$slot_id, $reservation_id]);
        $active_count = $stmt->fetchColumn();
        if ($active_count == 0) {
            $stmt = $pdo->prepare("UPDATE parking_slots SET slot_status = 'available' WHERE parking_slot_id = ?");
            $stmt->execute([$slot_id]);
        }
    }
    header("Location: staff-dashboard.php");
    exit();
}

// Fetch only expected bookings (pending and upcoming)
$sql = "SELECT r.reservation_id, r.status, r.start_time, r.end_time, r.duration, s.slot_number, s.slot_type, v.plate_number, m.brand, m.model, u.first_name, u.last_name
FROM reservations r
JOIN parking_slots s ON r.parking_slot_id = s.parking_slot_id
JOIN vehicles v ON r.vehicle_id = v.vehicle_id
JOIN Vehicle_Models m ON v.model_id = m.model_id
JOIN users u ON r.user_id = u.user_id
WHERE r.status = 'pending' AND r.start_time >= NOW()
ORDER BY r.start_time ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pagination settings
$per_page = 6;
// Active Reservations Pagination
$active_page = isset($_GET['active_page']) ? max(1, intval($_GET['active_page'])) : 1;
$active_offset = ($active_page - 1) * $per_page;
$sql_active_count = "SELECT COUNT(*) FROM reservations WHERE status IN ('confirmed', 'ongoing') AND end_time > NOW()";
$active_total = $pdo->query($sql_active_count)->fetchColumn();
$sql_active = "SELECT r.reservation_id, r.status, r.start_time, r.end_time, r.duration, s.slot_number, s.slot_type, v.plate_number, m.brand, m.model, u.first_name, u.last_name
FROM reservations r
JOIN parking_slots s ON r.parking_slot_id = s.parking_slot_id
JOIN vehicles v ON r.vehicle_id = v.vehicle_id
JOIN Vehicle_Models m ON v.model_id = m.model_id
JOIN users u ON r.user_id = u.user_id
WHERE r.status IN ('confirmed', 'ongoing') AND r.end_time > NOW()
ORDER BY r.start_time ASC LIMIT $per_page OFFSET $active_offset";
$stmt = $pdo->prepare($sql_active);
$stmt->execute();
$active_reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
$active_total_pages = ceil($active_total / $per_page);

// History Pagination
$history_page = isset($_GET['history_page']) ? max(1, intval($_GET['history_page'])) : 1;
$history_offset = ($history_page - 1) * $per_page;
$sql_history_count = "SELECT COUNT(*) FROM reservations WHERE status IN ('completed', 'cancelled')";
$history_total = $pdo->query($sql_history_count)->fetchColumn();
$sql_history = "SELECT r.reservation_id, r.status, r.start_time, r.end_time, r.duration, s.slot_number, s.slot_type, v.plate_number, m.brand, m.model, u.first_name, u.last_name
FROM reservations r
JOIN parking_slots s ON r.parking_slot_id = s.parking_slot_id
JOIN vehicles v ON r.vehicle_id = v.vehicle_id
JOIN Vehicle_Models m ON v.model_id = m.model_id
JOIN users u ON r.user_id = u.user_id
WHERE r.status IN ('completed', 'cancelled')
ORDER BY r.end_time DESC LIMIT $per_page OFFSET $history_offset";
$stmt = $pdo->prepare($sql_history);
$stmt->execute();
$history_reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
$history_total_pages = ceil($history_total / $per_page);

// Parking Slots Pagination
$slots_page = isset($_GET['slots_page']) ? max(1, intval($_GET['slots_page'])) : 1;
$slots_offset = ($slots_page - 1) * $per_page;
$sql_slots_count = "SELECT COUNT(*) FROM parking_slots";
$slots_total = $pdo->query($sql_slots_count)->fetchColumn();
$sql_slots = "SELECT * FROM parking_slots ORDER BY slot_number ASC LIMIT $per_page OFFSET $slots_offset";
$stmt = $pdo->prepare($sql_slots);
$stmt->execute();
$all_slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
$slots_total_pages = ceil($slots_total / $per_page);

// Helper for slot color class
function getSlotColorClass($status) {
    switch (strtolower($status)) {
        case 'available': return 'border-success';
        case 'reserved': return 'border-warning';
        case 'occupied': return 'border-danger';
        default: return 'border-secondary';
    }
}

// Helper for pagination range
function getPaginationRange($current, $total, $max = 5) {
    $start = max(1, $current - floor($max/2));
    $end = min($total, $start + $max - 1);
    if ($end - $start + 1 < $max) {
        $start = max(1, $end - $max + 1);
    }
    return [$start, $end];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Staff Dashboard - EasyPark</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../css/bootstrap.min.css" >
<link rel="stylesheet" href="../css/font-awesome.min.css">
<style>
body {
  background: url('../bg-car.jpg') no-repeat center center fixed;
  background-size: cover;
  min-height: 100vh;
}
.bg-overlay {
  background: rgba(20, 20, 20, 0.85);
  min-height: 100vh;
  padding-bottom: 40px;
}
.header-bar {
  background: rgba(0,0,0,0.7);
  color: #ffc107;
  padding: 1.5rem 2rem 1rem 2rem;
  border-radius: 0 0 1rem 1rem;
  box-shadow: 0 4px 16px rgba(0,0,0,0.2);
  margin-bottom: 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.header-bar h2 {
  margin: 0;
  font-weight: 700;
  letter-spacing: 1px;
  font-size: 2.2rem;
}
.header-bar .fa {
  margin-right: 10px;
}
.section-card {
  background: rgba(255,255,255,0.97);
  border-radius: 1rem;
  box-shadow: 0 2px 12px rgba(0,0,0,0.12);
  margin-bottom: 2rem;
  padding: 2rem 1.5rem 1.5rem 1.5rem;
}
.table {
  background: #fff;
  border-radius: 0.5rem;
  overflow: hidden;
}
.table thead {
  background: #343a40;
  color: #ffc107;
}
.table-hover tbody tr:hover {
  background: #ffe082;
}
.card.bg-dark {
  background: linear-gradient(135deg, #232526 0%, #414345 100%);
  border-radius: 1rem;
  box-shadow: 0 2px 12px rgba(0,0,0,0.18);
}
.card-title, .card-text {
  color: #ffc107;
}
.btn-warning, .btn-success, .btn-danger, .btn-secondary {
  font-weight: 600;
  letter-spacing: 0.5px;
}
input.form-control, select.form-control {
  border-radius: 0.5rem;
}
::-webkit-scrollbar {
  width: 8px;
  background: #eee;
}
::-webkit-scrollbar-thumb {
  background: #bbb;
  border-radius: 4px;
}
@media (max-width: 767px) {
  .header-bar { flex-direction: column; align-items: flex-start; padding: 1rem; }
  .section-card { padding: 1rem; }
}
</style>
</head>
<body>
<div class="bg-overlay">
  <div class="header-bar mb-4">
    <h2><i class="fa fa-user-shield"></i> Staff Dashboard</h2>
    <a href="../logout.php" class="btn btn-secondary"><i class="fa fa-sign-out"></i> Logout</a>
  </div>
  <div class="container">
    <!-- Staff Profile Section -->
    <div class="section-card mb-4">
      <h4 class="mb-3 text-info"><i class="fa fa-user"></i> My Profile</h4>
      <?php if (isset($_GET['profile_updated'])): ?>
        <div class="alert alert-success">Profile updated successfully.</div>
      <?php endif; ?>
      <div class="row align-items-center">
        <div class="col-md-2 text-center">
          <?php
            $profilePic = (!empty($staff['image']) && file_exists('../images/' . $staff['image'])) ? '../images/' . $staff['image'] : '../images/default.jpg';
          ?>
          <img src="<?= htmlspecialchars($profilePic) ?>" alt="Profile Picture" class="rounded-circle mb-2" style="width:90px;height:90px;object-fit:cover;border:3px solid #ffc107;">
          <form method="POST" enctype="multipart/form-data" class="mt-2">
            <input type="file" name="profile_pic" accept="image/*" class="form-control mb-1">
            <button type="submit" name="upload_pic" class="btn btn-warning btn-sm">Change Picture</button>
          </form>
          <?php if (!empty($staff['image'])): ?>
          <form method="POST" class="mt-1">
            <button type="submit" name="delete_pic" class="btn btn-danger btn-sm">Delete Picture</button>
          </form>
          <?php endif; ?>
        </div>
        <div class="col-md-10">
          <form method="POST" class="row">
            <div class="form-group col-md-4">
              <label>First Name</label>
              <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($staff['first_name']) ?>" required>
            </div>
            <div class="form-group col-md-4">
              <label>Middle Name</label>
              <input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($staff['middle_name']) ?>">
            </div>
            <div class="form-group col-md-4">
              <label>Last Name</label>
              <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($staff['last_name']) ?>" required>
            </div>
            <div class="form-group col-md-6">
              <label>Email</label>
              <input type="email" class="form-control" value="<?= htmlspecialchars($staff['email']) ?>" readonly>
            </div>
            <div class="form-group col-md-6">
              <label>Phone</label>
              <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($staff['phone']) ?>">
            </div>
            <div class="form-group col-12 mt-2">
              <button type="submit" name="update_profile" class="btn btn-success">Update Profile</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <div class="section-card">
      <h4 class="mb-3 text-primary"><i class="fa fa-calendar-check-o"></i> Manage Expected Bookings</h4>
      <p class="mb-3">Only upcoming <strong>pending</strong> bookings are shown. To confirm/cancel, use the action buttons for the corresponding <strong>Ref # (Reservation ID)</strong>.</p>
      <input type="text" id="bookingsSearch" class="form-control mb-2" placeholder="Search bookings...">
      <div class="table-responsive">
        <table id="bookingsTable" class="table table-bordered table-hover">
          <thead class="thead-dark">
            <tr>
              <th class="sortable">Ref #</th>
              <th class="sortable">Client</th>
              <th class="sortable">Slot</th>
              <th class="sortable">Vehicle</th>
              <th class="sortable">Start</th>
              <th class="sortable">End</th>
              <th class="sortable">Duration</th>
              <th class="sortable">Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($bookings) === 0): ?>
              <tr><td colspan="9" class="text-center">No bookings found.</td></tr>
            <?php else: foreach ($bookings as $b): ?>
              <tr>
                <td><?= htmlspecialchars($b['reservation_id']) ?></td>
                <td><?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?></td>
                <td><?= htmlspecialchars($b['slot_number']) ?> (<?= htmlspecialchars($b['slot_type']) ?>)</td>
                <td><?= htmlspecialchars($b['brand'].' '.$b['model'].' - '.$b['plate_number']) ?></td>
                <td><?= htmlspecialchars($b['start_time']) ?></td>
                <td><?= htmlspecialchars($b['end_time']) ?></td>
                <td><?= htmlspecialchars($b['duration']) ?></td>
                <td><?= htmlspecialchars(ucfirst($b['status'])) ?></td>
                <td>
                  <?php if ($b['status'] === 'pending'): ?>
                    <form method="post" style="display:inline-block">
                      <input type="hidden" name="reservation_id" value="<?= $b['reservation_id'] ?>">
                      <button type="submit" name="action" value="confirm" class="btn btn-success btn-sm">Confirm</button>
                    </form>
                    <form method="post" style="display:inline-block">
                      <input type="hidden" name="reservation_id" value="<?= $b['reservation_id'] ?>">
                      <button type="submit" name="action" value="cancel" class="btn btn-danger btn-sm">Cancel</button>
                    </form>
                  <?php else: ?>
                    <span class="text-muted">No actions</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="section-card">
      <h4 class="mb-3 text-success"><i class="fa fa-play-circle"></i> Active Reservations (Confirmed & Ongoing)</h4>
      <input type="text" id="activeSearch" class="form-control mb-2" placeholder="Search active reservations...">
      <div class="table-responsive">
        <table id="activeTable" class="table table-bordered table-hover">
          <thead class="thead-light">
            <tr>
              <th class="sortable">Ref #</th>
              <th class="sortable">Client</th>
              <th class="sortable">Slot</th>
              <th class="sortable">Vehicle</th>
              <th class="sortable">Start</th>
              <th class="sortable">End</th>
              <th class="sortable">Duration</th>
              <th class="sortable">Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($active_reservations) === 0): ?>
              <tr><td colspan="8" class="text-center">No active reservations.</td></tr>
            <?php else: foreach ($active_reservations as $b): ?>
              <tr>
                <td><?= htmlspecialchars($b['reservation_id']) ?></td>
                <td><?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?></td>
                <td><?= htmlspecialchars($b['slot_number']) ?> (<?= htmlspecialchars($b['slot_type']) ?>)</td>
                <td><?= htmlspecialchars($b['brand'].' '.$b['model'].' - '.$b['plate_number']) ?></td>
                <td><?= htmlspecialchars($b['start_time']) ?></td>
                <td><?= htmlspecialchars($b['end_time']) ?></td>
                <td><?= htmlspecialchars($b['duration']) ?></td>
                <td><?= htmlspecialchars(ucfirst($b['status'])) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="section-card">
      <h4 class="mb-3 text-info"><i class="fa fa-history"></i> Reservation History (Completed/Cancelled)</h4>
      <input type="text" id="historySearch" class="form-control mb-2" placeholder="Search reservation history...">
      <div class="table-responsive">
        <table id="historyTable" class="table table-bordered table-hover">
          <thead class="thead-light">
            <tr>
              <th class="sortable">Ref #</th>
              <th class="sortable">Client</th>
              <th class="sortable">Slot</th>
              <th class="sortable">Vehicle</th>
              <th class="sortable">Start</th>
              <th class="sortable">End</th>
              <th class="sortable">Duration</th>
              <th class="sortable">Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($history_reservations) === 0): ?>
              <tr><td colspan="8" class="text-center">No completed or cancelled reservations found.</td></tr>
            <?php else: foreach ($history_reservations as $b): ?>
              <tr>
                <td><?= htmlspecialchars($b['reservation_id']) ?></td>
                <td><?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?></td>
                <td><?= htmlspecialchars($b['slot_number']) ?> (<?= htmlspecialchars($b['slot_type']) ?>)</td>
                <td><?= htmlspecialchars($b['brand'].' '.$b['model'].' - '.$b['plate_number']) ?></td>
                <td><?= htmlspecialchars($b['start_time']) ?></td>
                <td><?= htmlspecialchars($b['end_time']) ?></td>
                <td><?= htmlspecialchars($b['duration']) ?></td>
                <td><?= htmlspecialchars(ucfirst($b['status'])) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="section-card">
      <h4 class="mb-3 text-warning"><i class="fa fa-car"></i> Parking Slots Overview</h4>
      <div class="row">
      <?php if (count($all_slots) === 0): ?>
        <div class="col-12"><div class="alert alert-info text-center">No parking slots found.</div></div>
      <?php else: foreach ($all_slots as $slot): ?>
        <div class="col-md-4 mb-3">
          <div class="card bg-dark text-light <?= getSlotColorClass($slot['slot_status']) ?>" style="border-width:3px;">
            <div class="card-body">
              <h5 class="card-title">Slot <?= htmlspecialchars($slot['slot_number']) ?></h5>
              <p class="card-text">Type: <?= htmlspecialchars($slot['slot_type']) ?></p>
              <p class="card-text">Status: <span class="font-weight-bold text-warning"><?= htmlspecialchars(ucfirst($slot['slot_status'])) ?></span></p>
            </div>
          </div>
        </div>
      <?php endforeach; endif; ?>
      </div>
      <?php if ($slots_total_pages > 1): ?>
      <?php list($slots_start, $slots_end) = getPaginationRange($slots_page, $slots_total_pages); ?>
      <nav aria-label="Parking Slots pagination">
        <ul class="pagination justify-content-center">
          <li class="page-item<?= $slots_page <= 1 ? ' disabled' : '' ?>">
            <a class="page-link" href="?slots_page=<?= $slots_page-1 ?>" tabindex="-1">Previous</a>
          </li>
          <?php if ($slots_start > 1): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
          <?php endif; ?>
          <?php for ($i = $slots_start; $i <= $slots_end; $i++): ?>
            <li class="page-item<?= $i == $slots_page ? ' active' : '' ?>">
              <a class="page-link" href="?slots_page=<?= $i ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
          <?php if ($slots_end < $slots_total_pages): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
          <?php endif; ?>
          <li class="page-item<?= $slots_page >= $slots_total_pages ? ' disabled' : '' ?>">
            <a class="page-link" href="?slots_page=<?= $slots_page+1 ?>">Next</a>
          </li>
        </ul>
      </nav>
      <?php endif; ?>
    </div>
  </div>
</div>
<script src="../js/bootstrap.bundle.min.js"></script>
<script src="../js/jquery.min.js"></script>
<script>
// Table search and sort for all tables
function tableSearch(inputId, tableSelector) {
  $(inputId).on('keyup', function() {
    var value = $(this).val().toLowerCase();
    $(tableSelector + ' tbody tr').filter(function() {
      $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
    });
  });
}
$(function() {
  tableSearch('#activeSearch', '#activeTable');
  tableSearch('#historySearch', '#historyTable');
  tableSearch('#bookingsSearch', '#bookingsTable');
  // Simple column sort
  $('th.sortable').on('click', function() {
    var table = $(this).closest('table');
    var rows = table.find('tbody > tr').toArray();
    var idx = $(this).index();
    var asc = !$(this).hasClass('asc');
    rows.sort(function(a, b) {
      var A = $(a).children().eq(idx).text().toUpperCase();
      var B = $(b).children().eq(idx).text().toUpperCase();
      if($.isNumeric(A) && $.isNumeric(B)) {
        return asc ? A - B : B - A;
      }
      return asc ? A.localeCompare(B) : B.localeCompare(A);
    });
    table.find('tbody').empty().append(rows);
    table.find('th').removeClass('asc desc');
    $(this).addClass(asc ? 'asc' : 'desc');
  });
});
</script>
<style>
th.sortable { cursor:pointer; }
th.asc:after { content:' \25B2'; }
th.desc:after { content:' \25BC'; }
</style>
<script src="../js/ef9baa832e.js" crossorigin="anonymous"></script>
</body>
</html>
