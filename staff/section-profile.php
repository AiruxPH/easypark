<?php
require_once __DIR__ . '/section-common.php';

// Staff Profile Section (for include or AJAX)
$profilePic = (!empty($staff['image']) && file_exists('../images/' . $staff['image'])) ? '../images/' . $staff['image'] : '../images/default.jpg';
?>
<div class="section-card mb-4">
  <h4 class="mb-3 text-info"><i class="fa fa-user"></i> My Profile</h4>
  <?php if (isset($_GET['profile_updated'])): ?>
    <div class="alert alert-success">Profile updated successfully.</div>
  <?php endif; ?>
  <div class="row align-items-center">
    <div class="col-md-2 text-center">
      <img src="<?= htmlspecialchars($profilePic) ?>" alt="Profile Picture" class="rounded-circle mb-2" style="width:90px;height:90px;object-fit:cover;border:3px solid #ffc107;">
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
          <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($staff['first_name']) ?>" required>
        </div>
        <div class="form-group col-md-4">
          <label>Middle Name</label>
          <input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($staff['middle_name']) ?>">
        </div>
        <div class="form-group col-md-4">
          <label>Last Name</label>
          <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($staff['last_name']) ?>" required>
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
