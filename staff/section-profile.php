<?php
require_once __DIR__ . '/section-common.php';
// Profile Section (for include or AJAX)

// Handle profile update (POST) inside this section
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    $update_stmt = $pdo->prepare('UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, phone = ? WHERE user_id = ?');
    $update_stmt->execute([$first_name, $middle_name, $last_name, $phone, $staff_id]);
    // Refresh staff data
    $stmt = $pdo->prepare('SELECT first_name, middle_name, last_name, email, phone, image FROM users WHERE user_id = ?');
    $stmt->execute([$staff_id]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
    $message = "✅ Profile updated successfully.";
  }
}
$profilePic = (!empty($staff['image']) && file_exists('../images/' . $staff['image'])) ? '../images/' . $staff['image'] : '../images/default.jpg';
?>
<div class="col-md-2 text-center">
  <img src="<?= htmlspecialchars($profilePic) ?>" alt="Profile Picture" class="rounded-circle mb-2"
    style="width:90px;height:90px;object-fit:cover;border:3px solid #ffc107;">
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
      <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($staff['first_name']) ?>"
        required>
    </div>
    <div class="form-group col-md-4">
      <label>Middle Name</label>
      <input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($staff['middle_name']) ?>">
    </div>
    <div class="form-group col-md-4">
      <label>Last Name</label>
      <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($staff['last_name']) ?>"
        required>
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