<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
//check if the user is a non-client
if ($_SESSION['user_type'] != 'client') {
    //if admin, redirect to admin dashboard and if a staff, redirect to staff dashboard
    if ($_SESSION['user_type'] == 'admin') {
        header('Location: admin_dashboard.php');
    } elseif ($_SESSION['user_type'] == 'staff') {
        header('Location: staff_dashboard.php');
    }
}
$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch user's vehicles
$stmt = $pdo->prepare('SELECT * FROM vehicles WHERE user_id = ?');
$stmt->execute([$user_id]);
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch available parking slots
$slot_stmt = $pdo->query("SELECT * FROM parking_slots WHERE slot_status = 'available'");
$available_slots = $slot_stmt->fetchAll(PDO::FETCH_ASSOC);

$message = '';
// Handle profile update, vehicle add/edit/delete, reservation here (to be implemented)

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - EASYPARK</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/font-awesome.min.css"/>
    <style>
        body { background: #f8f9fa; }
        .profile-section { background: #fff; border-radius: 8px; box-shadow: 0 2px 8px #0001; padding: 2rem; margin-bottom: 2rem; }
        .vehicle-table th, .vehicle-table td { vertical-align: middle; }
    </style>
</head>
<body>
<div class="container py-4">
    <h2 class="mb-4 text-warning">My Profile</h2>
    <div class="profile-section mb-4">
        <h4>Profile Information</h4>
        <form method="POST" action="profile.php">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>First Name</label>
                    <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                </div>
                <div class="form-group col-md-4">
                    <label>Middle Name</label>
                    <input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($user['middle_name']) ?>">
                </div>
                <div class="form-group col-md-4">
                    <label>Last Name</label>
                    <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required readonly>
                </div>
                <div class="form-group col-md-6">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>">
                </div>
            </div>
            <button type="submit" name="update_profile" class="btn btn-warning text-white">Update Profile</button>
        </form>
    </div>

    <div class="profile-section mb-4">
        <h4>My Vehicles</h4>
        <table class="table table-bordered vehicle-table">
            <thead class="thead-light">
                <tr>
                    <th>Plate Number</th>
                    <th>Type</th>
                    <th>Brand</th>
                    <th>Color</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($vehicles as $vehicle): ?>
                <tr>
                    <td><?= htmlspecialchars($vehicle['plate_number']) ?></td>
                    <td><?= htmlspecialchars($vehicle['vehicle_type']) ?></td>
                    <td><?= htmlspecialchars($vehicle['brand']) ?></td>
                    <td><?= htmlspecialchars($vehicle['color']) ?></td>
                    <td>
                        <a href="profile.php?edit_vehicle=<?= $vehicle['vehicle_id'] ?>" class="btn btn-sm btn-info">Edit</a>
                        <a href="profile.php?delete_vehicle=<?= $vehicle['vehicle_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this vehicle?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <a href="profile.php?add_vehicle=1" class="btn btn-success">Add Vehicle</a>
    </div>

    <div class="profile-section mb-4">
        <h4>Reserve a Parking Slot</h4>
        <form method="POST" action="profile.php">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Select Vehicle</label>
                    <select name="vehicle_id" class="form-control" required>
                        <option value="">-- Select Vehicle --</option>
                        <?php foreach ($vehicles as $vehicle): ?>
                            <option value="<?= $vehicle['vehicle_id'] ?>"><?= htmlspecialchars($vehicle['plate_number']) ?> (<?= htmlspecialchars($vehicle['vehicle_type']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label>Select Parking Slot</label>
                    <select name="slot_id" class="form-control" required>
                        <option value="">-- Select Slot --</option>
                        <?php foreach ($available_slots as $slot): ?>
                            <option value="<?= $slot['parking_slot_id'] ?>"><?= htmlspecialchars($slot['slot_number']) ?> (<?= htmlspecialchars($slot['slot_type']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button type="submit" name="reserve_slot" class="btn btn-primary">Reserve Slot</button>
        </form>
    </div>
</div>
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
