<?php

session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_email'])) {
    header('Location: ../login.php');
    exit;
} else if ($_SESSION['user_type'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}
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
    .header-bar {
      background: rgba(0,0,0,0.7);
      color: #ffc107;
      padding: 1.5rem 2rem 1rem 2rem;
      border-radius: 0 0 1rem 1rem;
      box-shadow: 0 4px 16px rgba(0,0,0,0.2);
      margin-bottom: 2rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .header-bar h2 {
      margin: 0;
      font-weight: 700;
      letter-spacing: 1px;
      font-size: 2.2rem;
    }
    .header-bar .fa {
      margin-right: 10px;
    }
    .section-card {
      background: rgba(255,255,255,0.97);
      border-radius: 1rem;
      box-shadow: 0 2px 12px rgba(0,0,0,0.12);
      margin-bottom: 2rem;
      padding: 2rem 1.5rem 1.5rem 1.5rem;
    }
    .table {
      background: #fff;
      border-radius: 0.5rem;
      overflow: hidden;
    }
    .table thead {
      background: #343a40;
      color: #ffc107;
    }
    .table-hover tbody tr:hover {
      background: #ffe082;
    }
    .card.bg-dark {
      background: linear-gradient(135deg, #232526 0%, #414345 100%);
      border-radius: 1rem;
      box-shadow: 0 2px 12px rgba(0,0,0,0.18);
    }
    .card-title, .card-text {
      color: #ffc107;
    }
    .btn-warning, .btn-success, .btn-danger, .btn-secondary {
      font-weight: 600;
      letter-spacing: 0.5px;
    }
    input.form-control, select.form-control {
      border-radius: 0.5rem;
    }
    ::-webkit-scrollbar {
      width: 8px;
      background: #eee;
    }
    ::-webkit-scrollbar-thumb {
      background: #bbb;
      border-radius: 4px;
    }
    @media (max-width: 991.98px) {
      .header-bar { flex-direction: column; align-items: flex-start; padding: 1rem; }
      .section-card { padding: 1rem; }
    }
  </style>
</head>
<body>
<div class="bg-overlay">
  <div class="header-bar mb-4">
    <h2><i class="fa fa-user-cog"></i> Admin Dashboard</h2>
    <a href="../logout.php" class="btn btn-secondary"><i class="fa fa-sign-out"></i> Logout</a>
  </div>
  <div class="container-fluid py-4">
    <div class="section-card">
      <!-- ...existing dashboard cards/statistics... -->
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

      // Search, filter and sorting parameters
      $search = isset($_GET['search']) ? trim($_GET['search']) : '';
      $searchBy = isset($_GET['searchBy']) ? $_GET['searchBy'] : 'all';
      $filterType = isset($_GET['filterType']) ? $_GET['filterType'] : 'all';
      $sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : 'user_id';
      $sortOrder = isset($_GET['sortOrder']) ? $_GET['sortOrder'] : 'ASC';
      
      // Build the WHERE clause for search
      $whereClause = [];
      $params = [];
      
      if ($search !== '') {
          switch($searchBy) {
              case 'user_id':
                  $whereClause[] = "user_id = :search";
                  $params[':search'] = $search;
                  break;
              case 'first_name':
                  $whereClause[] = "first_name LIKE :search";
                  $params[':search'] = "%$search%";
                  break;
              case 'middle_name':
                  $whereClause[] = "middle_name LIKE :search";
                  $params[':search'] = "%$search%";
                  break;
              case 'last_name':
                  $whereClause[] = "last_name LIKE :search";
                  $params[':search'] = "%$search%";
                  break;
              case 'email':
                  $whereClause[] = "email LIKE :search";
                  $params[':search'] = "%$search%";
                  break;
              case 'all':
                  $whereClause[] = "(first_name LIKE :search OR middle_name LIKE :search OR last_name LIKE :search OR email LIKE :search OR user_id = :search_id)";
                  $params[':search'] = "%$search%";
                  $params[':search_id'] = $search;
                  break;
          }
      }
      
      // Add user type filter
      if ($filterType !== 'all') {
          $whereClause[] = "user_type = :user_type";
          $params[':user_type'] = $filterType;
      }
      
      $whereSQL = !empty($whereClause) ? 'WHERE ' . implode(' AND ', $whereClause) : '';
      
      // Count total filtered users
      $countSQL = "SELECT COUNT(*) as total FROM users $whereSQL";
      $stmt = $pdo->prepare($countSQL);
      $stmt->execute($params);
      $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
      $totalPages = ceil($totalUsers / $usersPerPage);

      // Fetch filtered and sorted users
      $sql = "SELECT * FROM users $whereSQL ORDER BY $sortBy $sortOrder LIMIT :limit OFFSET :offset";
      $stmt = $pdo->prepare($sql);
      foreach ($params as $key => $value) {
          $stmt->bindValue($key, $value);
      }
      $stmt->bindValue(':limit', $usersPerPage, PDO::PARAM_INT);
      $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
      $stmt->execute();
      $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            <!-- Search and Filter Form -->
            <form class="mb-4" method="GET">
              <input type="hidden" name="users" value="1">
              <div class="row align-items-end">
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Search</label>
                    <input type="text" class="form-control search-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search users...">
                  </div>
                </div>
                <div class="col-md-2">
                  <div class="form-group">
                    <label>Search By</label>
                    <select class="form-control search-control" name="searchBy">
                      <option value="all" <?= $searchBy === 'all' ? 'selected' : '' ?>>All Fields</option>
                      <option value="user_id" <?= $searchBy === 'user_id' ? 'selected' : '' ?>>User ID</option>
                      <option value="first_name" <?= $searchBy === 'first_name' ? 'selected' : '' ?>>First Name</option>
                      <option value="middle_name" <?= $searchBy === 'middle_name' ? 'selected' : '' ?>>Middle Name</option>
                      <option value="last_name" <?= $searchBy === 'last_name' ? 'selected' : '' ?>>Last Name</option>
                      <option value="email" <?= $searchBy === 'email' ? 'selected' : '' ?>>Email</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-2">
                  <div class="form-group">
                    <label>Filter Type</label>
                    <select class="form-control search-control" name="filterType">
                      <option value="all" <?= $filterType === 'all' ? 'selected' : '' ?>>All Types</option>
                      <option value="admin" <?= $filterType === 'admin' ? 'selected' : '' ?>>Admin</option>
                      <option value="staff" <?= $filterType === 'staff' ? 'selected' : '' ?>>Staff</option>
                      <option value="client" <?= $filterType === 'client' ? 'selected' : '' ?>>Client</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-2">
                  <div class="form-group">
                    <label>Sort By</label>
                    <select class="form-control search-control" name="sortBy">
                      <option value="user_id" <?= $sortBy === 'user_id' ? 'selected' : '' ?>>User ID</option>
                      <option value="first_name" <?= $sortBy === 'first_name' ? 'selected' : '' ?>>First Name</option>
                      <option value="last_name" <?= $sortBy === 'last_name' ? 'selected' : '' ?>>Last Name</option>
                      <option value="email" <?= $sortBy === 'email' ? 'selected' : '' ?>>Email</option>
                      <option value="user_type" <?= $sortBy === 'user_type' ? 'selected' : '' ?>>User Type</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-1">
                  <div class="form-group">
                    <label>Order</label>
                    <select class="form-control search-control" name="sortOrder">
                      <option value="ASC" <?= $sortOrder === 'ASC' ? 'selected' : '' ?>>↑</option>
                      <option value="DESC" <?= $sortOrder === 'DESC' ? 'selected' : '' ?>>↓</option>
                    </select>
                  </div>
                </div>
              </div>
            </form>

            <?php if ($users && count($users) > 0): ?>
            <div class="table-responsive">              <table class="table table-striped table-bordered table-hover align-middle w-100" id="usersTable">
                <thead class="thead-dark">
                  <tr>
                    <th scope="col" class="text-center" style="width: 5%">#</th>
                    <th scope="col" style="width: 10%">ID</th>
                    <th scope="col" style="width: 15%">First Name</th>
                    <th scope="col" style="width: 15%">Middle Name</th>
                    <th scope="col" style="width: 15%">Last Name</th>
                    <th scope="col" style="width: 20%">Email</th>
                    <th scope="col" style="width: 10%">Role</th>
                    <th scope="col" class="text-center" style="width: 10%">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <!-- Table body will be populated by JavaScript -->
                </tbody>
              </table>
            </div>
            <nav id="usersPagination">
              <!-- Pagination will be populated by JavaScript -->
            </nav>
            <?php else: ?>
              <div class="text-muted">No users found.</div>
            <?php endif; ?>
          </div>
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
            <button type="submit" class="btn btn-primary">Add Staff/Admin</button>
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

  <script src="../js/bootstrap.bundle.min.js"></script>
  <script src="../js/jquery.min.js"></script>
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

    // Live Search and Filter Functions
    let searchTimer;
    function updateUsers(page = 1) {
      const searchInput = document.querySelector('input[name="search"]');
      const searchBy = document.querySelector('select[name="searchBy"]');
      const filterType = document.querySelector('select[name="filterType"]');
      const sortBy = document.querySelector('select[name="sortBy"]');
      const sortOrder = document.querySelector('select[name="sortOrder"]');
      
      const params = new URLSearchParams({
        search: searchInput.value,
        searchBy: searchBy.value,
        filterType: filterType.value,
        sortBy: sortBy.value,
        sortOrder: sortOrder.value,
        page: page
      });

      fetch(`get_users.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
          const tbody = document.querySelector('#usersTable tbody');
          const paginationContainer = document.querySelector('#usersPagination');
          tbody.innerHTML = data.html;
          paginationContainer.innerHTML = data.pagination;
          
          // Update total users count if you have an element for it
          if (document.getElementById('totalUsers')) {
            document.getElementById('totalUsers').textContent = data.totalUsers;
          }

          // Add event listeners to pagination links
          document.querySelectorAll('#usersPagination .page-link').forEach(link => {
            link.addEventListener('click', function(e) {
              e.preventDefault();
              updateUsers(this.dataset.page);
            });
          });
        })
        .catch(error => {
          console.error('Error:', error);
        });
    }

    // Add event listeners for live search
    document.querySelectorAll('.search-control').forEach(control => {
      control.addEventListener('change', function() {
        updateUsers(1);
      });
    });

    document.querySelector('input[name="search"]').addEventListener('input', function() {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => {
        updateUsers(1);
      }, 300); // Debounce search for 300ms
    });

    // Initial load
    updateUsers(1);

    // Rest of your existing functions...

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
