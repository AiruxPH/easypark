<?php
// User management section
// Expects: $users, $isSuperAdmin, $totalPages, $currentPage, etc.
?>
<div id="users-container" style="<?= isset($_GET['users']) ? '' : 'display:none;' ?>">
  <div class="card mb-4 shadow">
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
      <span><i class="fas fa-users"></i> User Management</span>
      <button class="btn btn-light btn-sm" onclick="showAddUserModal()" <?= $isSuperAdmin || $_SESSION['user_type'] === 'admin' ? '' : 'disabled' ?>>
        <i class="fas fa-plus"></i> Add User
      </button>
    </div>
    <div class="card-body">
      <!-- Search and Filter Form -->
      <form class="mb-4" method="GET">
        <input type="hidden" name="users" value="1">
        <div class="row align-items-end">
          <div class="col-md-4">
            <div class="form-group">
              <!-- Add search fields here if needed -->
            </div>
          </div>
        </div>
      </form>
      <?php if ($users && count($users) > 0): ?>
      <div class="table-responsive">
        <!-- User table goes here -->
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
