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
<div class="glass-card">
  <div class="row">
    <!-- Sidebar / Avatar -->
    <div class="col-md-4 text-center border-right border-secondary pr-md-4">
      <h4 class="text-warning mb-4"><i class="fas fa-id-card-alt mr-2"></i> My Profile</h4>

      <div class="position-relative d-inline-block mb-3">
        <img id="currentProfilePic" src="<?= htmlspecialchars($profilePic) ?>" alt="Profile Picture"
          class="rounded-circle shadow-lg"
          style="width: 150px; height: 150px; object-fit: cover; border: 4px solid var(--primary);">
        <label for="profilePicInput"
          class="position-absolute bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center shadow-sm"
          style="width: 40px; height: 40px; bottom: 0; right: 10px; cursor: pointer; transition: transform 0.2s;"
          onclick="this.style.transform='scale(0.9)'" onmouseout="this.style.transform='scale(1)'">
          <i class="fas fa-camera"></i>
        </label>
        <input type="file" id="profilePicInput" accept="image/*" class="d-none">
      </div>

      <div class="mb-4">
        <h5 class="text-white mb-1"><?= htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']) ?></h5>
        <span class="badge badge-glass-info">Staff Access</span>
      </div>

      <?php if (!empty($staff['image']) && $staff['image'] !== 'default.jpg'): ?>
        <button id="btnDeletePic" class="btn btn-outline-danger btn-sm rounded-pill px-3 mb-4">
          <i class="fas fa-trash-alt mr-1"></i> Remove Photo
        </button>
      <?php endif; ?>

      <div class="text-left mt-3 px-3">
        <label class="text-white-50 small text-uppercase font-weight-bold">Role Responsibilities</label>
        <ul class="text-white small pl-3" style="opacity: 0.8;">
          <li>Manage Parking Slots</li>
          <li>Verify Reservations</li>
          <li>Monitor Active Vehicles</li>
        </ul>
      </div>
    </div>

    <!-- Main Form Area -->
    <div class="col-md-8 pl-md-4">
      <ul class="nav nav-pills mb-4" id="profileTabs" role="tablist">
        <li class="nav-item">
          <a class="nav-link active btn-glass mr-2" id="details-tab" data-toggle="pill" href="#details" role="tab"
            aria-controls="details" aria-selected="true"><i class="fas fa-user-edit mr-2"></i> Edit Details</a>
        </li>
        <li class="nav-item">
          <a class="nav-link btn-glass" id="security-tab" data-toggle="pill" href="#security" role="tab"
            aria-controls="security" aria-selected="false"><i class="fas fa-lock mr-2"></i> Security</a>
        </li>
      </ul>

      <div class="tab-content" id="profileTabsContent">
        <!-- Details Tab -->
        <div class="tab-pane fade show active" id="details" role="tabpanel" aria-labelledby="details-tab">
          <form id="formUpdateProfile">
            <input type="hidden" name="action" value="update_details">
            <div class="form-row">
              <div class="form-group col-md-4">
                <label class="text-white-50 small">First Name</label>
                <input type="text" name="first_name" class="form-control glass-input"
                  value="<?= htmlspecialchars($staff['first_name']) ?>" required>
              </div>
              <div class="form-group col-md-4">
                <label class="text-white-50 small">Middle Name</label>
                <input type="text" name="middle_name" class="form-control glass-input"
                  value="<?= htmlspecialchars($staff['middle_name']) ?>">
              </div>
              <div class="form-group col-md-4">
                <label class="text-white-50 small">Last Name</label>
                <input type="text" name="last_name" class="form-control glass-input"
                  value="<?= htmlspecialchars($staff['last_name']) ?>" required>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-6">
                <label class="text-white-50 small">Email Address</label>
                <input type="email" class="form-control glass-input" value="<?= htmlspecialchars($staff['email']) ?>"
                  readonly style="opacity: 0.7; cursor: not-allowed;">
              </div>
              <div class="form-group col-md-6">
                <label class="text-white-50 small">Phone Number</label>
                <input type="text" name="phone" class="form-control glass-input"
                  value="<?= htmlspecialchars($staff['phone']) ?>">
              </div>
            </div>

            <div class="d-flex justify-content-end mt-3">
              <button type="submit" class="btn btn-glass-primary px-4"><i class="fas fa-save mr-2"></i> Save
                Changes</button>
            </div>
          </form>
        </div>

        <!-- Security Tab -->
        <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
          <div class="alert alert-warning text-dark small"><i class="fas fa-shield-alt mr-2"></i> Ensure your password
            is strong (8+ characters, mixed case).</div>
          <form id="formChangePassword">
            <input type="hidden" name="action" value="change_password">
            <div class="form-group">
              <label class="text-white-50 small">Current Password</label>
              <input type="password" name="current_password" class="form-control glass-input" required>
            </div>
            <div class="form-group">
              <label class="text-white-50 small">New Password</label>
              <input type="password" name="new_password" class="form-control glass-input" required>
            </div>
            <div class="form-group">
              <label class="text-white-50 small">Confirm New Password</label>
              <input type="password" name="confirm_new_password" class="form-control glass-input" required>
            </div>

            <div class="d-flex justify-content-end mt-3">
              <button type="submit" class="btn btn-danger px-4 shadow-sm"><i class="fas fa-key mr-2"></i> Update
                Password</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Toast Container for Notifications -->
<div aria-live="polite" aria-atomic="true" style="position: fixed; bottom: 20px; right: 20px; z-index: 1050;">
  <div id="profileToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true" data-delay="3000">
    <div class="toast-header bg-success text-white">
      <strong class="mr-auto"><i class="fas fa-check-circle mr-2"></i> Notification</strong>
      <button type="button" class="ml-2 mb-1 close text-white" data-dismiss="toast" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
    <div class="toast-body text-dark font-weight-bold">
      Action completed successfully.
    </div>
  </div>
</div>

<script>
  $(document).ready(function () {
    function showToast(message, isError = false) {
      var toast = $('#profileToast');
      toast.find('.toast-body').text(message);
      if (isError) {
        toast.find('.toast-header').removeClass('bg-success').addClass('bg-danger');
      } else {
        toast.find('.toast-header').removeClass('bg-danger').addClass('bg-success');
      }
      toast.toast('show');
    }

    // 1. Update Details
    $('#formUpdateProfile').on('submit', function (e) {
      e.preventDefault();
      $.post('action_profile.php', $(this).serialize(), function (data) {
        showToast(data.message, !data.success);
        // Optionally update header name if changed (requires page reload or DOM update)
        // Ideally we rely on refresh or manual update
      }, 'json').fail(function () {
        showToast('Server error occurred.', true);
      });
    });

    // 2. Change Password
    $('#formChangePassword').on('submit', function (e) {
      e.preventDefault();
      $.post('action_profile.php', $(this).serialize(), function (data) {
        showToast(data.message, !data.success);
        if (data.success) $('#formChangePassword')[0].reset();
      }, 'json').fail(function () {
        showToast('Server error occurred.', true);
      });
    });

    // 3. Upload Picture
    $('#profilePicInput').on('change', function () {
      if (this.files && this.files[0]) {
        var formData = new FormData();
        formData.append('action', 'upload_pic');
        formData.append('profile_pic', this.files[0]);

        $.ajax({
          url: 'action_profile.php',
          type: 'POST',
          data: formData,
          contentType: false,
          processData: false,
          dataType: 'json',
          success: function (data) {
            showToast(data.message, !data.success);
            if (data.success && data.new_image_url) {
              $('#currentProfilePic').attr('src', data.new_image_url);
              // Also update header image if possible
              $('.header-bar img').attr('src', data.new_image_url);
            }
          },
          error: function () {
            showToast('Image upload failed.', true);
          }
        });
      }
    });

    // 4. Delete Picture
    $('#btnDeletePic').on('click', function (e) {
      e.preventDefault();
      if (!confirm('Are you sure you want to remove your profile picture?')) return;

      $.post('action_profile.php', { action: 'delete_pic' }, function (data) {
        showToast(data.message, !data.success);
        if (data.success) {
          $('#currentProfilePic').attr('src', '../images/default.jpg');
          $(this).hide(); // Hide delete button
        }
      }, 'json');
    });
  });
</script>