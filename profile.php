<?php
session_start();
require_once 'includes/db.php';
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

// --- DATA FETCHING ---
// Fetch user info
$stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = ?');
$stmt->execute([$user_id]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch user's vehicles with brand, model, and type from Vehicle_Models
$stmt = $pdo->prepare('SELECT v.*, vm.brand, vm.model, vm.type FROM vehicles v LEFT JOIN Vehicle_Models vm ON v.model_id = vm.model_id WHERE v.user_id = ?');
$stmt->execute([$user_id]);
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Active reservations
$vehicle_active_reservations = [];
foreach ($vehicles as $vehicle) {
  $stmt = $pdo->prepare('SELECT * FROM reservations WHERE vehicle_id = ? AND status NOT IN ("cancelled", "completed") AND status IN ("confirmed", "ongoing") AND end_time > NOW() ORDER BY start_time DESC LIMIT 1');
  $stmt->execute([$vehicle['vehicle_id']]);
  $active_res = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($active_res) {
    $vehicle_active_reservations[$vehicle['vehicle_id']] = $active_res;
  }
}

// Fetch available parking slots
$slot_stmt = $pdo->query("SELECT * FROM parking_slots WHERE slot_status = 'available'");
$available_slots = $slot_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent activity logs (Limit 5)
$log_stmt = $pdo->prepare('SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$log_stmt->execute([$user_id]);
$recent_logs = $log_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile - EasyPark</title>
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="icon" href="images/favicon.png" type="image/png" />
  <script src="js/ef9baa832e.js"></script>
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary-color: #f0a500;
      --secondary-color: #1a1a1a;
      --text-color: #e0e0e0;
      --bg-overlay: rgba(30, 30, 30, 0.85);
      --card-bg: rgba(45, 45, 45, 0.9);
    }

    body {
      font-family: 'Outfit', sans-serif;
      background: url('images/bg-car.jpg') no-repeat center center fixed;
      background-size: cover;
      color: var(--text-color);
    }

    /* Fixed Glass Fixed Overlay */
    .glass-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.7);
      z-index: -1;
    }

    /* Scrollbar */
    ::-webkit-scrollbar {
      width: 10px;
    }

    ::-webkit-scrollbar-track {
      background: #333;
    }

    ::-webkit-scrollbar-thumb {
      background: var(--primary-color);
      border-radius: 5px;
    }

    /* Navbar adjustment */
    .navbar {
      background: rgba(0, 0, 0, 0.9) !important;
      backdrop-filter: blur(10px);
    }

    /* Cards */
    .custom-card {
      background: var(--card-bg);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 15px;
      backdrop-filter: blur(10px);
      box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
      margin-bottom: 2rem;
      transition: transform 0.3s ease;
    }

    .custom-card:hover {
      transform: translateY(-5px);
    }

    .card-header {
      background: transparent;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      padding: 1.5rem;
    }

    .card-title {
      margin: 0;
      font-weight: 600;
      color: var(--primary-color);
    }

    .card-body {
      padding: 1.5rem;
    }

    /* Profile Picture */
    .profile-pic-container {
      position: relative;
      width: 150px;
      height: 150px;
      margin: 0 auto;
    }

    .profile-pic {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 50%;
      border: 4px solid var(--primary-color);
      box-shadow: 0 0 20px rgba(240, 165, 0, 0.3);
      transition: all 0.3s ease;
    }

    .profile-pic-edit {
      position: absolute;
      bottom: 0;
      right: 0;
      background: var(--primary-color);
      color: #000;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .profile-pic-edit:hover {
      transform: scale(1.1);
      background: #fff;
    }

    /* Form Controls */
    .form-control {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: #fff;
      border-radius: 8px;
    }

    .form-control option {
      background: #333;
      color: #fff;
    }

    .form-control:focus {
      background: rgba(255, 255, 255, 0.1);
      border-color: var(--primary-color);
      color: #fff;
      box-shadow: none;
    }

    .form-control:disabled,
    .form-control[readonly] {
      background: rgba(0, 0, 0, 0.3);
    }

    label {
      font-weight: 500;
      color: #aaa;
      margin-bottom: 0.5rem;
    }

    /* Buttons */
    .btn-custom {
      background: linear-gradient(45deg, var(--primary-color), #ffc107);
      border: none;
      color: #000;
      font-weight: 600;
      padding: 0.8rem 2rem;
      border-radius: 30px;
      transition: all 0.3s ease;
    }

    .btn-custom:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(240, 165, 0, 0.4);
      color: #000;
    }

    .btn-danger-custom {
      background: rgba(220, 53, 69, 0.2);
      border: 1px solid #dc3545;
      color: #dc3545;
      border-radius: 30px;
    }

    .btn-danger-custom:hover {
      background: #dc3545;
      color: #fff;
    }

    /* Table */
    .table-custom {
      color: #e0e0e0;
    }

    .table-custom th {
      border-top: none;
      border-bottom: 2px solid rgba(255, 255, 255, 0.1);
      color: var(--primary-color);
    }

    .table-custom td {
      border-top: 1px solid rgba(255, 255, 255, 0.05);
      vertical-align: middle;
    }

    /* Alerts */
    .alert-custom {
      border-radius: 10px;
      border: none;
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(5px);
    }
  </style>
</head>

<body>
  <!-- Fixed Background Overlay -->
  <div class="glass-overlay"></div>

  <!-- Navbar -->
  <?php include 'includes/client_navbar.php'; ?>

  <!-- Content -->
  <div class="container pb-5 pt-5">
    <!-- Title Section -->
    <div class="text-center mb-5">
      <h1 class="display-4 font-weight-bold text-white mb-3">My Profile</h1>
      <p class="lead text-white-50">Manage your personal information and vehicles</p>
    </div>

    <!-- Messages -->
    <?php if (!empty($message)): ?>
      <div class="alert alert-info alert-custom text-center mb-4">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="close text-white" data-dismiss="alert">&times;</button>
      </div>
    <?php endif; ?>

    <div class="row">
      <!-- Left Column: Profile Card -->
      <div class="col-lg-4">
        <div class="custom-card text-center pb-3">
          <div class="card-body">
            <?php
            $profilePic = (!empty($userData['image']) && file_exists('images/' . $userData['image'])) ? 'images/' . $userData['image'] : 'images/default.jpg';
            ?>
            <div class="profile-pic-container mb-3">
              <img src="<?= htmlspecialchars($profilePic) ?>" alt="Profile" class="profile-pic" id="avatarPreview">
              <label for="profilePicInput" class="profile-pic-edit" title="Change Picture">
                <i class="fa fa-camera"></i>
              </label>
            </div>

            <h3 class="text-white mb-1"><?= htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']) ?>
            </h3>
            <p class="text-white-50 mb-3"><?= htmlspecialchars($userData['email']) ?></p>

            <div class="d-flex justify-content-center">
              <div class="mr-3">
                <span class="d-block font-weight-bold h4 text-warning mb-0"><?= count($vehicles) ?></span>
                <small class="text-muted">Vehicles</small>
              </div>
              <!-- You could add more stats here, e.g. Bookings -->
            </div>

            <div class="mt-4">
              <a href="activity_logs.php" class="btn btn-outline-light btn-sm px-4 rounded-pill">
                <i class="fa fa-history mr-1"></i> View Activity Logs
              </a>
            </div>

            <!-- Hidden Form for Image Upload -->
            <form method="POST" enctype="multipart/form-data" id="avatarForm">
              <input type="file" id="profilePicInput" name="profile_pic" accept="image/*" class="d-none">
              <input type="hidden" name="upload_pic" value="1">
            </form>

            <?php if (!empty($userData['image']) && $userData['image'] !== 'default.jpg'): ?>
              <form method="POST" class="mt-3">
                <input type="hidden" name="delete_pic" value="1">
                <button type="submit" class="btn btn-sm btn-outline-danger"
                  onclick="return confirm('Delete profile picture?')">
                  <i class="fa fa-trash"></i> Remove Picture
                </button>
              </form>
            <?php endif; ?>
          </div>
        </div>

        <!-- Recent Activity Widget -->
        <div class="custom-card">
          <div class="card-header">
            <h5 class="card-title"><i class="fa fa-history mr-2"></i> Recent Activity</h5>
          </div>
          <div class="card-body p-0">
            <ul class="list-group list-group-flush bg-transparent">
              <?php if (count($recent_logs) > 0): ?>
                <?php foreach ($recent_logs as $log): ?>
                  <li class="list-group-item bg-transparent border-secondary text-white-50 small">
                    <div class="d-flex w-100 justify-content-between">
                      <strong
                        class="text-white"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $log['action']))) ?></strong>
                      <small class="text-muted"><?= date('M j, H:i', strtotime($log['created_at'])) ?></small>
                    </div>
                    <p class="mb-0 text-truncate"><?= htmlspecialchars($log['details']) ?></p>
                  </li>
                <?php endforeach; ?>
              <?php else: ?>
                <li class="list-group-item bg-transparent text-center text-muted py-3">No recent activity.</li>
              <?php endif; ?>
            </ul>
            <div class="text-center p-3">
              <a href="activity_logs.php" class="small text-warning">View All Logs</a>
            </div>
          </div>
        </div>

        <!-- Security Card -->
        <div class="custom-card">
          <div class="card-header">
            <h5 class="card-title"><i class="fa fa-shield mr-2"></i> Security</h5>
          </div>
          <div class="card-body">
            <form method="POST">
              <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" class="form-control" required>
              </div>
              <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" class="form-control" required>
              </div>
              <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_new_password" class="form-control" required>
              </div>
              <button type="submit" name="change_password" class="btn btn-custom btn-block btn-sm">
                Update Password
              </button>
            </form>
            <div class="text-center mt-3">
              <a href="#" class="text-warning small" data-toggle="modal" data-target="#forgotPasswordModal">Forgot
                Password?</a>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Details & Vehicles -->
      <div class="col-lg-8">
        <!-- Personal Info -->
        <div class="custom-card">
          <div class="card-header">
            <h5 class="card-title"><i class="fa fa-user mr-2"></i> Personal Details</h5>
          </div>
          <div class="card-body">
            <form method="POST">
              <div class="form-row">
                <div class="form-group col-md-4">
                  <label>First Name</label>
                  <input type="text" name="first_name" class="form-control"
                    value="<?= htmlspecialchars($userData['first_name']) ?>" required>
                </div>
                <div class="form-group col-md-4">
                  <label>Middle Name</label>
                  <input type="text" name="middle_name" class="form-control"
                    value="<?= htmlspecialchars($userData['middle_name']) ?>">
                </div>
                <div class="form-group col-md-4">
                  <label>Last Name</label>
                  <input type="text" name="last_name" class="form-control"
                    value="<?= htmlspecialchars($userData['last_name']) ?>" required>
                </div>
              </div>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label>Email Address</label>
                  <input type="email" class="form-control" value="<?= htmlspecialchars($userData['email']) ?>" readonly
                    title="Email cannot be changed">
                </div>
                <div class="form-group col-md-6">
                  <label>Phone Number</label>
                  <input type="text" name="phone" class="form-control"
                    value="<?= htmlspecialchars($userData['phone']) ?>">
                </div>
              </div>
              <div class="text-right mt-3">
                <button type="submit" name="update_profile" class="btn btn-custom">Save Changes</button>
              </div>
            </form>
          </div>
        </div>

        <!-- My Vehicles -->
        <div class="custom-card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title"><i class="fa fa-car mr-2"></i> My Vehicles</h5>
            <button class="btn btn-sm btn-outline-warning" data-toggle="modal" data-target="#addVehicleModal">
              <i class="fa fa-plus"></i> Add Vehicle
            </button>
          </div>
          <div class="card-body">
            <?php if (count($vehicles) > 0): ?>
              <div class="table-responsive">
                <table class="table table-custom mb-0">
                  <thead>
                    <tr>
                      <th>Plate #</th>
                      <th>Type</th>
                      <th>Model</th>
                      <th>Color</th>
                      <th class="text-right">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($vehicles as $v): ?>
                      <tr>
                        <td class="font-weight-bold"><?= htmlspecialchars($v['plate_number']) ?></td>
                        <td><?= htmlspecialchars(ucfirst($v['type'] ?? 'Standard')) ?></td>
                        <td><?= htmlspecialchars($v['brand'] . ' ' . $v['model']) ?></td>
                        <td>
                          <span class="badge badge-secondary"
                            style="background-color: <?= htmlspecialchars($v['color']) ?>; color: #fff; text-shadow: 0 0 2px #000;">
                            <?= htmlspecialchars($v['color']) ?>
                          </span>
                        </td>
                        <td class="text-right">
                          <button class="btn btn-sm btn-outline-info mr-1 btn-edit-vehicle"
                            data-id="<?= $v['vehicle_id'] ?>" data-plate="<?= htmlspecialchars($v['plate_number']) ?>"
                            data-color="<?= htmlspecialchars($v['color']) ?>"
                            data-type="<?= htmlspecialchars($v['type']) ?>"
                            data-brand="<?= htmlspecialchars($v['brand']) ?>"
                            data-model-id="<?= htmlspecialchars($v['model_id']) ?>" data-toggle="modal"
                            data-target="#editVehicleModal">
                            <i class="fa fa-edit"></i>
                          </button>
                          <a href="profile.php?delete_vehicle=<?= $v['vehicle_id'] ?>" class="btn btn-sm btn-danger-custom"
                            onclick="return confirm('Are you sure you want to delete this vehicle?')">
                            <i class="fa fa-trash"></i>
                          </a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <div class="text-center py-4">
                <i class="fa fa-car fa-3x text-muted mb-3"></i>
                <p class="text-muted">No vehicles added yet.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Add Vehicle Modal -->
  <div class="modal fade" id="addVehicleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-dark text-white border-secondary">
        <div class="modal-header border-secondary">
          <h5 class="modal-title text-warning">Add New Vehicle</h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <form method="POST">
          <div class="modal-body">
            <div class="form-group">
              <label>Plate Number</label>
              <input type="text" name="plate_number" class="form-control" required>
            </div>
            <div class="form-group">
              <label>Color</label>
              <input type="text" name="color" class="form-control" required placeholder="e.g. Red, Black, #123456">
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
            <input type="hidden" name="add_vehicle" value="1">
          </div>
          <div class="modal-footer border-secondary">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-warning">Add Vehicle</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Forgot Password Modal (Preserved Functionality) -->
  <div class="modal fade" id="forgotPasswordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-dark text-white border-secondary">
        <form method="POST" id="forgotPasswordForm">
          <div class="modal-header border-secondary">
            <h5 class="modal-title text-warning">Reset Password</h5>
            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <div id="forgot-step-1">
              <p class="text-white-50">Enter your security word to verify your identity.</p>
              <div class="form-group">
                <label>Security Word</label>
                <input type="text" name="fp_security_word" class="form-control" required>
              </div>
            </div>
            <div id="forgot-step-2" style="display:none;">
              <div class="form-group">
                <label>New Password</label>
                <input type="password" name="fp_new_password" class="form-control" required>
              </div>
              <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="fp_confirm_new_password" class="form-control" required>
              </div>
            </div>
            <input type="hidden" name="forgot_password_action" value="1">
          </div>
          <div class="modal-footer border-secondary">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-warning" id="forgotNextBtn">Next</button>
            <button type="submit" class="btn btn-success" id="forgotResetBtn" style="display:none;">Reset
              Password</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="js/jquery.min.js"></script>
  <script src="js/bootstrap.bundle.min.js"></script>
  <script>
    // --- AJAX Logic ---
    $(document).ready(function () {
      // Global Toast Function
      function showToast(message, isError = false) {
        // Remove existing toast if any
        $('#dynamicToast').remove();

        const bgClass = isError ? 'bg-danger text-white' : 'bg-success text-white';
        const icon = isError ? 'fa-exclamation-circle' : 'fa-check-circle';

        const toastHtml = `
                <div id="dynamicToast" class="toast hide ${bgClass}" role="alert" aria-live="assertive" aria-atomic="true" data-delay="4000" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                    <div class="toast-header ${bgClass} border-0">
                        <strong class="mr-auto"><i class="fa ${icon} mr-2"></i> Notification</strong>
                        <button type="button" class="ml-2 mb-1 close text-white" data-dismiss="toast">&times;</button>
                    </div>
                    <div class="toast-body font-weight-bold">
                        ${message}
                    </div>
                </div>
            `;
        $('body').append(toastHtml);
        $('#dynamicToast').toast('show');
      }

      // 1. Update Profile
      $('button[name="update_profile"]').parent().parent().on('submit', function (e) {
        e.preventDefault();
        const form = $(this);
        const btn = form.find('button[type="submit"]');

        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

        // Collect data
        const formData = new FormData(this);
        formData.append('action', 'update_profile');

        fetch('action_client_profile.php', { method: 'POST', body: formData })
          .then(res => res.json())
          .then(data => {
            btn.prop('disabled', false).text('Save Changes');
            showToast(data.message, !data.success);
          })
          .catch(err => {
            btn.prop('disabled', false).text('Save Changes');
            showToast('Server error occurred.', true);
          });
      });

      // 2. Change Password
      $('form button[name="change_password"]').parent().closest('form').on('submit', function (e) {
        e.preventDefault();
        const form = $(this);
        const btn = form.find('button[type="submit"]');

        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Updating...');

        const formData = new FormData(this);
        formData.append('action', 'change_password');

        fetch('action_client_profile.php', { method: 'POST', body: formData })
          .then(res => res.json())
          .then(data => {
            btn.prop('disabled', false).text('Update Password');
            showToast(data.message, !data.success);
            if (data.success) form[0].reset();
          })
          .catch(err => {
            btn.prop('disabled', false).text('Update Password');
            showToast('Server error occurred.', true);
          });
      });

      // 3. Add Vehicle
      $('#addVehicleModal form').on('submit', function (e) {
        e.preventDefault();
        const form = $(this);
        const btn = form.find('button[type="submit"]');

        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Adding...');

        const formData = new FormData(this);
        formData.append('action', 'add_vehicle');

        fetch('action_client_profile.php', { method: 'POST', body: formData })
          .then(res => res.json())
          .then(data => {
            btn.prop('disabled', false).text('Add Vehicle');
            if (data.success) {
              showToast(data.message, false);
              $('#addVehicleModal').modal('hide');
              form[0].reset();
              // Ideally Append to table dynamically, but reload is easiest for now to render row fully 
              // or we build row. Let's reload for simplicity or build row.
              // For SPA feel, we should build row.
              setTimeout(() => location.reload(), 1000);
            } else {
              showToast(data.message, true);
            }
          })
          .catch(err => {
            btn.prop('disabled', false).text('Add Vehicle');
            showToast('Server error occurred.', true);
          });
      });

      // 4. Edit Vehicle UI Population
      $('.btn-edit-vehicle').on('click', function () {
        const btn = $(this);
        const id = btn.data('id');
        const plate = btn.data('plate');
        const color = btn.data('color');
        const type = btn.data('type');
        const brand = btn.data('brand');
        const modelId = btn.data('model-id');

        $('#editVehicleId').val(id);
        $('#editPlate').val(plate);
        $('#editColor').val(color);
        $('#editVehicleType').val(type).trigger('change');

        // We need to wait for brand dropdown to populate, then set brand
        // And then wait for model dropdown to populate, then set model
        // Simple timeout hack or event listener approach?
        // Since our dropdown logic is synchronous (using cached JS object allBrands), it should be fast.
        // However, the 'change' event might be async if we used AJAX there, but we are using local logic.
        // Let's manually trigger the updates to be safe.

        // Trigger Type Change logic manually or rely on trigger?
        // The existing logic binds to '#vehicleTypeInput' (Add Modal) and now we need it for '#editVehicleType' (Edit Modal).
        // We need to update the binding logic to cover both or duplicate it.
        // See below for binding update.

        // Timeout to allow dropdown population
        setTimeout(() => {
          $('#editBrand').val(brand).trigger('change');
          setTimeout(() => {
            $('#editModel').val(modelId);
          }, 50);
        }, 50);
      });

      // 5. Submit Edit Vehicle
      $('#editVehicleModal form').on('submit', function (e) {
        e.preventDefault();
        const form = $(this);
        const btn = form.find('button[type="submit"]');

        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

        const formData = new FormData(this);
        formData.append('action', 'edit_vehicle');

        fetch('action_client_profile.php', { method: 'POST', body: formData })
          .then(res => res.json())
          .then(data => {
            btn.prop('disabled', false).text('Save Changes');
            if (data.success) {
              showToast(data.message, false);
              $('#editVehicleModal').modal('hide');
              setTimeout(() => location.reload(), 1000);
            } else {
              showToast(data.message, true);
            }
          })
          .catch(err => {
            btn.prop('disabled', false).text('Save Changes');
            showToast('Server error occurred.', true);
          });
      });

      // 6. Delete Vehicle (Intercept Links)
      $('.btn-danger-custom').on('click', function (e) {
        e.preventDefault();
        const link = $(this);
        const url = new URL(link.attr('href'), window.location.origin);
        const id = url.searchParams.get('delete_vehicle');

        if (!confirm('Are you sure you want to delete this vehicle?')) return;

        const formData = new FormData();
        formData.append('action', 'delete_vehicle');
        formData.append('vehicle_id', id);

        fetch('action_client_profile.php', { method: 'POST', body: formData })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              showToast(data.message, false);
              link.closest('tr').fadeOut(300, function () { $(this).remove(); });
            } else {
              showToast(data.message, true);
            }
          })
          .catch(err => showToast('Server error.', true));
      });

      // 5. Upload Profile Pic
      // Avatar Form Submit
      // We need to change the inline onchange logic to use our listener
      // The HTML has id="avatarForm" and input has id="profilePicInput"
    });

    // Preview Image and Upload immediately (AJAX)
    document.getElementById('profilePicInput').addEventListener('change', function (e) {
      if (e.target.files && e.target.files[0]) {
        // Prepare FormData
        const formData = new FormData();
        formData.append('action', 'upload_pic');
        formData.append('profile_pic', e.target.files[0]);

        // Show loading or opacity
        const img = document.getElementById('avatarPreview');
        img.style.opacity = '0.5';

        fetch('action_client_profile.php', { method: 'POST', body: formData })
          .then(res => res.json())
          .then(data => {
            img.style.opacity = '1';
            if (data.success && data.image_url) {
              img.src = data.image_url + '?t=' + new Date().getTime(); // burst cache
              // Update navbar avatar if exists
              $('.navbar img[alt="Profile"]').attr('src', data.image_url + '?t=' + new Date().getTime());
            } else {
              alert(data.message || 'Upload failed');
            }
          })
          .catch(err => {
            img.style.opacity = '1';
            alert('Server error');
          });
      }
    });

    // 6. Delete Profile Pic
    // The existing button is wrapped in a form. We should intercept it.
    // The form doesn't have a specific ID, but it contains a button with onclick='confirm...'
    // Lets modify the HTML structure slightly via JS or target it carefully.
    // Or we handle it with a global listener on the button class?
    $(document).on('submit', 'form', function (e) {
      // Check if this form has delete_pic input
      if ($(this).find('input[name="delete_pic"]').length > 0) {
        e.preventDefault();
        if (!confirm('Delete profile picture?')) return;

        const btn = $(this).find('button');
        const formData = new FormData();
        formData.append('action', 'delete_pic');

        fetch('action_client_profile.php', { method: 'POST', body: formData })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              $('#avatarPreview').attr('src', data.image_url);
              $('.navbar img[alt="Profile"]').attr('src', data.image_url);
              btn.remove(); // Remove delete button
            } else {
              alert(data.message);
            }
          });
      }
    });

    // Nested dropdown for type > brand > model
    $(function () {
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
      // Generic function for dropdowns
      function setupDropdowns(typeSelector, brandSelector, modelSelector) {
        $(typeSelector).on('change', function () {
          var type = $(this).val();
          var brandSel = $(brandSelector);
          var modelSel = $(modelSelector);
          brandSel.empty().append('<option value="">Select Brand</option>');
          modelSel.empty().append('<option value="">Select Model</option>').prop('disabled', true);
          if (type && allBrands[type]) {
            var uniqueBrands = [...new Set(allBrands[type])];
            uniqueBrands.forEach(function (brand) {
              brandSel.append('<option value="' + brand + '">' + brand + '</option>');
            });
            brandSel.prop('disabled', false);
          } else {
            brandSel.prop('disabled', true);
          }
        });

        $(brandSelector).on('change', function () {
          var type = $(typeSelector).val();
          var brand = $(this).val();
          var modelSel = $(modelSelector);
          modelSel.empty().append('<option value="">Select Model</option>');
          if (type && brand && allModels[type] && allModels[type][brand]) {
            allModels[type][brand].forEach(function (obj) {
              modelSel.append('<option value="' + obj.model_id + '">' + obj.model + '</option>');
            });
            modelSel.prop('disabled', false);
          } else {
            modelSel.prop('disabled', true);
          }
        });
      }

      // Initialize for Add Modal
      setupDropdowns('#vehicleTypeInput', '#brandInput', '#modelInput');
      // Initialize for Edit Modal
      setupDropdowns('#editVehicleType', '#editBrand', '#editModel');

      // On modal open, reset dropdowns (Add Modal only)
      $('#addVehicleModal').on('show.bs.modal', function () {
        $('#vehicleTypeInput').val('');
        $('#brandInput').empty().append('<option value="">Select Brand</option>').prop('disabled', true);
        $('#modelInput').empty().append('<option value="">Select Model</option>').prop('disabled', true);
      });
    });

    // Forgot Password Logic
    document.getElementById('forgotPasswordForm').addEventListener('submit', function (e) {
      e.preventDefault();
      const step1 = document.getElementById('forgot-step-1');
      const step2 = document.getElementById('forgot-step-2');
      const nextBtn = document.getElementById('forgotNextBtn');
      const resetBtn = document.getElementById('forgotResetBtn');

      // Determine which step we are on based on visibility
      if (step1.style.display !== 'none') {
        const formData = new FormData(this);
        formData.append('forgot_password_action', '1'); // Trigger the backend logic

        fetch('action_client_profile.php', { method: 'POST', body: formData })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              step1.style.display = 'none';
              step2.style.display = 'block';
              nextBtn.style.display = 'none';
              resetBtn.style.display = 'inline-block';
            } else {
              alert(data.message || 'Incorrect security word.');
            }
          })
          .catch(err => alert('Error checking security word.'));
      }
      else if (step2.style.display !== 'none') {
        const formData = new FormData(this);
        formData.append('forgot_password_action', '1'); // Keep this key so backend knows what to do

        fetch('action_client_profile.php', { method: 'POST', body: formData })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              alert(data.message);
              location.reload();
            } else {
              alert(data.message);
            }
          })
          .catch(err => alert('Error resetting password.'));
      }
    });

  </script>
</body>

</html>