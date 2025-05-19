<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    header("Location: ../index.php");
    exit();
}
require_once '../db.php';

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
    header('Location: profile.php?profile_updated=1');
    exit();
}
// Handle profile picture upload
if (isset($_POST['upload_pic']) && isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
    $fileTmp = $_FILES['profile_pic']['tmp_name'];
    $fileName = basename($_FILES['profile_pic']['name']);
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (in_array($fileExt, $allowed)) {
        $newName = 'profile_staff_' . $staff_id . '_' . time() . '.' . $fileExt;
        $targetPath = '../images/' . $newName;
        if (move_uploaded_file($fileTmp, $targetPath)) {
            if (!empty($staff['image']) && $staff['image'] !== 'default.jpg' && file_exists('../images/' . $staff['image'])) {
                unlink('../images/' . $staff['image']);
            }
            $stmt = $pdo->prepare('UPDATE users SET image = ? WHERE user_id = ?');
            $stmt->execute([$newName, $staff_id]);
            header('Location: profile.php?profile_updated=1');
            exit();
        }
    }
}
// Handle profile picture delete
if (isset($_POST['delete_pic'])) {
    if (!empty($staff['image']) && $staff['image'] !== 'default.jpg' && file_exists('../images/' . $staff['image'])) {
        unlink('../images/' . $staff['image']);
    }
    $stmt = $pdo->prepare('UPDATE users SET image = NULL WHERE user_id = ?');
    $stmt->execute([$staff_id]);
    header('Location: profile.php?profile_updated=1');
    exit();
}
$profilePic = (!empty($staff['image']) && file_exists('../images/' . $staff['image'])) ? '../images/' . $staff['image'] : '../images/default.jpg';

// Handle password change
$password_message = '';
if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_new_password'] ?? '';
    if (!$current || !$new || !$confirm) {
        $password_message = '❌ Please fill all password fields.';
    } elseif ($new !== $confirm) {
        $password_message = '❌ New passwords do not match.';
    } else {
        // Fetch current hashed password
        $stmt = $pdo->prepare('SELECT password FROM users WHERE user_id = ?');
        $stmt->execute([$staff_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && password_verify($current, $row['password'])) {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE user_id = ?');
            $stmt->execute([$hashed, $staff_id]);
            $password_message = '✅ Password changed successfully!';
        } else {
            $password_message = '❌ Current password is incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>My Profile - Staff | EasyPark</title>
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
.section-card {
  background: #232526;
  border-radius: 1rem;
  box-shadow: 0 2px 12px rgba(0,0,0,0.12);
  padding: 2rem;
  margin-top: 2rem;
  color: #fff;
}
.profile-container {
  background: #2c2f33;
  border-radius: 1rem;
  padding: 2rem;
  margin-top: 2rem;
  color: #fff;
}
.profile-pic {
  width: 120px;
  height: 120px;
  object-fit: cover;
  border-radius: 50%;
  border: 3px solid #ffc107;
}
.password-message {
  margin-bottom: 1rem;
  padding: 0.75rem;
  border-radius: 0.5rem;
}
</style>
</head>
<body>
<div class="bg-overlay min-vh-100 d-flex align-items-center justify-content-center">
  <div class="container" style="max-width: 700px;">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
      <h2 class="text-warning mb-0"><i class="fa fa-user"></i> My Profile</h2>
      <a href="staff-dashboard.php" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
    </div>
    <div class="section-card mb-4 p-4">
      <?php if (isset($_GET['profile_updated'])): ?>
        <div class="alert alert-success">Profile updated successfully.</div>
      <?php endif; ?>
      <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo strpos($message, '✅') !== false ? 'success' : 'danger'; ?>"> <?php echo htmlspecialchars($message); ?> </div>
      <?php endif; ?>
      <div class="row align-items-center">
        <div class="col-md-4 text-center mb-3 mb-md-0">
          <img src="<?= htmlspecialchars($profilePic) ?>" alt="Profile Picture" class="rounded-circle mb-2 shadow" style="width:120px;height:120px;object-fit:cover;border:3px solid #ffc107;">
          <form method="POST" enctype="multipart/form-data" class="mt-2">
            <div class="custom-file mb-1 position-relative">
              <input type="file" name="profile_pic" accept="image/*" class="custom-file-input form-control d-none" id="profilePicInput">
              <label class="custom-file-label btn btn-outline-warning w-100" for="profilePicInput" id="profilePicLabel">
                <i class="fa fa-upload"></i> <span id="profilePicLabelText">Choose a new picture</span>
              </label>
            </div>
            <button type="submit" name="upload_pic" class="btn btn-warning btn-sm w-100 mt-2">Change Picture</button>
          </form>
          <script>
            // Fancy file picker label update
            document.addEventListener('DOMContentLoaded', function() {
              var input = document.getElementById('profilePicInput');
              var label = document.getElementById('profilePicLabelText');
              if(input && label) {
                input.addEventListener('change', function(e) {
                  var fileName = input.files && input.files.length > 0 ? input.files[0].name : 'Choose a new picture';
                  label.textContent = fileName;
                });
                // Make label clickable
                document.getElementById('profilePicLabel').addEventListener('click', function(e) {
                  input.click();
                });
              }
            });
          </script>
          <?php if (!empty($staff['image'])): ?>
          <form method="POST" class="mt-1">
            <button type="submit" name="delete_pic" class="btn btn-danger btn-sm w-100">Delete Picture</button>
          </form>
          <?php endif; ?>
        </div>
        <div class="col-md-8">
          <form method="POST" class="row g-3">
            <div class="form-group col-md-6">
              <label>First Name</label>
              <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($staff['first_name']) ?>" required>
            </div>
            <div class="form-group col-md-6">
              <label>Middle Name</label>
              <input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($staff['middle_name']) ?>">
            </div>
            <div class="form-group col-md-6">
              <label>Last Name</label>
              <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($staff['last_name']) ?>" required>
            </div>
            <div class="form-group col-md-6">
              <label>Email</label>
              <input type="email" class="form-control" value="<?= htmlspecialchars($staff['email']) ?>" disabled>
            </div>
            <div class="form-group col-md-12">
              <label>Phone</label>
              <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($staff['phone']) ?>">
            </div>
            <div class="form-group col-12 mt-3">
              <button type="submit" name="update_profile" class="btn btn-warning w-100">Update Profile</button>
            </div>
          </form>
          <hr class="my-4">
        </div>
      </div>
    </div>
    <!-- Separate container for Change Password -->
    <div class="profile-container mt-4">
      <h5 class="text-warning mb-3"><i class="fa fa-lock"></i> Change Password</h5>
      <?php if (!empty($password_message)): ?>
        <div class="password-message"><?php echo htmlspecialchars($password_message); ?></div>
      <?php endif; ?>
      <form method="POST" class="change-password-form">
        <div class="form-group mb-2">
          <label>Current Password</label>
          <input type="password" name="current_password" class="form-control" required>
        </div>
        <div class="form-group mb-2">
          <label>New Password</label>
          <input type="password" name="new_password" class="form-control" required>
        </div>
        <div class="form-group mb-2">
          <label>Confirm New Password</label>
          <input type="password" name="confirm_new_password" class="form-control" required>
        </div>
        <div class="form-group col-12 mt-2">
          <button type="submit" name="change_password" class="btn btn-warning w-100">Change Password</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script src="../js/bootstrap.bundle.min.js"></script>
<script src="../js/jquery.min.js"></script>
<script src="../js/ef9baa832e.js" crossorigin="anonymous"></script>
</body>
</html>
