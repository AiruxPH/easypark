<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>EasyPark Admin Dashboard</title>
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/font-awesome.min.css">
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
    <a class="navbar-brand mb-4" href="#"><i class="fa fa-parking"></i> EasyPark</a>
    <ul class="nav flex-column mb-auto">
      <li class="nav-item">
        <a class="nav-link active" href="#"><i class="fa fa-tachometer"></i> Dashboard</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#"><i class="fa fa-car"></i> Parking Slots</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#"><i class="fa fa-users"></i> Users</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#"><i class="fa fa-exchange"></i> Transactions</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#"><i class="fa fa-cogs"></i> Settings</a>
      </li>
    </ul>
    <hr class="bg-secondary">
    <div class="dropdown">
      <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <img src="https://ui-avatars.com/api/?name=Admin" alt="admin" width="32" height="32" class="rounded-circle mr-2">
        <strong>Admin</strong>
      </a>
      <div class="dropdown-menu dropdown-menu-dark bg-dark text-light" aria-labelledby="dropdownUser">
        <a class="dropdown-item" href="#"><i class="fa fa-user"></i> Profile</a>
        <a class="dropdown-item" href="#"><i class="fa fa-cog"></i> Settings</a>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item text-danger" href="logout.php"><i class="fa fa-sign-out"></i> Logout</a>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <div id="main-content">
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
      <button class="btn btn-outline-secondary d-lg-none mr-2" id="sidebarToggle"><i class="fa fa-bars"></i></button>
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
              <i class="fa fa-bell"></i> <span class="badge badge-danger">3</span>
            </a>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="notifDropdown">
              <a class="dropdown-item" href="#"><i class="fa fa-info-circle text-primary"></i> New user registered</a>
              <a class="dropdown-item" href="#"><i class="fa fa-car text-success"></i> Slot #12 reserved</a>
              <a class="dropdown-item" href="#"><i class="fa fa-exclamation-triangle text-warning"></i> Payment pending</a>
            </div>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <img src="https://ui-avatars.com/api/?name=Admin" alt="admin" width="30" height="30" class="rounded-circle"> Admin
            </a>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
              <a class="dropdown-item" href="#"><i class="fa fa-user"></i> Profile</a>
              <a class="dropdown-item" href="#"><i class="fa fa-cog"></i> Settings</a>
              <div class="dropdown-divider"></div>
              <a class="dropdown-item text-danger" href="logout.php"><i class="fa fa-sign-out"></i> Logout</a>
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
                  <div class="h5 mb-0 font-weight-bold text-gray-800">100</div>
                </div>
                <div class="col-auto">
                  <i class="fa fa-parking fa-2x text-gray-300"></i>
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
                  <div class="h5 mb-0 font-weight-bold text-gray-800">75</div>
                </div>
                <div class="col-auto">
                  <i class="fa fa-check-circle fa-2x text-gray-300"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3 mb-4">
          <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
              <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                  <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Users</div>
                  <div class="h5 mb-0 font-weight-bold text-gray-800">150</div>
                </div>
                <div class="col-auto">
                  <i class="fa fa-users fa-2x text-gray-300"></i>
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
                  <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Revenue</div>
                  <div class="h5 mb-0 font-weight-bold text-gray-800">₱15,000</div>
                </div>
                <div class="col-auto">
                  <i class="fa fa-dollar-sign fa-2x text-gray-300"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- Add more dashboard content here -->
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
  </script>
</body>
</html>
