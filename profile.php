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
            if (!empty($user['image']) && $user['image'] !== 'default.jpg' && file_exists('images/' . $user['image'])) {
                unlink('images/' . $user['image']);
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
    if (!empty($user['image']) && $user['image'] !== 'default.jpg' && file_exists('images/' . $user['image'])) {
        unlink('images/' . $user['image']);
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
        body.bg-car {
            background-image: url('bg-car.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        .bg-image-dark {
            background-image: url('nav-bg.jpg');
            background-size: 100% auto;
            background-position: top left;
            background-repeat: repeat-y;
        }
        .custom-size {
            color: #ffc107;
            transition: text-shadow 0.3s ease-in-out, color 0.3s ease-in-out;
        }
        .custom-size:hover {
            text-shadow: 0 0 10px #ffd700, 0 0 20px #ffd700, 0 0 30px #ffd700;
            color: white;
        }
        .profile-section { background: rgba(255,255,255,0.95); border-radius: 8px; box-shadow: 0 2px 8px #0001; padding: 2rem; margin-bottom: 2rem; }
        .vehicle-table th, .vehicle-table td { vertical-align: middle; }
        .navbar-dark .navbar-brand, .navbar-dark .navbar-nav .nav-link {
            color: #fff;
        }
        .navbar-dark .navbar-brand:hover, .navbar-dark .navbar-nav .nav-link:hover {
            color: #ccc;
        }
        .profile-pic {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #ffc107;
            background: #fff;
        }
        .profile-pic-upload {
            display: block;
            margin: 0.5rem auto 0 auto;
        }
        .delete-pic-btn {
            margin-top: 0.5rem;
        }
        @media (max-width: 768px) {
            .custom-size.display-4 { font-size: 2.5rem; }
        }
        @media (max-width: 576px) {
            .custom-size.display-4 { font-size: 2rem; }
        }
        .fancy-file-label {
            display: inline-block;
            cursor: pointer;
            padding: 0.5rem 1.2rem;
            border-radius: 25px;
            background: linear-gradient(90deg, #ffc107 0%, #ff9800 100%);
            color: #fff;
            font-weight: 600;
            box-shadow: 0 2px 8px #0002;
            transition: background 0.2s, color 0.2s;
            margin-bottom: 0.5rem;
        }
        .fancy-file-label:hover, .fancy-file-label:focus {
            background: linear-gradient(90deg, #ff9800 0%, #ffc107 100%);
            color: #222;
            text-decoration: none;
        }
        .profile-pic {
            transition: box-shadow 0.2s, border 0.2s;
            box-shadow: 0 2px 8px #0002;
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
        <a class="nav-link" href="vehicles.php">My Vehicles</a>
      </li>
    </ul>
  </div>
</nav>
<div class="container py-4">
    <h2 class="mb-4 text-warning custom-size display-4 text-center">My Profile</h2>
    <?php
    // Show error message if set
    if (!empty($message)) {
        echo '<div class="alert alert-danger text-center">' . htmlspecialchars($message) . '</div>';
    }
    ?>
    <div class="profile-section mb-4 text-center">
        <?php
        $profilePic = (!empty($user['image']) && file_exists('images/' . $user['image'])) ? 'images/' . $user['image'] : 'images/default.jpg';
        ?>
        <img id="profilePicPreview" src="<?= htmlspecialchars($profilePic) ?>" alt="Profile Picture" class="profile-pic mb-2">
        <form method="POST" action="profile.php" enctype="multipart/form-data" class="mb-2" id="profilePicForm">
            <label for="profilePicInput" class="fancy-file-label btn btn-warning btn-sm mt-2 mb-0">
                <i class="fa fa-camera"></i> Choose New Picture
            </label>
            <input type="file" id="profilePicInput" name="profile_pic" accept="image/*" class="d-none">
            <button type="submit" name="upload_pic" class="btn btn-sm btn-warning mt-2">Change Picture</button>
        </form>
        <?php if (!empty($user['image'])): ?>
        <form method="POST" action="profile.php" id="deletePicForm">
            <button type="submit" name="delete_pic" class="btn btn-sm btn-danger delete-pic-btn">Delete Picture</button>
        </form>
        <?php endif; ?>
    </div>
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
        <h4>Change Password</h4>
        <form method="POST" action="profile.php">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="form-group col-md-4">
                    <label>New Password</label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
                <div class="form-group col-md-4">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_new_password" class="form-control" required>
                </div>
            </div>
            <button type="submit" name="change_password" class="btn btn-warning text-white">Change Password</button>
        </form>
    </div>

    <div class="text-center mt-4">
      <a href="index.php" class="btn btn-primary">Go back to Home</a>
      <a href="logout.php" class="btn btn-danger ml-2">Logout</a>
    </div>
</div>
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
<script>
  const navbar = document.getElementById('navbar');
  let lastScrollTop = 0;
  window.addEventListener('scroll', function () {
    let st = window.scrollY;
    if (st > lastScrollTop && st > 100) {
      // Scroll down: collapse navbar if open
      if (navbar.classList.contains('show')) {
        $('.navbar-collapse').collapse('hide');
      }
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }
    lastScrollTop = st;
  });
  // Fancy image picker preview
  document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('profilePicInput');
    const previewImg = document.getElementById('profilePicPreview');
    const picForm = document.getElementById('profilePicForm');
    const deleteForm = document.getElementById('deletePicForm');
    if (fileInput && previewImg) {
      fileInput.addEventListener('change', function(e) {
        if (fileInput.files && fileInput.files[0]) {
          const reader = new FileReader();
          reader.onload = function(ev) {
            previewImg.src = ev.target.result;
          };
          reader.readAsDataURL(fileInput.files[0]);
        }
      });
      previewImg.style.cursor = 'pointer';
      previewImg.addEventListener('click', function() {
        fileInput.click();
      });
    }
    // Confirmation for profile picture change
    if (picForm) {
      picForm.addEventListener('submit', function(e) {
        if (fileInput.value) {
          if (!confirm('Are you sure you want to change your profile picture?')) {
            e.preventDefault();
          }
        }
      });
    }
    // Confirmation for profile picture deletion
    if (deleteForm) {
      deleteForm.addEventListener('submit', function(e) {
        if (!confirm('Are you sure you want to delete your profile picture?')) {
          e.preventDefault();
        }
      });
    }
  });
</script>
</body>
</html>
