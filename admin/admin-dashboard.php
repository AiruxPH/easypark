<?php

session_start();
//check
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
        <a class="nav-link<?= !$showParkingSlots && !isset($_GET['users']) && !isset($_GET['transactions']) && !isset($_GET['vehicles']) ? ' active' : '' ?>" href="admin-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
      </li>
      <li class="nav-item">
        <a class="nav-link<?= $showParkingSlots ? ' active' : '' ?>" href="admin-dashboard.php?page=1"><i class="fas fa-car"></i> Parking Slots</a>
      </li>
      <li class="nav-item">
        <a class="nav-link<?= isset($_GET['users']) ? ' active' : '' ?>" href="admin-dashboard.php?users=1"><i class="fas fa-users"></i> Users</a>
      </li>
      <li class="nav-item">
        <a class="nav-link<?= isset($_GET['transactions']) ? ' active' : '' ?>" href="admin-dashboard.php?transactions=1"><i class="fas fa-exchange-alt"></i> Transactions</a>
      </li>
      <li class="nav-item">
        <a class="nav-link<?= isset($_GET['vehicles']) ? ' active' : '' ?>" href="admin-dashboard.php?vehicles=1"><i class="fas fa-car-side"></i> Vehicles</a>
      </li>
      <li class="nav-item">
        <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
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
          </li>
          <li class="nav-item dropdown">
          </li>
        </ul>
      </div>
    </nav>
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
              <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover align-middle w-100" id="usersTable">
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
                    <?php if ($users && count($users) > 0): ?>
                      <?php $i = 1 + $offset; foreach ($users as $user):
                        $isSelf = ($user['email'] === $loggedInUserEmail);
                        $userRole = strtolower($user['user_type']);
                        $canEdit = false;
                        if ($isSuperAdmin) {
                          // Super Admin can edit anyone except themselves
                          $canEdit = !$isSelf;
                        } else if ($_SESSION['user_type'] === 'admin') {
                          // Admin can edit staff and clients, but not themselves or other admins/super admin
                          $canEdit = !$isSelf && ($userRole === 'staff' || $userRole === 'client');
                        }
                      ?>
                        <tr>
                          <td class="text-center"><?= $i++ ?></td>
                          <td><?= htmlspecialchars($user['user_id']) ?></td>
                          <td><?= htmlspecialchars($user['first_name']) ?></td>
                          <td><?= htmlspecialchars($user['middle_name']) ?></td>
                          <td><?= htmlspecialchars($user['last_name']) ?></td>
                          <td><?= htmlspecialchars($user['email']) ?></td>
                          <td><?= htmlspecialchars(ucfirst($user['user_type'])) ?></td>
                          <td class="text-center">
                            <button class="btn btn-sm btn-info" onclick='editUser(<?= json_encode($user) ?>)' <?= $canEdit ? '' : 'disabled title="You cannot edit this user"' ?>><i class="fas fa-edit"></i></button>
                            <?php if ($isSuperAdmin || ($userRole !== 'admin' && !$isSelf)): ?>
                              <button class="btn btn-sm btn-danger" onclick='deleteUser(<?= json_encode($user['user_id']) ?>)'><i class="fas fa-trash"></i></button>
                              <button class="btn btn-sm btn-warning" onclick='suspendUser(<?= json_encode($user['user_id']) ?>)'><i class="fas fa-user-slash"></i></button>
                            <?php endif; ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr><td colspan="8" class="text-center text-muted">No users found.</td></tr>
                    <?php endif; ?>
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
        <div id="transactions-container" style="<?= isset($_GET['transactions']) ? '' : 'display:none;' ?>">
          <div class="card mb-4 shadow">
            <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
              <span><i class="fas fa-exchange-alt"></i> Transactions</span>
            </div>
            <div class="card-body">
              <form class="mb-3" id="transactionsSearchForm">
                <div class="row align-items-end">
                  <div class="col-md-3">
                    <label>Search</label>
                    <input type="text" class="form-control" id="transactionsSearchInput" placeholder="Search by user, slot, plate, ref#...">
                  </div>
                  <div class="col-md-2">
                    <label>Status</label>
                    <select class="form-control" id="transactionsStatusFilter">
                      <option value="">All</option>
                      <option value="pending">Pending</option>
                      <option value="confirmed">Confirmed</option>
                      <option value="ongoing">Ongoing</option>
                      <option value="completed">Completed</option>
                      <option value="cancelled">Cancelled</option>
                      <option value="expired">Expired</option>
                      <option value="void">Void</option>
                    </select>
                  </div>
                  <div class="col-md-2">
                    <label>Payment Status</label>
                    <select class="form-control" id="transactionsPaymentStatusFilter">
                      <option value="">All</option>
                      <option value="pending">Pending</option>
                      <option value="successful">Successful</option>
                      <option value="failed">Failed</option>
                      <option value="refunded">Refunded</option>
                    </select>
                  </div>
                  <div class="col-md-2">
                    <label>Payment Method</label>
                    <select class="form-control" id="transactionsPaymentMethodFilter">
                      <option value="">All</option>
                      <option value="cash">Cash</option>
                      <option value="gcash">GCash</option>
                      <option value="card">Card</option>
                    </select>
                  </div>
                  <div class="col-md-2">
                    <label>Sort By</label>
                    <select class="form-control" id="transactionsSortBy">
                      <option value="reservation_id">Ref #</option>
                      <option value="user_name">User</option>
                      <option value="slot_number">Slot</option>
                      <option value="status">Status</option>
                      <option value="amount">Amount</option>
                      <option value="payment_status">Payment Status</option>
                      <option value="payment_date">Payment Date</option>
                    </select>
                  </div>
                  <div class="col-md-1">
                    <label>Order</label>
                    <select class="form-control" id="transactionsSortOrder">
                      <option value="DESC">↓</option>
                      <option value="ASC">↑</option>
                    </select>
                  </div>
                </div>
              </form>
              <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover align-middle w-100" id="transactionsTable">
                  <thead class="thead-dark">
                    <tr>
                      <th>Ref #</th>
                      <th>User</th>
                      <th>Slot</th>
                      <th>Vehicle</th>
                      <th>Status</th>
                      <th>Amount</th>
                      <th>Payment Status</th>
                      <th>Payment Method</th>
                      <th>Payment Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    // Fetch transactions (reservations + payments + user info)
                    $sql = "SELECT r.reservation_id, CONCAT(u.first_name, ' ', u.last_name) AS user_name, s.slot_number, s.slot_type, v.plate_number, m.brand, m.model, r.status, p.amount, p.status AS payment_status, p.method, p.payment_date
                      FROM reservations r
                      JOIN users u ON r.user_id = u.user_id
                      JOIN parking_slots s ON r.parking_slot_id = s.parking_slot_id
                      JOIN vehicles v ON r.vehicle_id = v.vehicle_id
                      JOIN Vehicle_Models m ON v.model_id = m.model_id
                      LEFT JOIN payments p ON r.reservation_id = p.reservation_id
                      ORDER BY r.reservation_id DESC LIMIT 200";
                    $stmt = $pdo->query($sql);
                    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($transactions as $t): ?>
                      <tr data-tx='<?= htmlspecialchars(json_encode($t)) ?>'>
                        <td><?= htmlspecialchars($t['reservation_id']) ?></td>
                        <td><?= htmlspecialchars($t['user_name']) ?></td>
                        <td><?= htmlspecialchars($t['slot_number']) ?> (<?= htmlspecialchars($t['slot_type']) ?>)</td>
                        <td><?= htmlspecialchars($t['brand'].' '.$t['model'].' - '.$t['plate_number']) ?></td>
                        <td><span class="badge bg-secondary text-uppercase"><?= htmlspecialchars($t['status']) ?></span></td>
                        <td>₱<?= number_format($t['amount'],2) ?></td>
                        <td><span class="badge bg-secondary text-uppercase"><?= $t['payment_status'] ? htmlspecialchars($t['payment_status']) : 'N/A' ?></span></td>
                        <td><?= htmlspecialchars(ucfirst($t['method'])) ?></td>
                        <td><?= htmlspecialchars($t['payment_date']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <div id="vehicles-container" style="<?= isset($_GET['vehicles']) ? '' : 'display:none;' ?>">
          <div class="card mb-4 shadow">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
              <span><i class="fas fa-car-side"></i> Vehicles</span>
            </div>
            <div class="card-body">
              <form class="mb-3" id="vehiclesSearchForm">
                <div class="row align-items-end">
                  <div class="col-md-3">
                    <label>Search</label>
                    <input type="text" class="form-control" id="vehiclesSearchInput" placeholder="Search by owner, plate, brand, model...">
                  </div>
                  <div class="col-md-2">
                    <label>Type</label>
                    <select class="form-control" id="vehiclesTypeFilter">
                      <option value="">All</option>
                      <option value="two_wheeler">Two Wheeler</option>
                      <option value="standard">Standard</option>
                      <option value="compact">Compact</option>
                    </select>
                  </div>
                  <div class="col-md-2">
                    <label>Sort By</label>
                    <select class="form-control" id="vehiclesSortBy">
                      <option value="plate_number">Plate #</option>
                      <option value="owner_name">Owner</option>
                      <option value="brand">Brand</option>
                      <option value="model">Model</option>
                      <option value="type">Type</option>
                    </select>
                  </div>
                  <div class="col-md-1">
                    <label>Order</label>
                    <select class="form-control" id="vehiclesSortOrder">
                      <option value="ASC">↑</option>
                      <option value="DESC">↓</option>
                    </select>
                  </div>
                </div>
              </form>
              <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover align-middle w-100" id="vehiclesTable">
                  <thead class="thead-dark">
                    <tr>
                      <th>Plate #</th>
                      <th>Owner</th>
                      <th>Brand</th>
                      <th>Model</th>
                      <th>Type</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $sql = "SELECT v.plate_number, CONCAT(u.first_name, ' ', u.last_name) AS owner_name, m.brand, m.model, v.type
                      FROM vehicles v
                      JOIN users u ON v.user_id = u.user_id
                      LEFT JOIN Vehicle_Models m ON v.model_id = m.model_id
                      ORDER BY v.plate_number ASC LIMIT 200";
                    $stmt = $pdo->query($sql);
                    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($vehicles as $veh): ?>
                      <tr data-veh='<?= htmlspecialchars(json_encode($veh)) ?>'>
                        <td><?= htmlspecialchars($veh['plate_number']) ?></td>
                        <td><?= htmlspecialchars($veh['owner_name']) ?></td>
                        <td><?= htmlspecialchars($veh['brand']) ?></td>
                        <td><?= htmlspecialchars($veh['model']) ?></td>
                        <td><?= htmlspecialchars(ucfirst(str_replace('_',' ',$veh['type']))) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src="../js/bootstrap.bundle.min.js"></script>
  <script src="../js/jquery.min.js"></script>
  <script src="../js/ef9baa832e.js" crossorigin="anonymous"></script>
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
          document.getElementById('transactions-container').style.display = 'none';
          document.getElementById('vehicles-container').style.display = 'none';
        } else if (text.includes('Dashboard')) {
          e.preventDefault();
          document.getElementById('dashboard-cards').style.display = 'block';
          document.getElementById('parking-slots-container').style.display = 'none';
          document.getElementById('users-container').style.display = 'none';
          document.getElementById('transactions-container').style.display = 'none';
          document.getElementById('vehicles-container').style.display = 'none';
        } else if (text.includes('Users')) {
          e.preventDefault();
          document.getElementById('dashboard-cards').style.display = 'none';
          document.getElementById('parking-slots-container').style.display = 'none';
          document.getElementById('users-container').style.display = 'block';
          document.getElementById('transactions-container').style.display = 'none';
          document.getElementById('vehicles-container').style.display = 'none';
        } else if (text.includes('Transactions')) {
          e.preventDefault();
          document.getElementById('dashboard-cards').style.display = 'none';
          document.getElementById('parking-slots-container').style.display = 'none';
          document.getElementById('users-container').style.display = 'none';
          document.getElementById('transactions-container').style.display = 'block';
          document.getElementById('vehicles-container').style.display = 'none';
        } else if (text.includes('Vehicles')) {
          e.preventDefault();
          document.getElementById('dashboard-cards').style.display = 'none';
          document.getElementById('parking-slots-container').style.display = 'none';
          document.getElementById('users-container').style.display = 'none';
          document.getElementById('transactions-container').style.display = 'none';
          document.getElementById('vehicles-container').style.display = 'block';
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

    // Transactions search, filter, sort
    function normalizeTxText(text) {
      return (text || '').toString().toLowerCase().trim();
    }
    function filterAndSortTransactions() {
      const search = normalizeTxText(document.getElementById('transactionsSearchInput').value);
      const status = document.getElementById('transactionsStatusFilter').value;
      const payStatus = document.getElementById('transactionsPaymentStatusFilter').value;
      const payMethod = document.getElementById('transactionsPaymentMethodFilter').value;
      const sortBy = document.getElementById('transactionsSortBy').value;
      const sortOrder = document.getElementById('transactionsSortOrder').value;
      const rows = Array.from(document.querySelectorAll('#transactionsTable tbody tr'));
      rows.forEach(row => {
        const tx = JSON.parse(row.getAttribute('data-tx'));
        let show = true;
        if (status && tx.status !== status) show = false;
        if (payStatus && tx.payment_status !== payStatus) show = false;
        if (payMethod && tx.method !== payMethod) show = false;
        if (search) {
          const values = [tx.reservation_id, tx.user_name, tx.slot_number, tx.slot_type, tx.plate_number, tx.brand, tx.model].map(normalizeTxText);
          if (!values.some(v => v.includes(search))) show = false;
        }
        row.style.display = show ? '' : 'none';
      });
      // Sorting
      rows.sort((a, b) => {
        const txA = JSON.parse(a.getAttribute('data-tx'));
        const txB = JSON.parse(b.getAttribute('data-tx'));
        let valA = txA[sortBy] || '';
        let valB = txB[sortBy] || '';
        if (sortBy === 'amount') {
          valA = parseFloat(valA) || 0;
          valB = parseFloat(valB) || 0;
        } else {
          valA = normalizeTxText(valA);
          valB = normalizeTxText(valB);
        }
        if (valA < valB) return sortOrder === 'ASC' ? -1 : 1;
        if (valA > valB) return sortOrder === 'ASC' ? 1 : -1;
        return 0;
      });
      const tbody = document.querySelector('#transactionsTable tbody');
      rows.forEach(row => tbody.appendChild(row));
    }
    document.getElementById('transactionsSearchInput').addEventListener('input', filterAndSortTransactions);
    document.getElementById('transactionsStatusFilter').addEventListener('change', filterAndSortTransactions);
    document.getElementById('transactionsPaymentStatusFilter').addEventListener('change', filterAndSortTransactions);
    document.getElementById('transactionsPaymentMethodFilter').addEventListener('change', filterAndSortTransactions);
    document.getElementById('transactionsSortBy').addEventListener('change', filterAndSortTransactions);
    document.getElementById('transactionsSortOrder').addEventListener('change', filterAndSortTransactions);

    // Vehicles search, filter, sort
    function normalizeVehText(text) {
      return (text || '').toString().toLowerCase().trim();
    }
    function filterAndSortVehicles() {
      const search = normalizeVehText(document.getElementById('vehiclesSearchInput').value);
      const type = document.getElementById('vehiclesTypeFilter').value;
      const sortBy = document.getElementById('vehiclesSortBy').value;
      const sortOrder = document.getElementById('vehiclesSortOrder').value;
      const rows = Array.from(document.querySelectorAll('#vehiclesTable tbody tr'));
      rows.forEach(row => {
        const veh = JSON.parse(row.getAttribute('data-veh'));
        let show = true;
        if (type && veh.type !== type) show = false;
        if (search) {
          const values = [veh.plate_number, veh.owner_name, veh.brand, veh.model, veh.type].map(normalizeVehText);
          if (!values.some(v => v.includes(search))) show = false;
        }
        row.style.display = show ? '' : 'none';
      });
      // Sorting
      rows.sort((a, b) => {
        const vehA = JSON.parse(a.getAttribute('data-veh'));
        const vehB = JSON.parse(b.getAttribute('data-veh'));
        let valA = vehA[sortBy] || '';
        let valB = vehB[sortBy] || '';
        valA = normalizeVehText(valA);
        valB = normalizeVehText(valB);
        if (valA < valB) return sortOrder === 'ASC' ? -1 : 1;
        if (valA > valB) return sortOrder === 'ASC' ? 1 : -1;
        return 0;
      });
      const tbody = document.querySelector('#vehiclesTable tbody');
      rows.forEach(row => tbody.appendChild(row));
    }
    document.getElementById('vehiclesSearchInput').addEventListener('input', filterAndSortVehicles);
    document.getElementById('vehiclesTypeFilter').addEventListener('change', filterAndSortVehicles);
    document.getElementById('vehiclesSortBy').addEventListener('change', filterAndSortVehicles);
    document.getElementById('vehiclesSortOrder').addEventListener('change', filterAndSortVehicles);
  </script>
</body>
</html>
