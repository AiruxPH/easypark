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
        <a class="nav-link" href="logout.php">Logout</a>
      </li>
    </ul>
  </div>
</nav>
<div class="container py-4">
    <h2 class="mb-4 text-warning custom-size display-4 text-center">My Profile</h2>
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
    <div class="text-center mt-4">
      <a href="index.php" class="btn btn-primary">Go back to Home</a>
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
