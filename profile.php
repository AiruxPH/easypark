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


// Fetch user info
$stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = ?');
$stmt->execute([$user_id]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch user's vehicles with brand, model, and type from Vehicle_Models
$stmt = $pdo->prepare('SELECT v.*, vm.brand, vm.model, vm.type FROM vehicles v LEFT JOIN Vehicle_Models vm ON v.model_id = vm.model_id WHERE v.user_id = ?');
$stmt->execute([$user_id]);
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// For each vehicle, check if it has an active reservation and fetch details if so
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

$message = '';
// Handle profile update, vehicle add/edit/delete, reservation here (to be implemented)

// Handle profile picture upload
if (isset($_POST['upload_pic']) && isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
  $fileTmp = $_FILES['profile_pic']['tmp_name'];
  $fileName = basename($_FILES['profile_pic']['name']);
  $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
  $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
  if (in_array($fileExt, $allowed)) {
    $newName = 'profile_' . $user_id . '_' . time() . '.' . $fileExt;
    $targetPath = 'images/' . $newName;
    if (move_uploaded_file($fileTmp, $targetPath)) {
      // Remove old pic if not default
      if (!empty($userData['image']) && $userData['image'] !== 'default.jpg' && file_exists('images/' . $userData['image'])) {
        unlink('images/' . $userData['image']);
      }
      $stmt = $pdo->prepare('UPDATE users SET image = ? WHERE user_id = ?');
      $stmt->execute([$newName, $user_id]);
      header('Location: profile.php');
      exit();
    } else {
      $message = '❌ Failed to upload image.';
    }
  } else {
    $message = '❌ Invalid file type.';
  }
}
// Handle profile picture delete
if (isset($_POST['delete_pic'])) {
  if (!empty($userData['image']) && $userData['image'] !== 'default.jpg' && file_exists('images/' . $userData['image'])) {
    unlink('images/' . $userData['image']);
  }
  $stmt = $pdo->prepare('UPDATE users SET image = NULL WHERE user_id = ?');
  $stmt->execute([$user_id]);
  header('Location: profile.php');
  exit();
}

// Handle vehicle deletion
if (isset($_GET['delete_vehicle'])) {
  $vehicle_id = intval($_GET['delete_vehicle']);
  // Check for active reservation for this vehicle
  $stmt = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE vehicle_id = ? AND status NOT IN ("cancelled", "completed") AND end_time > NOW()');
  $stmt->execute([$vehicle_id]);
  $active = $stmt->fetchColumn();
  if ($active > 0) {
    $message = '❌ Cannot delete vehicle with an active reservation.';
  } else {
    $stmt = $pdo->prepare('DELETE FROM vehicles WHERE vehicle_id = ? AND user_id = ?');
    $stmt->execute([$vehicle_id, $user_id]);
    header('Location: profile.php');
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
    header('Location: profile.php');
    exit();
  } else {
    $message = '❌ Please fill all vehicle fields.';
  }
}
// Handle profile update
if (isset($_POST['update_profile'])) {
  $first_name = trim($_POST['first_name']);
  $middle_name = trim($_POST['middle_name']);
  $last_name = trim($_POST['last_name']);
  $phone = trim($_POST['phone']);

  // Validate and update profile information
  if ($first_name && $last_name) {
    $stmt = $pdo->prepare('UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, phone = ? WHERE user_id = ?');
    $stmt->execute([$first_name, $middle_name, $last_name, $phone, $user_id]);
    $message = '✅ Profile updated successfully.';
  } else {
    $message = '❌ Please fill in all required fields.';
  }
}

// Handle password change (plain, for demo only)
if (isset($_POST['change_password'])) {
  $current = $_POST['current_password'] ?? '';
  $new = $_POST['new_password'] ?? '';
  $confirm = $_POST['confirm_new_password'] ?? '';
  if (!$current || !$new || !$confirm) {
    $message = '❌ Please fill all password fields.';
  } elseif ($new !== $confirm) {
    $message = '❌ New passwords do not match.';
  } else {
    // Fetch current password (plain, for demo only)
    $stmt = $pdo->prepare('SELECT password FROM users WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && $current === $row['password']) {
      $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE user_id = ?');
      $stmt->execute([$new, $user_id]);
      $message = '✅ Password changed successfully!';
    } else {
      $message = '❌ Current password is incorrect.';
    }
  }
}

// Handle forgot password - AJAX step logic
if (isset($_POST['forgot_password_action'])) {
  header('Content-Type: application/json');
  if (!isset($_POST['fp_security_word'])) {
    echo json_encode(['success' => false, 'message' => 'Security word required.']);
    exit;
  }
  $fp_security_word = trim($_POST['fp_security_word']);
  // Check security word
  $stmt = $pdo->prepare('SELECT security_word FROM users WHERE user_id = ?');
  $stmt->execute([$user_id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$row || strtolower($fp_security_word) !== strtolower($row['security_word'])) {
    echo json_encode(['success' => false, 'message' => 'Incorrect security word.']);
    exit;
  }
  // If new password fields are present, update password
  if (!empty($_POST['fp_new_password']) && !empty($_POST['fp_confirm_new_password'])) {
    $new = $_POST['fp_new_password'];
    $confirm = $_POST['fp_confirm_new_password'];
    if ($new !== $confirm) {
      echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
      exit;
    }
    $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE user_id = ?');
    $stmt->execute([$new, $user_id]);
    echo json_encode(['success' => true, 'message' => 'Password reset successful!']);
    exit;
  }
  // Security word correct, prompt for new password
  echo json_encode(['success' => true]);
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile - EasyPark</title>
  <link rel="stylesheet" href="css/bootstrap.min.css">
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
                    value="<?= htmlspecialchars($user['first_name']) ?>" required>
                </div>
                <div class="form-group col-md-4">
                  <label>Middle Name</label>
                  <input type="text" name="middle_name" class="form-control"
                    value="<?= htmlspecialchars($user['middle_name']) ?>">
                </div>
                <div class="form-group col-md-4">
                  <label>Last Name</label>
                  <input type="text" name="last_name" class="form-control"
                    value="<?= htmlspecialchars($user['last_name']) ?>" required>
                </div>
              </div>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label>Email Address</label>
                  <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly
                    title="Email cannot be changed">
                </div>
                <div class="form-group col-md-6">
                  <label>Phone Number</label>
                  <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>">
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
              <label>Car Model</label>
              <!-- Note: ideally this should be a dynamic dropdown from Vehicle_Models table -->
              <!-- For now, using a simple input or static select if we don't have the list loaded. -->
              <!-- The original code used model_id input, implying a dropdown. Let's make it a number input if we can't fetch. -->
              <!-- Wait, original code: $stmt = $pdo->prepare('INSERT INTO vehicles (..., model_id, ...)...'); -->
              <!-- We need to provide model_id. Let's add a dropdown with some likely IDs or just an input for ID if we don't want to query. -->
              <!-- Better: Query models first? The original file didn't querying models for the ADD form, it just had inputs. -->
              <!-- Actually, the original file had `if (isset($_POST['add_vehicle'])) ... $model_id = intval($_POST['model_id']);` -->
              <!-- But I don't see the HTML form in the snippet I read earlier? -->
              <!-- Ah, the snippet I read earlier had logic but I might have missed the actual HTML form for adding inside or outside the snippet? -->
              <!-- Let's check lines 100-113: handles POST. -->
              <!-- I'll add a dropdown if I can, or just an input. Since I don't want to break it, I'll fetch models if possible. -->
              <!-- I will add a simple Query to fetch models at the top to populate this. -->
              <select name="model_id" class="form-control" required>
                <option value="">Select Model</option>
                <?php
                // Quick fetch for models
                $m_stmt = $pdo->query("SELECT * FROM Vehicle_Models ORDER BY brand, model");
                while ($m = $m_stmt->fetch(PDO::FETCH_ASSOC)) {
                  echo "<option value='" . $m['model_id'] . "'>" . $m['brand'] . " " . $m['model'] . " (" . $m['type'] . ")</option>";
                }
                ?>
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
    // Preview Image immediately
    document.getElementById('profilePicInput').addEventListener('change', function (e) {
      if (e.target.files && e.target.files[0]) {
        // Auto submit form for better UX or just preview?
        // The logic requires POST to update DB, so we should probably submit.
        // But let's preview first, user might want to cancel? 
        // The original logic had a submit button. Let's auto-submit for smoother experience.
        document.getElementById('avatarForm').submit();
      }
    });

    // Forgot Password Logic (Preserved)
    document.getElementById('forgotPasswordForm').addEventListener('submit', function (e) {
      const step1 = document.getElementById('forgot-step-1');
      const step2 = document.getElementById('forgot-step-2');
      const nextBtn = document.getElementById('forgotNextBtn');
      const resetBtn = document.getElementById('forgotResetBtn');

      if (step1.style.display !== 'none') {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('profile.php', { method: 'POST', body: formData })
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
      // If step 2 is visible, let the form submit normally (AJAX logic in PHP at top handles this? No, lines 156-187 return JSON)
      // Wait, if it returns JSON, we shouldn't submit normally or we get JSON on screen.
      // We need to handle the Step 2 submit via AJAX too.
      if (step2.style.display !== 'none') {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('profile.php', { method: 'POST', body: formData })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              alert(data.message);
              location.reload(); // Reload to login with new pass or just refresh
            } else {
              alert(data.message);
            }
          });
      }
    });
  </script>
</body>

</html>