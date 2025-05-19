<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
// Only allow clients
if ($_SESSION['user_type'] != 'client') {
    if ($_SESSION['user_type'] == 'admin') {
        header('Location: admin_dashboard.php');
        exit();
    } elseif ($_SESSION['user_type'] == 'staff') {
        header('Location: staff_dashboard.php');
        exit();
    }
}
$user_id = $_SESSION['user_id'];


// Fetch user's vehicles with brand, model, and type from Vehicle_Models
$stmt = $pdo->prepare('SELECT v.*, vm.brand, vm.model, vm.type FROM vehicles v LEFT JOIN Vehicle_Models vm ON v.model_id = vm.model_id WHERE v.user_id = ?');
$stmt->execute([$user_id]);
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// For each vehicle, check if it has an active reservation and fetch details if so
$vehicle_active_reservations = [];
foreach ($vehicles as $vehicle) {
    $stmt = $pdo->prepare('SELECT * FROM reservations WHERE vehicle_id = ? AND status IN ("pending", "confirmed", "ongoing") AND end_time > NOW() ORDER BY start_time DESC LIMIT 1');
    $stmt->execute([$vehicle['vehicle_id']]);
    $active_res = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($active_res) {
        $vehicle_active_reservations[$vehicle['vehicle_id']] = $active_res;
    }
}

$message = '';
// Handle vehicle deletion
if (isset($_GET['delete_vehicle'])) {
    $vehicle_id = intval($_GET['delete_vehicle']);
    // Check for active reservation for this vehicle
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE vehicle_id = ? AND status IN ("pending", "confirmed", "ongoing") AND end_time > NOW()');
    $stmt->execute([$vehicle_id]);
    $active = $stmt->fetchColumn();
    if ($active > 0) {
        $message = '❌ Cannot delete vehicle with an active reservation.';
    } else {
        $stmt = $pdo->prepare('DELETE FROM vehicles WHERE vehicle_id = ? AND user_id = ?');
        $stmt->execute([$vehicle_id, $user_id]);
        header('Location: vehicles.php');
        exit();
    }
}
// Handle add vehicle
if (isset($_POST['add_vehicle'])) {
    $plate = trim($_POST['plate_number']);
    $color = trim($_POST['color']);
    $model_id = intval($_POST['model_id']);
    if ($plate && $color && $model_id) {
        $stmt = $pdo->prepare('INSERT INTO vehicles (user_id, model_id, plate_number, color) VALUES (?, ?, ?, ?)');
        $stmt->execute([$user_id, $model_id, $plate, $color]);
        header('Location: vehicles.php');
        exit();
    } else {
        $message = '❌ Please fill all vehicle fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Vehicles - EASYPARK</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/font-awesome.min.css"/>
    <style>
        body.bg-car {
            background-image: url('bg-car.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        .profile-section { background: rgba(255,255,255,0.95); border-radius: 8px; box-shadow: 0 2px 8px #0001; padding: 2rem; margin-bottom: 2rem; }
        .vehicle-table th, .vehicle-table td { vertical-align: middle; }
        .navbar-dark .navbar-brand, .navbar-dark .navbar-nav .nav-link {
            color: #fff;
        }
        .navbar-dark .navbar-brand:hover, .navbar-dark .navbar-nav .nav-link:hover {
            color: #ccc;
        }
    </style>
</head>
<body class="bg-car">
<nav id="navbar" class="navbar navbar-expand-lg bg-image-dark navbar-dark sticky-top w-100 px-3">
  <a id="opp" class="navbar-brand" href="index.php">
    <h1 class="custom-size 75rem">EASYPARK</h1>
  </a>
  <button class="navbar-toggler ml-auto" type="button" data-toggle="collapse" data-target="#collapsibleNavbar">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse justify-content-end" id="collapsibleNavbar">
    <ul id="opp" class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" href="dashboard.php">Dashboard</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="profile.php">Profile</a>
      </li>
      <li class="nav-item">
        <a class="nav-link active" href="vehicles.php">My Vehicles</a>
      </li>
    </ul>
  </div>
</nav>
<div class="container py-4">
    <h2 class="mb-4 text-warning custom-size display-4 text-center">My Vehicles</h2>
    <?php if (!empty($message)) {
        echo '<div class="alert alert-danger text-center" id="vehicleMsg">' . htmlspecialchars($message) . '</div>';
    } ?>
    <div class="profile-section mb-4">
        <table class="table table-bordered vehicle-table bg-white text-dark">
            <thead class="thead-light">
                <tr>
                    <th>Plate Number</th>
                    <th>Type</th>
                    <th>Brand</th>
                    <th>Model</th>
                    <th>Color</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($vehicles as $vehicle): ?>
                <tr>
                    <td><?= htmlspecialchars($vehicle['plate_number']) ?></td>
                    <td><?= htmlspecialchars($vehicle['type'] ?? $vehicle['vehicle_type']) ?></td>
                    <td><?= htmlspecialchars($vehicle['brand'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($vehicle['model'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($vehicle['color']) ?></td>
                    <td>
                        <?php if (isset($vehicle_active_reservations[$vehicle['vehicle_id']])): 
                            $res = $vehicle_active_reservations[$vehicle['vehicle_id']];
                            $status = ucfirst($res['status']);
                        ?>
                            <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#reservationModal<?= $vehicle['vehicle_id'] ?>">
                                <?= htmlspecialchars($status) ?>
                            </button>
                        <?php else: ?>
                            <span class="badge badge-success">Available</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!isset($vehicle_active_reservations[$vehicle['vehicle_id']])): ?>
                        <a href="vehicles.php?delete_vehicle=<?= $vehicle['vehicle_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this vehicle?')">Delete</a>
                        <?php else: ?>
                        <button class="btn btn-sm btn-secondary" disabled>Delete</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if (isset($vehicle_active_reservations[$vehicle['vehicle_id']])): 
                    $res = $vehicle_active_reservations[$vehicle['vehicle_id']]; ?>
                <!-- Reservation Modal -->
                <div class="modal fade" id="reservationModal<?= $vehicle['vehicle_id'] ?>" tabindex="-1" role="dialog" aria-labelledby="reservationModalLabel<?= $vehicle['vehicle_id'] ?>" aria-hidden="true">
                  <div class="modal-dialog" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="reservationModalLabel<?= $vehicle['vehicle_id'] ?>">Active Reservation Details</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>
                      <div class="modal-body">
                        <p><b>Reference #:</b> <?= htmlspecialchars($res['reservation_id']) ?></p>
                        <p><b>Slot:</b> <?= htmlspecialchars($res['parking_slot_id']) ?></p>
                        <p><b>Start:</b> <?= htmlspecialchars($res['start_time']) ?></p>
                        <p><b>End:</b> <?= htmlspecialchars($res['end_time']) ?></p>
                        <p><b>Status:</b> <?= htmlspecialchars($res['status']) ?></p>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                      </div>
                    </div>
                  </div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
        <button class="btn btn-success" data-toggle="modal" data-target="#addVehicleModal">Add Vehicle</button>
    </div>
    <!-- Add Vehicle Modal -->
    <div class="modal fade" id="addVehicleModal" tabindex="-1" role="dialog" aria-labelledby="addVehicleModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST" action="vehicles.php">
            <div class="modal-header">
              <h5 class="modal-title" id="addVehicleModalLabel">Add Vehicle</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label>Plate Number</label>
                <input type="text" name="plate_number" class="form-control" required>
              </div>
              <div class="form-group">
                <label>Color</label>
                <input type="text" name="color" class="form-control" required>
              </div>
              <div class="form-group">
                <label>Type</label>
                <select id="vehicleTypeInput" class="form-control" required>
                  <option value="">Select Type</option>
                  <?php
                  $types = $pdo->query('SELECT DISTINCT type FROM Vehicle_Models')->fetchAll(PDO::FETCH_COLUMN);
                  foreach ($types as $type) {
                    echo '<option value="' . htmlspecialchars($type) . '">' . htmlspecialchars($type) . '</option>';
                  }
                  ?>
                </select>
              </div>
              <div class="form-group">
                <label>Brand</label>
                <select id="brandInput" class="form-control" required disabled>
                  <option value="">Select Brand</option>
                </select>
              </div>
              <div class="form-group">
                <label>Model</label>
                <select name="model_id" id="modelInput" class="form-control" required disabled>
                  <option value="">Select Model</option>
                </select>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
              <button type="submit" name="add_vehicle" class="btn btn-success">Add Vehicle</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <div class="text-center mt-4">
      <a href="profile.php" class="btn btn-primary">Back to Profile</a>
      <a href="logout.php" class="btn btn-danger ml-2">Logout</a>
    </div>
</div>
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
<script>
  // Nested dropdown for type > brand > model
  $(function() {
    var allBrands = {};
    var allModels = {};
    <?php
      $models = $pdo->query('SELECT model_id, brand, model, type FROM Vehicle_Models')->fetchAll(PDO::FETCH_ASSOC);
      $brandTypeMap = [];
      $modelTypeBrandMap = [];
      foreach ($models as $m) {
        $brandTypeMap[$m['type']][] = $m['brand'];
        $modelTypeBrandMap[$m['type']][$m['brand']][] = ['model' => $m['model'], 'model_id' => $m['model_id']];
      }
      echo 'allBrands = ' . json_encode($brandTypeMap) . ";\n";
      echo 'allModels = ' . json_encode($modelTypeBrandMap) . ";\n";
    ?>
    $('#vehicleTypeInput').on('change', function() {
      var type = $(this).val();
      var brandSel = $('#brandInput');
      var modelSel = $('#modelInput');
      brandSel.empty().append('<option value="">Select Brand</option>');
      modelSel.empty().append('<option value="">Select Model</option>').prop('disabled', true);
      if (type && allBrands[type]) {
        var uniqueBrands = [...new Set(allBrands[type])];
        uniqueBrands.forEach(function(brand) {
          brandSel.append('<option value="'+brand+'">'+brand+'</option>');
        });
        brandSel.prop('disabled', false);
      } else {
        brandSel.prop('disabled', true);
      }
    });
    $('#brandInput').on('change', function() {
      var type = $('#vehicleTypeInput').val();
      var brand = $(this).val();
      var modelSel = $('#modelInput');
      modelSel.empty().append('<option value="">Select Model</option>');
      if (type && brand && allModels[type] && allModels[type][brand]) {
        allModels[type][brand].forEach(function(obj) {
          modelSel.append('<option value="'+obj.model_id+'">'+obj.model+'</option>');
        });
        modelSel.prop('disabled', false);
      } else {
        modelSel.prop('disabled', true);
      }
    });
    // On modal open, reset dropdowns
    $('#addVehicleModal').on('show.bs.modal', function() {
      $('#vehicleTypeInput').val('');
      $('#brandInput').empty().append('<option value="">Select Brand</option>').prop('disabled', true);
      $('#modelInput').empty().append('<option value="">Select Model</option>').prop('disabled', true);
    });
  });
  // Hide vehicle error message after 3 seconds
  setTimeout(function() {
    var msg = document.getElementById('vehicleMsg');
    if (msg) msg.style.display = 'none';
  }, 3000);
</script>
</body>
</html>
