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
.staff-navbar {
  background: #232526;
  border-radius: 1rem;
  margin-bottom: 2rem;
  box-shadow: 0 2px 12px rgba(0,0,0,0.12);
}
.staff-navbar .nav-link {
  color: #ffc107;
  font-weight: 600;
  font-size: 1.1rem;
  letter-spacing: 0.5px;
}
.staff-navbar .nav-link.active, .staff-navbar .nav-link:focus {
  background: #ffc107;
  color: #232526 !important;
  border-radius: 0.5rem;
}
@media (max-width: 767px) {
  .header-bar { flex-direction: column; align-items: flex-start; padding: 1rem; }
  .section-card { padding: 1rem; }
  .staff-navbar .nav-link { font-size: 1rem; }
}
</style>
</head>
<body>
<div class="bg-overlay">
  <div class="header-bar mb-4">
    <h2><i class="fa fa-user-shield"></i> Staff Dashboard</h2>
    <div class="d-flex align-items-center">
      <a href="profile.php" class="d-flex align-items-center nav-link p-0 mr-3" style="color: #ffc107; text-decoration: none;">
        <img src="<?php echo (!empty($staff['image']) && file_exists('../images/' . $staff['image'])) ? '../images/' . $staff['image'] : '../images/default.jpg'; ?>" alt="Profile Picture" class="rounded-circle mr-2" style="width:40px;height:40px;object-fit:cover;border:2px solid #ffc107;">
        <span class="font-weight-bold" style="font-size:1.1rem;">
          My Profile
        </span>
      </a>
      <a href="../logout.php" class="btn btn-secondary ml-2"><i class="fa fa-sign-out"></i> Logout</a>
    </div>
  </div>
  <div class="container">
    <!-- Responsive Navbar for Sections -->
    <nav class="staff-navbar navbar navbar-expand-md navbar-dark mb-4">
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#staffNav" aria-controls="staffNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="staffNav">
        <ul class="navbar-nav w-100 justify-content-between">
          <li class="nav-item"><a class="nav-link" href="javascript:void(0)" data-section="bookings"><i class="fa fa-calendar-check-o"></i> Bookings</a></li>
          <li class="nav-item"><a class="nav-link" href="javascript:void(0)" data-section="active"><i class="fa fa-play-circle"></i> Active</a></li>
          <li class="nav-item"><a class="nav-link" href="javascript:void(0)" data-section="history"><i class="fa fa-history"></i> History</a></li>
          <li class="nav-item"><a class="nav-link" href="javascript:void(0)" data-section="slots"><i class="fa fa-car"></i> Slots</a></li>
        </ul>
      </div>
    </nav>
    <!-- Section Content Loader -->
    <div id="section-content"></div>
  </div>
</div>
<script src="../js/bootstrap.bundle.min.js"></script>
<script src="../js/jquery.min.js"></script>
<script>
// SPA-like section navigation
const sectionFiles = {
  profile: 'section-profile.php',
  bookings: 'section-bookings.php',
  active: 'section-active.php',
  history: 'section-history.php',
  slots: 'section-slots.php'
};
$(function() {
  function loadSection(section) {
    $('.staff-navbar .nav-link').removeClass('active');
    $('.staff-navbar .nav-link[data-section="' + section + '"]').addClass('active');
    $('#section-content').fadeOut(100, function() {
      $('#section-content').load(sectionFiles[section], function(response, status, xhr) {
        if (status === "error") {
          $('#section-content').html(
            "<div class='alert alert-danger'>Failed to load section: " + xhr.status + " " + xhr.statusText + "</div>"
          ).fadeIn(100);
        } else {
          $('#section-content').fadeIn(100);
        }
      });
    });
  }
  $('.staff-navbar .nav-link').on('click', function(e) {
    e.preventDefault();
    var section = $(this).data('section');
    if (!section) return;
    loadSection(section);
  });
  // Load bookings section by default on page load
  loadSection('bookings');
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
