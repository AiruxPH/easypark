<?php
require_once 'db.php';
// Handle AJAX request for parking slots table only
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    include __DIR__ . '/admin-dashboard.php-table.php';
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>EasyPark Admin Dashboard</title>
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <script src="js/ef9baa832e.js" crossorigin="anonymous"></script>
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
    }
    @media (min-width: 992px) {
      #main-content {
        margin-left: 220px;
      }
    }
    .navbar {
      z-index: 1050;
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <nav class="sidebar d-flex flex-column position-fixed p-3" id="sidebarMenu">
    <a class="navbar-brand mb-4" href="#"><i class="fas fa-parking"></i> EasyPark</a>
    <ul class="nav flex-column mb-auto">
      <li class="nav-item">
        <a class="nav-link active" href="#"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#"><i class="fas fa-car"></i> Parking Slots</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#"><i class="fas fa-users"></i> Users</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#"><i class="fas fa-exchange-alt"></i> Transactions</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#"><i class="fas fa-cogs"></i> Settings</a>
      </li>
    </ul>
    <hr class="bg-secondary">
    <div class="dropdown">
      <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <img src="https://ui-avatars.com/api/?name=Admin" alt="admin" width="32" height="32" class="rounded-circle mr-2">
        <strong>Admin</strong>
      </a>
      <div class="dropdown-menu dropdown-menu-dark bg-dark text-light" aria-labelledby="dropdownUser">
        <a class="dropdown-item" href="#"><i class="fas fa-user"></i> Profile</a>
        <a class="dropdown-item" href="#"><i class="fas fa-cog"></i> Settings</a>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <div id="main-content">
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
      <button class="btn btn-outline-secondary d-lg-none mr-2" id="sidebarToggle"><i class="fas fa-bars"></i></button>
      <a class="navbar-brand d-lg-none" href="#">EasyPark</a>
      <div class="collapse navbar-collapse">
        <ul class="navbar-nav mr-auto">
          <li class="nav-item">
            <a class="nav-link" href="#">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">Reports</a>
          </li>
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
              <img src="https://ui-avatars.com/api/?name=Admin" alt="admin" width="30" height="30" class="rounded-circle"> Admin
            </a>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
              <a class="dropdown-item" href="#"><i class="fas fa-user"></i> Profile</a>
              <a class="dropdown-item" href="#"><i class="fas fa-cog"></i> Settings</a>
              <div class="dropdown-divider"></div>
              <a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
          </li>
        </ul>
      </div>
    </nav>

    <div class="container-fluid py-4">
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
      <!-- Parking Slots Table with Pagination and Categorization -->
      <div class="card mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
          <span><i class="fas fa-car"></i> Parking Slots</span>
        </div>
        <div class="card-body">
          <form class="form-inline mb-3" id="slotFilterForm">
            <label class="mr-2">Status:</label>
            <select name="status" class="form-control mr-3" id="statusFilter">
              <option value="">All</option>
              <option value="available"<?= isset($_GET['status']) && $_GET['status']==='available'?' selected':'' ?>>Available</option>
              <option value="reserved"<?= isset($_GET['status']) && $_GET['status']==='reserved'?' selected':'' ?>>Reserved</option>
              <option value="occupied"<?= isset($_GET['status']) && $_GET['status']==='occupied'?' selected':'' ?>>Occupied</option>
            </select>
            <label class="mr-2">Type:</label>
            <select name="type" class="form-control mr-3" id="typeFilter">
              <option value="">All</option>
              <option value="two_wheeler"<?= isset($_GET['type']) && $_GET['type']==='two_wheeler'?' selected':'' ?>>Two Wheeler</option>
              <option value="standard"<?= isset($_GET['type']) && $_GET['type']==='standard'?' selected':'' ?>>Standard</option>
              <option value="compact"<?= isset($_GET['type']) && $_GET['type']==='compact'?' selected':'' ?>>Compact</option>
            </select>
          </form>
          <div class="table-responsive" id="slotsTableContainer">
            <?php include __DIR__ . '/admin-dashboard.php-table.php'; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="js/jquery.slim.min.js"></script>
  <script src="js/bootstrap.bundle.min.js"></script>
  <script>
    // Sidebar toggle for mobile
    document.getElementById('sidebarToggle').addEventListener('click', function() {
      var sidebar = document.getElementById('sidebarMenu');
      sidebar.classList.toggle('show');
    });

    // AJAX filter for parking slots
    $(function() {
      $('#slotFilterForm select').on('change', function() {
        var status = $('#statusFilter').val();
        var type = $('#typeFilter').val();
        $.ajax({
          url: 'admin-dashboard.php',
          type: 'GET',
          data: { ajax: 1, status: status, type: type },
          success: function(data) {
            $('#slotsTableContainer').html(data);
          }
        });
      });
      // Pagination click
      $(document).on('click', '.pagination .page-link', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');
        var status = $('#statusFilter').val();
        var type = $('#typeFilter').val();
        $.ajax({
          url: url,
          type: 'GET',
          data: { ajax: 1, status: status, type: type },
          success: function(data) {
            $('#slotsTableContainer').html(data);
          }
        });
      });
    });
  </script>
</body>
</html>
