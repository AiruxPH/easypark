<?php
require_once '../db.php';
// Handle AJAX request for parking slots table only
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    include __DIR__ . '../admin-dashboard.php-table.php';
    exit;
}
// Fetch parking slot statistics using PDO
$totalSlots = $availableSlots = $reservedSlots = $occupiedSlots = 0;

// Total slots
$stmt = $pdo->query("SELECT COUNT(*) as total FROM parking_slots");
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $totalSlots = $row['total'];
}
// Available slots
$stmt = $pdo->query("SELECT COUNT(*) as available FROM parking_slots WHERE slot_status='available'");
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $availableSlots = $row['available'];
}
// Reserved slots
$stmt = $pdo->query("SELECT COUNT(*) as reserved FROM parking_slots WHERE slot_status='reserved'");
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $reservedSlots = $row['reserved'];
}
// Occupied slots
$stmt = $pdo->query("SELECT COUNT(*) as occupied FROM parking_slots WHERE slot_status='occupied'");
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $occupiedSlots = $row['occupied'];
}

// Determine if parking slots view should be shown
$showParkingSlots = isset($_GET['page']) || isset($_GET['status']) || isset($_GET['type']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>EasyPark Admin Dashboard</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <script src="../js/ef9baa832e.js" crossorigin="anonymous"></script>
  <style>
    body {
      min-height: 100vh;
      background: #f8f9fa;
    }
    .sidebar {
      min-height: 100vh;
      background: #343a40;
      color: #fff;
    }
    .sidebar .nav-link, .sidebar .navbar-brand {
      color: #fff;
    }
    .sidebar .nav-link.active, .sidebar .nav-link:hover {
      background: #495057;
      color: #ffc107;
    }
    .sidebar .navbar-brand {
      font-weight: bold;
      font-size: 1.5rem;
      letter-spacing: 1px;
    }
    .sidebar .fa {
      margin-right: 8px;
    }
    @media (max-width: 991.98px) {
      .sidebar {
        min-height: auto;
        position: fixed;
        z-index: 1040;
        left: -220px;
        width: 220px;
        transition: left 0.3s;
      }
      .sidebar.show {
        left: 0;
      }
      #main-content {
        margin-left: 0 !important;
      }
      #sidebarClose {
        display: none;
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 1051;
      }
      .sidebar.show #sidebarClose {
        display: block;
      }
    }
    @media (min-width: 992px) {
      #main-content {
        margin-left: 220px;
      }
      #sidebarClose {
        display: none !important;
      }
    }
    .navbar {
      z-index: 1050;
    }
    /* Fix pagination overflow */
    .pagination {
      flex-wrap: wrap;
      justify-content: center;
    }
    .table-responsive {
      overflow-x: auto;
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <nav class="sidebar d-flex flex-column position-fixed p-3" id="sidebarMenu">
    <button class="btn btn-outline-light d-lg-none mb-3 align-self-end" id="sidebarClose" style="display:none;"><i class="fas fa-times"></i></button>
    <a class="navbar-brand mb-4" href="admin-dashboard.php"><i class="fas fa-parking"></i> EasyPark</a>
    <ul class="nav flex-column mb-auto">
      <li class="nav-item">
        <a class="nav-link<?= !$showParkingSlots && !isset($_GET['users']) ? ' active' : '' ?>" href="#"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
      </li>
      <li class="nav-item">
        <a class="nav-link<?= $showParkingSlots ? ' active' : '' ?>" href="#"><i class="fas fa-car"></i> Parking Slots</a>
      </li>
      <li class="nav-item">
        <a class="nav-link<?= isset($_GET['users']) ? ' active' : '' ?>" href="?users=1"><i class="fas fa-users"></i> Users</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#"><i class="fas fa-exchange-alt"></i> Transactions</a>
      </li>
    </ul>
    <hr class="bg-secondary">
    <!-- Removed sidebar user dropdown for Admin -->
  </nav>

  <!-- Main Content -->
  <div id="main-content">
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
      <button class="btn btn-outline-secondary d-lg-none mr-2" id="sidebarToggle"><i class="fas fa-bars"></i></button>
      <a class="navbar-brand d-lg-none" href="admin-dashboard.php">EasyPark</a>
      <div class="collapse navbar-collapse">
        <ul class="navbar-nav mr-auto">
        </ul>
        <ul class="navbar-nav ml-auto">
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="notifDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <i class="fas fa-bell"></i> <span class="badge badge-danger">3</span>
            </a>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="notifDropdown">
              <a class="dropdown-item" href="#"><i class="fas fa-info-circle text-primary"></i> New user registered</a>
              <a class="dropdown-item" href="#"><i class="fas fa-car text-success"></i> Slot #12 reserved</a>
              <a class="dropdown-item" href="#"><i class="fas fa-exclamation-triangle text-warning"></i> Payment pending</a>
            </div>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <img src="https://ui-avatars.com/api/?name=Admin" alt="admin" width="30" height="30" class="rounded-circle"> Admin <?php echo $_SESSION['email']; ?>
            </a>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
              <a class="dropdown-item" href="#"><i class="fas fa-user"></i> Profile</a>
              <a class="dropdown-item" href="#"><i class="fas fa-cog"></i> Settings</a>
              <div class="dropdown-divider"></div>
              <a class="dropdown-item text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
          </li>
        </ul>
      </div>
    </nav>

    <div class="container-fluid py-4">
      <div id="dashboard-cards" style="<?= $showParkingSlots || isset($_GET['users']) ? 'display:none;' : '' ?>">
        <div class="row">
          <div class="col-md-3 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
              <div class="card-body">
                <div class="row no-gutters align-items-center">
                  <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Parking Slots</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalSlots; ?></div>
                  </div>
                  <div class="col-auto">
                    <i class="fas fa-parking fa-2x text-gray-300"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-3 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
              <div class="card-body">
                <div class="row no-gutters align-items-center">
                  <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Available Slots</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $availableSlots; ?></div>
                  </div>
                  <div class="col-auto">
                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-3 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
              <div class="card-body">
                <div class="row no-gutters align-items-center">
                  <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Reserved Slots</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $reservedSlots; ?></div>
                  </div>
                  <div class="col-auto">
                    <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-3 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
              <div class="card-body">
                <div class="row no-gutters align-items-center">
                  <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Occupied Slots</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $occupiedSlots; ?></div>
                  </div>
                  <div class="col-auto">
                    <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div id="parking-slots-container" style="<?= $showParkingSlots && !isset($_GET['users']) ? '' : 'display:none;' ?>">
        <div class="card mb-4">
          <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <span><i class="fas fa-car"></i> Parking Slots</span>
          </div>
          <div class="card-body">
            <form class="form-inline mb-3">
              <label class="mr-2">Status:</label>
              <select name="status" class="form-control mr-3" onchange="this.form.submit()">
                <option value="">All</option>
                <option value="available"<?= isset($_GET['status']) && $_GET['status']==='available'?' selected':'' ?>>Available</option>
                <option value="reserved"<?= isset($_GET['status']) && $_GET['status']==='reserved'?' selected':'' ?>>Reserved</option>
                <option value="occupied"<?= isset($_GET['status']) && $_GET['status']==='occupied'?' selected':'' ?>>Occupied</option>
              </select>
              <label class="mr-2">Type:</label>
              <select name="type" class="form-control mr-3" onchange="this.form.submit()">
                <option value="">All</option>
                <option value="two_wheeler"<?= isset($_GET['type']) && $_GET['type']==='two_wheeler'?' selected':'' ?>>Two Wheeler</option>
                <option value="standard"<?= isset($_GET['type']) && $_GET['type']==='standard'?' selected':'' ?>>Standard</option>
                <option value="compact"<?= isset($_GET['type']) && $_GET['type']==='compact'?' selected':'' ?>>Compact</option>
              </select>
            </form>
            <div class="table-responsive">
              <?php include __DIR__ . '/admin-dashboard.php-table.php'; ?>
            </div>
          </div>
        </div>
      </div>
      <?php
      // Pagination logic for Users
      $usersPerPage = 10; // Number of users per page
      $currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
      $offset = ($currentPage - 1) * $usersPerPage;

      $totalUsers = $pdo->query("SELECT COUNT(*) as total FROM users")->fetch(PDO::FETCH_ASSOC)['total'];
      $totalPages = ceil($totalUsers / $usersPerPage);

      $users = [];
      try {
        $stmt = $pdo->prepare("SELECT * FROM users ORDER BY user_id ASC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $usersPerPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
      } catch (Exception $e) {
        echo '<div class="alert alert-danger mb-0">Users table not found in database.</div>';
      }
      ?>
      <?php
      // Check if the logged-in user is the super admin
      $loggedInUserEmail = $_SESSION['email']; // Assuming email is stored in session
      $isSuperAdmin = $loggedInUserEmail === 'admin@gmail.com';
      ?>
      <div id="users-container" style="<?= isset($_GET['users']) ? '' : 'display:none;' ?>">
        <div class="card mb-4 shadow">
          <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
            <span><i class="fas fa-users"></i> User Management</span>
            <button class="btn btn-light btn-sm" onclick="showAddUserModal()" <?= $isSuperAdmin ? '' : 'disabled' ?>>
              <i class="fas fa-plus"></i> Add User
            </button>
          </div>
          <div class="card-body">
            <?php if ($users && count($users) > 0): ?>
            <div class="table-responsive">
              <table class="table table-striped table-bordered table-hover align-middle w-100">
                <thead class="thead-dark">
                  <tr>
                    <th scope="col">#</th>
                    <?php foreach(array_keys($users[0]) as $col): ?>
                      <?php if (!in_array($col, ['password', 'security_code'])): // Hide sensitive columns ?>
                        <th scope="col"><?= htmlspecialchars(ucwords(str_replace('_',' ',$col))) ?></th>
                      <?php endif; ?>
                    <?php endforeach; ?>
                    <th scope="col">User Type</th>
                    <th scope="col">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $rownum = $offset + 1; foreach ($users as $user): ?>
                    <tr>
                      <th scope="row"><?= $rownum++ ?></th>
                      <?php foreach($user as $key => $val): ?>
                        <?php if (!in_array($key, ['password', 'security_code'])): ?>
                          <td><?= htmlspecialchars($val) ?></td>
                        <?php endif; ?>
                      <?php endforeach; ?>
                      <td>
                        <?php if ($user['user_type'] === 'admin' && $user['email'] === 'admin@gmail.com'): ?>
                          <span class="badge badge-danger">Super Admin</span>
                        <?php elseif ($user['user_type'] === 'admin'): ?>
                          <span class="badge badge-warning">Admin</span>
                        <?php elseif ($user['user_type'] === 'staff'): ?>
                          <span class="badge badge-info">Staff</span>
                        <?php else: ?>
                          <span class="badge badge-secondary">Client</span>
                        <?php endif; ?>
                      </td>
                      <td class="text-center">
                        <?php if ($isSuperAdmin || $user['user_type'] !== 'admin'): ?>
                          <button class="btn btn-sm btn-primary" onclick="editUser(<?= htmlspecialchars(json_encode($user)) ?>)">
                            <i class="fas fa-edit"></i>
                          </button>
                          <button class="btn btn-sm btn-danger" onclick="deleteUser(<?= htmlspecialchars($user['user_id']) ?>)">
                            <i class="fas fa-trash"></i>
                          </button>
                        <?php endif; ?>
                        <?php if ($user['user_type'] === 'user'): ?>
                          <button class="btn btn-sm btn-warning" onclick="suspendUser(<?= htmlspecialchars($user['user_id']) ?>)">
                            <i class="fas fa-ban"></i> Suspend
                          </button>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <nav>
              <ul class="pagination justify-content-center">
                <li class="page-item<?= $currentPage == 1 ? ' disabled' : '' ?>">
                  <a class="page-link" href="?users=1&page=<?= $currentPage - 1 ?>" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                  </a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                  <li class="page-item<?= $i == $currentPage ? ' active' : '' ?>">
                    <a class="page-link" href="?users=1&page=<?= $i ?>"><?= $i ?></a>
                  </li>
                <?php endfor; ?>
                <li class="page-item<?= $currentPage == $totalPages ? ' disabled' : '' ?>">
                  <a class="page-link" href="?users=1&page=<?= $currentPage + 1 ?>" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                  </a>
                </li>
              </ul>
            </nav>
            <?php else: ?>
              <div class="text-muted">No users found.</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Add User Modal -->
  <div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form id="addUserForm">
          <div class="modal-body">
            <div class="form-group">
              <label>First Name</label>
              <input type="text" class="form-control" name="first_name" required>
            </div>
            <div class="form-group">
              <label>Last Name</label>
              <input type="text" class="form-control" name="last_name" required>
            </div>
            <div class="form-group">
              <label>Email</label>
              <input type="email" class="form-control" name="email" required>
            </div>
            <div class="form-group">
              <label>Password</label>
              <input type="password" class="form-control" name="password" required>
            </div>
            <div class="form-group">
              <label>User Type</label>
              <select class="form-control" name="user_type" required>
                <option value="staff">Staff</option>
                <?php if ($isSuperAdmin): ?>
                  <option value="admin">Admin</option>
                <?php endif; ?>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Add User</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit User Modal -->
  <div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form id="editUserForm">
          <div class="modal-body">
            <input type="hidden" name="user_id" id="edit_user_id">
            <div class="form-group">
              <label>First Name</label>
              <input type="text" class="form-control" name="first_name" id="edit_first_name" required>
            </div>
            <div class="form-group">
              <label>Last Name</label>
              <input type="text" class="form-control" name="last_name" id="edit_last_name" required>
            </div>
            <div class="form-group">
              <label>Email</label>
              <input type="email" class="form-control" name="email" id="edit_email" required>
            </div>
            <div class="form-group">
              <label>Password (leave blank to keep current)</label>
              <input type="password" class="form-control" name="password" id="edit_password">
            </div>
            <div class="form-group">
              <label>User Type</label>
              <select class="form-control" name="user_type" id="edit_user_type" required>
                <option value="user">User</option>
                <option value="staff">Staff</option>
                <option value="admin">Admin</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Delete User Modal -->
  <div class="modal fade" id="deleteUserModal" tabindex="-1" role="dialog" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteUserModalLabel">Delete User</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          Are you sure you want to delete this user? This action cannot be undone.
          <input type="hidden" id="delete_user_id">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-danger" onclick="confirmDeleteUser()">Delete</button>
        </div>
      </div>
    </div>
  </div>

  <script src="../js/jquery.slim.min.js"></script>
  <script src="../js/bootstrap.bundle.min.js"></script>
  <script>
    // Sidebar toggle for mobile
    document.getElementById('sidebarToggle').addEventListener('click', function() {
      var sidebar = document.getElementById('sidebarMenu');
      sidebar.classList.toggle('show');
      document.getElementById('sidebarClose').style.display = sidebar.classList.contains('show') ? '' : 'none';
    });
    // Sidebar close button for mobile
    document.getElementById('sidebarClose').addEventListener('click', function() {
      var sidebar = document.getElementById('sidebarMenu');
      sidebar.classList.remove('show');
      this.style.display = 'none';
    });
    // Sidebar highlighting and section toggle
    document.querySelectorAll('.sidebar .nav-link').forEach(function(link) {
      link.addEventListener('click', function(e) {
        var text = this.textContent.trim();
        // Remove 'active' from all
        document.querySelectorAll('.sidebar .nav-link').forEach(function(l) {
          l.classList.remove('active');
        });
        // Add 'active' to clicked
        this.classList.add('active');
        if (text.includes('Parking Slots')) {
          e.preventDefault();
          document.getElementById('dashboard-cards').style.display = 'none';
          document.getElementById('parking-slots-container').style.display = 'block';
          document.getElementById('users-container').style.display = 'none';
        } else if (text.includes('Dashboard')) {
          e.preventDefault();
          document.getElementById('dashboard-cards').style.display = 'block';
          document.getElementById('parking-slots-container').style.display = 'none';
          document.getElementById('users-container').style.display = 'none';
        } else if (text.includes('Users')) {
          e.preventDefault();
          document.getElementById('dashboard-cards').style.display = 'none';
          document.getElementById('parking-slots-container').style.display = 'none';
          document.getElementById('users-container').style.display = 'block';
        }
      });
    });

    // User Management Functions
    function showAddUserModal() {
      $('#addUserModal').modal('show');
    }

    function editUser(userData) {
      document.getElementById('edit_user_id').value = userData.user_id;
      document.getElementById('edit_first_name').value = userData.first_name;
      document.getElementById('edit_last_name').value = userData.last_name;
      document.getElementById('edit_email').value = userData.email;
      document.getElementById('edit_user_type').value = userData.user_type;
      document.getElementById('edit_password').value = '';
      $('#editUserModal').modal('show');
    }

    function deleteUser(userId) {
      document.getElementById('delete_user_id').value = userId;
      $('#deleteUserModal').modal('show');
    }

    // Form Submissions
    document.getElementById('addUserForm').addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      
      fetch('add_user.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          $('#addUserModal').modal('hide');
          window.location.reload();
        } else {
          alert(data.message || 'Error adding user');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Error adding user');
      });
    });

    document.getElementById('editUserForm').addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      
      fetch('update_user.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          $('#editUserModal').modal('hide');
          window.location.reload();
        } else {
          alert(data.message || 'Error updating user');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Error updating user');
      });
    });

    function confirmDeleteUser() {
      const userId = document.getElementById('delete_user_id').value;
      
      fetch('delete_user.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ user_id: userId })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          $('#deleteUserModal').modal('hide');
          window.location.reload();
        } else {
          alert(data.message || 'Error deleting user');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Error deleting user');
      });
    }

    // Suspend User Function
    function suspendUser(userId) {
      if (confirm('Are you sure you want to suspend this user?')) {
        fetch('suspend_user.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ user_id: userId })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('User suspended successfully.');
            window.location.reload();
          } else {
            alert(data.message || 'Error suspending user.');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error suspending user.');
        });
      }
    }
  </script>
</body>
</html>
