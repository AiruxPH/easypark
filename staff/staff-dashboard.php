<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    header("Location: ../index.php");
    exit();
}
require_once '../db.php';

// Handle confirm/cancel actions
if (isset($_POST['action']) && isset($_POST['reservation_id'])) {
    $reservation_id = intval($_POST['reservation_id']);
    if ($_POST['action'] === 'confirm') {
        $stmt = $pdo->prepare("UPDATE reservations SET status = 'confirmed' WHERE reservation_id = ?");
        $stmt->execute([$reservation_id]);
    } elseif ($_POST['action'] === 'cancel') {
        $stmt = $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE reservation_id = ?");
        $stmt->execute([$reservation_id]);
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Staff Dashboard - EasyPark</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../css/bootstrap.min.css" >
</head>
<body class="bg-light">
<div class="container py-5">
<h2 class="mb-4">Staff Dashboard - Manage Expected Bookings</h2>
<p class="mb-3">Only upcoming <strong>pending</strong> bookings are shown. To confirm/cancel, use the action buttons for the corresponding <strong>Ref # (Reservation ID)</strong>.</p>
<table class="table table-bordered table-hover bg-white">
  <thead class="thead-dark">
    <tr>
      <th>Ref #</th>
      <th>Client</th>
      <th>Slot</th>
      <th>Vehicle</th>
      <th>Start</th>
      <th>End</th>
      <th>Duration</th>
      <th>Status</th>
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
<a href="../logout.php" class="btn btn-secondary mt-4">Logout</a>
</div>
<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
