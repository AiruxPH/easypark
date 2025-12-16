<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
  header("Location: ../index.php");
  exit();
}
require_once '../includes/db.php';

// Fetch staff profile info
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
  header('Location: staff-dashboard.php?profile_updated=1');
  exit();
}
// Handle profile picture upload (optional)
if (isset($_POST['upload_pic']) && isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
  $fileTmp = $_FILES['profile_pic']['tmp_name'];
  $fileName = basename($_FILES['profile_pic']['name']);
  $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
  $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
  if (in_array($fileExt, $allowed)) {
    $newName = 'profile_staff_' . $staff_id . '_' . time() . '.' . $fileExt;
    $targetPath = '../images/' . $newName;
    if (move_uploaded_file($fileTmp, $targetPath)) {
      // Remove old pic if not default
      if (!empty($staff['image']) && $staff['image'] !== 'default.jpg' && file_exists('../images/' . $staff['image'])) {
        unlink('../images/' . $staff['image']);
      }
      $stmt = $pdo->prepare('UPDATE users SET image = ? WHERE user_id = ?');
      $stmt->execute([$newName, $staff_id]);
      header('Location: staff-dashboard.php?profile_updated=1');
      exit();
    }
  }
}
// Handle profile picture delete (optional)
if (isset($_POST['delete_pic'])) {
  if (!empty($staff['image']) && $staff['image'] !== 'default.jpg' && file_exists('../images/' . $staff['image'])) {
    unlink('../images/' . $staff['image']);
  }
  $stmt = $pdo->prepare('UPDATE users SET image = NULL WHERE user_id = ?');
  $stmt->execute([$staff_id]);
  header('Location: staff-dashboard.php?profile_updated=1');
  exit();
}

// All shared data-fetching and helpers are now in section-common.php, which is used by all AJAX section includes and can be included as needed.
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title>Staff Dashboard - EasyPark</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #f0a500;
      --dark: #1a1a1a;
      --glass: rgba(255, 255, 255, 0.08);
      --glass-border: rgba(255, 255, 255, 0.1);
    }

    body {
      font-family: 'Outfit', sans-serif;
      min-height: 100vh;
      background: url('../images/bg-car.jpg') no-repeat center center fixed;
      background-size: cover;
      color: #fff;
    }

    .bg-overlay {
      background: radial-gradient(circle at center, rgba(0, 0, 0, 0.4) 0%, rgba(0, 0, 0, 0.85) 100%);
      min-height: 100vh;
      padding-bottom: 40px;
    }

    /* Staff Navbar */
    .staff-navbar {
      background: rgba(0, 0, 0, 0.6) !important;
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      border-radius: 0;
      margin-bottom: 2rem;
      padding: 1rem 2rem;
    }

    .staff-navbar .nav-link {
      color: rgba(255, 255, 255, 0.7);
      font-weight: 500;
      transition: all 0.3s ease;
      padding: 0.5rem 1rem;
      border-radius: 50px;
      margin: 0 5px;
    }

    .staff-navbar .nav-link:hover,
    .staff-navbar .nav-link.active {
      background: var(--glass);
      color: var(--primary) !important;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }

    .staff-navbar .nav-link i {
      margin-right: 8px;
    }

    /* Header Bar (Profile Area) */
    .header-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 2rem 0;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      margin-bottom: 3rem;
    }

    .page-title h2 {
      font-weight: 700;
      letter-spacing: 1px;
      font-size: 2.2rem;
      color: #fff;
      margin: 0;
    }

    .page-title span {
      color: var(--primary);
    }

    /* Glass Cards (for sections) */
    .glass-card {
      background: var(--glass);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border: 1px solid var(--glass-border);
      border-radius: 24px;
      padding: 2rem;
      box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
      margin-bottom: 2rem;
    }

    /* Preloader */
    #preloader {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: #0f0f0f;
      z-index: 9999;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      transition: opacity 0.8s ease-out;
    }

    .loader-logo {
      font-size: 3rem;
      font-weight: 800;
      color: var(--primary);
      margin-bottom: 20px;
      animation: pulse 1s infinite alternate;
    }

    .car-loader {
      position: relative;
      width: 100px;
      height: 4px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 4px;
      overflow: hidden;
    }

    .car-bar {
      position: absolute;
      top: 0;
      left: 0;
      height: 100%;
      width: 50%;
      background: var(--primary);
      animation: drive 1.5s infinite linear;
      border-radius: 4px;
    }

    @keyframes pulse {
      from {
        opacity: 0.6;
        transform: scale(0.95);
      }

      to {
        opacity: 1;
        transform: scale(1.05);
      }
    }

    @keyframes drive {
      0% {
        left: -50%;
      }

      100% {
        left: 100%;
      }
    }

    .hide-loader {
      opacity: 0;
      pointer-events: none;
    }

    /* GLASS COMPONENTS (Global) */

    /* Buttons */
    .btn-glass {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      color: #fff;
      backdrop-filter: blur(5px);
      transition: all 0.3s ease;
    }

    .btn-glass:hover {
      background: rgba(255, 255, 255, 0.2);
      transform: translateY(-2px);
      color: #fff;
    }

    .btn-glass-primary {
      background: rgba(240, 165, 0, 0.8);
      border: none;
      color: #000;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .btn-glass-primary:hover {
      background: #f0a500;
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(240, 165, 0, 0.4);
    }

    /* Inputs */
    .glass-input {
      background: rgba(0, 0, 0, 0.3) !important;
      border: 1px solid rgba(255, 255, 255, 0.1) !important;
      color: #fff !important;
      border-radius: 10px;
      padding: 0.5rem 1rem;
      height: auto;
    }

    .glass-input:focus {
      background: rgba(0, 0, 0, 0.5) !important;
      border-color: var(--primary) !important;
      box-shadow: 0 0 0 0.2rem rgba(240, 165, 0, 0.25) !important;
    }

    /* Input Placeholders */
    .glass-input::placeholder {
      color: rgba(255, 255, 255, 0.5);
    }

    /* Select Options */
    select.glass-input option {
      background: #333;
      color: #fff;
    }

    /* Tables */
    .table-responsive {
      border-radius: 15px;
      overflow: hidden;
    }

    .glass-table {
      width: 100%;
      color: #fff;
      margin-bottom: 0;
    }

    .glass-table thead th {
      background: rgba(240, 165, 0, 0.1);
      color: var(--primary);
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.85rem;
      letter-spacing: 0.5px;
      border: none;
      padding: 1rem;
    }

    .glass-table tbody tr {
      background: rgba(255, 255, 255, 0.02);
      transition: all 0.2s;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .glass-table tbody tr:hover {
      background: rgba(255, 255, 255, 0.08);
    }

    .glass-table td {
      padding: 1rem;
      vertical-align: middle;
      border: none;
      color: rgba(255, 255, 255, 0.8);
    }

    /* Badges */
    .badge-glass-success {
      background: rgba(40, 167, 69, 0.2);
      color: #28a745;
      border: 1px solid rgba(40, 167, 69, 0.3);
    }

    .badge-glass-warning {
      background: rgba(255, 193, 7, 0.2);
      color: #ffc107;
      border: 1px solid rgba(255, 193, 7, 0.3);
    }

    .badge-glass-danger {
      background: rgba(220, 53, 69, 0.2);
      color: #dc3545;
      border: 1px solid rgba(220, 53, 69, 0.3);
    }

    .badge-glass-info {
      background: rgba(23, 162, 184, 0.2);
      color: #17a2b8;
      border: 1px solid rgba(23, 162, 184, 0.3);
    }

    .badge {
      padding: 0.5em 0.8em;
      font-weight: 500;
      letter-spacing: 0.5px;
      border-radius: 6px;
    }

    /* Text Utilities Overrides for Glass Theme */
    .text-muted {
      color: rgba(255, 255, 255, 0.5) !important;
    }

    .text-dark {
      color: #fff !important;
      /* Force readable text if class exists */
    }

    /* Pagination */
    .pagination .page-item .page-link {
      background: rgba(0, 0, 0, 0.3);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: #fff;
      margin: 0 5px;
      border-radius: 8px;
      transition: all 0.3s ease;
    }

    .pagination .page-item:first-child .page-link,
    .pagination .page-item:last-child .page-link {
      border-radius: 8px;
      /* Override bootstrap rounding */
    }

    .pagination .page-item .page-link:hover {
      background: rgba(255, 255, 255, 0.1);
      transform: translateY(-2px);
    }

    .pagination .page-item.active .page-link {
      background: var(--primary);
      border-color: var(--primary);
      color: #000;
      font-weight: bold;
      box-shadow: 0 4px 10px rgba(240, 165, 0, 0.3);
    }

    .pagination .page-item.disabled .page-link {
      background: rgba(0, 0, 0, 0.1);
      color: rgba(255, 255, 255, 0.3);
      border-color: rgba(255, 255, 255, 0.05);
    }
  </style>
</head>

<body>

  <!-- Preloader -->
  <div id="preloader">
    <div class="loader-logo">EASYPARK</div>
    <div class="car-loader">
      <div class="car-bar"></div>
    </div>
    <p class="text-white-50 mt-2 small letter-spacing-1">STARTING ENGINE...</p>
  </div>

  <div class="bg-overlay">
    <div class="container">
      <!-- Header Bar -->
      <div class="header-bar">
        <div class="page-title">
          <h2>Staff <span>Dashboard</span></h2>
          <p class="text-white-50 mb-0 small">Manage bookings and parking slots efficiently.</p>
        </div>
        <div class="d-flex align-items-center">
          <a href="javascript:void(0)" onclick="loadSection('profile')"
            class="d-flex align-items-center nav-link p-0 mr-4"
            style="color: rgba(255,255,255,0.8); text-decoration: none;">
            <img
              src="<?php echo (!empty($staff['image']) && file_exists('../images/' . $staff['image'])) ? '../images/' . $staff['image'] : '../images/default.jpg'; ?>"
              alt="Profile Picture" class="rounded-circle mr-2 shadow"
              style="width:40px;height:40px;object-fit:cover;border:2px solid var(--primary);">
            <span style="font-size:1rem;">
              My Profile
            </span>
          </a>
          <a href="../logout.php" class="btn btn-outline-light btn-sm px-3 rounded-pill"><i
              class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="staff-navbar navbar navbar-expand-md navbar-dark mb-4">
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#staffNav"
          aria-controls="staffNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="staffNav">
          <ul class="navbar-nav w-100 justify-content-center">
            <li class="nav-item"><a class="nav-link active" href="javascript:void(0)" data-section="dashboard"><i
                  class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="javascript:void(0)" data-section="bookings"><i
                  class="fas fa-calendar-check"></i> Bookings</a></li>
            <li class="nav-item"><a class="nav-link" href="javascript:void(0)" data-section="active"><i
                  class="fas fa-play-circle"></i> Active</a></li>
            <li class="nav-item"><a class="nav-link" href="javascript:void(0)" data-section="history"><i
                  class="fas fa-history"></i> History</a></li>
            <li class="nav-item"><a class="nav-link" href="javascript:void(0)" data-section="slots"><i
                  class="fas fa-car"></i> Slots</a></li>
            <li class="nav-item"><a class="nav-link" href="javascript:void(0)" data-section="activity"><i
                  class="fas fa-clipboard-list"></i> Activity</a></li>
          </ul>
        </div>
      </nav>

      <!-- Section Content Loader -->
      <div id="section-content"></div>
    </div>
  </div>

  <script src="../js/jquery.min.js"></script>
  <script src="../js/popper.min.js"></script>
  <script src="../js/bootstrap.bundle.min.js"></script>

  <script>
    // Preloader Logic
    window.addEventListener('load', function () {
      const loader = document.getElementById('preloader');
      setTimeout(function () {
        if (loader) loader.classList.add('hide-loader');
      }, 1200);
    });

    // SPA-like section navigation
    const sectionFiles = {
      dashboard: 'section-dashboard.php',
      profile: 'section-profile.php',
      bookings: 'section-bookings.php',
      active: 'section-active.php',
      history: 'section-history.php',
      slots: 'section-slots.php',
      activity: 'section-activity.php'
    };

    // Check for section in localStorage or default
    let currentSection = localStorage.getItem('staffCurrentSection') || 'dashboard';

    // Auto-refresh interval reference
    let refreshInterval = null;

    function loadSection(section, params = {}) {
      currentSection = section;
      localStorage.setItem('staffCurrentSection', section);
      $('.staff-navbar .nav-link').removeClass('active');
      $('.staff-navbar .nav-link[data-section="' + section + '"]').addClass('active');

      let url = sectionFiles[section];
      if (Object.keys(params).length > 0) {
        url += '?' + $.param(params);
      }

      $('#section-content').fadeOut(100, function () {
        $('#section-content').load(url, function (response, status, xhr) {
          if (status === "error") {
            $('#section-content').html(
              "<div class='alert alert-danger'>Failed to load section: " + xhr.status + " " + xhr.statusText + "</div>"
            ).fadeIn(100);
          } else {
            $('#section-content').fadeIn(100);
          }
        });
      });

      // Handle Auto-Refresh logic
      if (refreshInterval) clearInterval(refreshInterval);
      if (section === 'active' || section === 'slots' || section === 'dashboard') {
        refreshInterval = setInterval(function () {
          if ($('input:focus').length === 0) {
            let refreshUrl = sectionFiles[section];
            $('#section-content').load(refreshUrl);
            console.log('Auto-refreshing ' + section + '...');
          }
        }, 30000); // 30 seconds
      }
    }

    $(function () {
      $('.staff-navbar .nav-link').on('click', function (e) {
        e.preventDefault();
        var section = $(this).data('section');
        if (!section) return;
        loadSection(section);
      });

      // Intercept pagination link clicks
      $('#section-content').on('click', '.pagination .page-link', function (e) {
        var href = $(this).attr('href');
        if (href && href !== '#' && href.indexOf('javascript:') !== 0) {
          e.preventDefault();
          var params = {};
          var match = href.match(/([a-z_]+)_page=(\d+)/);
          if (match) {
            params[match[1] + '_page'] = match[2];
          }
          loadSection(currentSection, params);
        }
      });

      // Load last focused section on page load
      loadSection(currentSection);
    });
  </script>
</body>

</html>