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
  <link rel="stylesheet" href="../css/font-awesome.min.css">
  <style>
    body {
      background: url('../images/bg-car.jpg') no-repeat center center fixed;
      background-size: cover;
      min-height: 100vh;
    }

    .bg-overlay {
      background: rgba(20, 20, 20, 0.85);
      min-height: 100vh;
      padding-bottom: 40px;
    }

    .header-bar {
      background: rgba(0, 0, 0, 0.7);
      color: #ffc107;
      padding: 1.5rem 2rem 1rem 2rem;
      border-radius: 0 0 1rem 1rem;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
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

    .staff-navbar {
      background: #232526;
      border-radius: 1rem;
      margin-bottom: 2rem;
      box-shadow: 0 2px 12px rgba(0, 0, 0, 0.12);
    }

    .staff-navbar .nav-link {
      color: #ffc107;
      font-weight: 600;
      font-size: 1.1rem;
      letter-spacing: 0.5px;
    }

    .staff-navbar .nav-link.active,
    .staff-navbar .nav-link:focus {
      background: #ffc107;
      color: #232526 !important;
      border-radius: 0.5rem;
    }

    @media (max-width: 767px) {
      .header-bar {
        flex-direction: column;
        align-items: flex-start;
        padding: 1rem;
      }

      .section-card {
        padding: 1rem;
      }

      .staff-navbar .nav-link {
        font-size: 1rem;
      }
    }
  </style>
</head>

<body>
  <div class="bg-overlay">
    <div class="header-bar mb-4">
      <h2><i class="fa fa-user-shield"></i> Staff Dashboard</h2>
      <div class="d-flex align-items-center">
        <a href="profile.php" class="d-flex align-items-center nav-link p-0 mr-3"
          style="color: #ffc107; text-decoration: none;">
          <img
            src="<?php echo (!empty($staff['image']) && file_exists('../images/' . $staff['image'])) ? '../images/' . $staff['image'] : '../images/default.jpg'; ?>"
            alt="Profile Picture" class="rounded-circle mr-2"
            style="width:40px;height:40px;object-fit:cover;border:2px solid #ffc107;">
          <span class="font-weight-bold" style="font-size:1.1rem;">
            My Profile
          </span>
        </a>
        <a href="../logout.php" class="btn btn-secondary ml-2"><i class="fa fa-sign-out"></i> Logout</a>
      </div>
    </div>
    <div class="container">
      <!-- Responsive Navbar for Sections -->
      <nav class="staff-navbar navbar navbar-expand-md navbar-dark mb-4">
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#staffNav"
          aria-controls="staffNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="staffNav">
          <ul class="navbar-nav w-100 justify-content-between">
            <li class="nav-item"><a class="nav-link" href="javascript:void(0)" data-section="dashboard"><i
                  class="fa fa-tachometer-alt"></i> Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="javascript:void(0)" data-section="bookings"><i
                  class="fa fa-calendar-check-o"></i> Bookings</a></li>
            <li class="nav-item"><a class="nav-link" href="javascript:void(0)" data-section="active"><i
                  class="fa fa-play-circle"></i> Active</a></li>
            <li class="nav-item"><a class="nav-link" href="javascript:void(0)" data-section="history"><i
                  class="fa fa-history"></i> History</a></li>
            <li class="nav-item"><a class="nav-link" href="javascript:void(0)" data-section="slots"><i
                  class="fa fa-car"></i> Slots</a></li>
          </ul>
        </div>
      </nav>
      <!-- Section Content Loader -->
      <div id="section-content"></div>
    </div>
  </div>
  <script src="../js/bootstrap.bundle.min.js"></script>
  <script src="../js/jquery.min.js"></script>
  <script>
    // SPA-like section navigation
    const sectionFiles = {
      dashboard: 'section-dashboard.php',
      profile: 'section-profile.php',
      bookings: 'section-bookings.php',
      active: 'section-active.php',
      history: 'section-history.php',
      slots: 'section-slots.php'
    };
    // Check for section in localStorage or URL hash
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
        // Auto-refresh these sections every 30 seconds
        refreshInterval = setInterval(function () {
          // Only refresh if no modal/input is active (simple check)
          if ($('input:focus').length === 0) {
            // Reload silently (without fadeOut)
            let refreshUrl = sectionFiles[section];
            // Preserve current usage? For improved UX, we might skip preserving detailed view state if it's too complex, 
            // but re-loading the content keeps counters live.
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
      // Intercept pagination link clicks inside #section-content
      $('#section-content').on('click', '.pagination .page-link', function (e) {
        var href = $(this).attr('href');
        if (href && href !== '#' && href.indexOf('javascript:') !== 0) {
          e.preventDefault();
          // Extract page param from href
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
  <style>
    th.sortable {
      cursor: pointer;
    }

    th.asc:after {
      content: ' \25B2';
    }

    th.desc:after {
      content: ' \25BC';
    }
  </style>
  <script src="../js/ef9baa832e.js" crossorigin="anonymous"></script>
</body>

</html>