<?php
session_start();

if (!isset($_SESSION['username'])){
  header("Location: ../index.php?msg=error2");
  exit();
}

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$username = $_SESSION['username'];
?>


<?php 
include 'db.php'; 
include 'functions.php';
?>
<!DOCTYPE html>
<html>
<head>
  <title>Delete | Pets Organizer</title>
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <style>
    footer {
      position: fixed;
      left: 0;
      bottom: 0;
      width: 100%;
    }
    img {
      max-width: 150px;
      height: auto;
    }
  </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container mt-4">
  <h2>Delete Pet</h2>

  <!-- Step 1: Search Form -->
  <form method="POST">
    <div class="mb-3">
      <label class="form-label">Enter pet ID</label>
      <input type="number" name="search_id" class="form-control" required>
    </div>
    <button type="submit" name="search" class="btn btn-primary">Search</button>
  </form>

  <?php
  if (isset($_POST['search'])) {
      $search_id = $_POST['search_id'];
      $res = $conn->query("SELECT * FROM pets_rec WHERE id = $search_id");

      if ($res->num_rows > 0) {
          $pet = $res->fetch_assoc();
          $id = $pet['id'];
          $name = $pet['name'];
          $breed = $pet['breed'];
          echo "<hr>";
          echo "<h4>Pet Found:</h4>";
          echo "<p><strong>ID:</strong> " . $pet['id'] . "</p>";
          echo "<p><strong>Name:</strong> " . $pet['name'] . "</p>";
          echo "<p><strong>Type:</strong> " . $pet['breed'] . "</p>";
          echo "<p><strong>Photo:</strong><br><img src='uploads/{$pet['photo']}' alt='Pet Photo'></p>";
          
          // Step 2: Confirm Deletion
          echo '
          <form method="POST" onsubmit="return confirm(\'Are you sure you want to delete this pet?\');">
            <input type="hidden" name="id" value="' . $pet['id'] . '">
            <input type="hidden" name="photo" value="' . $pet['photo'] . '">
            <button type="submit" name="delete" class="btn btn-danger">Delete This Pet</button>
          </form>';
      } else {
          echo "<div class='alert alert-warning mt-3'>Pet with ID $search_id not found.</div>";
      }
  }

  // Step 3: Perform Deletion
  if (isset($_POST['delete'])) {
      $id = $_POST['id'];
      $file = $_POST['photo'];

      if ($file != "default.jpg" && file_exists("uploads/$file")) {
          unlink("uploads/$file");
      }

      $res = $conn->query("SELECT * FROM pets_rec WHERE id = $id");

      if ($res->num_rows > 0) {
          $pet = $res->fetch_assoc();
          $id = $pet['id'];
          $name = $pet['name'];
          $breed = $pet['breed'];
          
          addNotification($conn, "$username deleted a pet: $name (ID $id)", 'admin');
          addNotification($conn, "$username deleted a pet: $name (ID $id)", 'staff');
          addNotification($conn, "A pet has been deleted. ID: $id, Name: $name", 'user');
      }
      $conn->query("DELETE FROM pets_rec WHERE id = $id");

      echo "<div class='alert alert-success mt-3'>Pet info deleted successfully.</div>";
  }
  ?>
</div>

<footer class="text-center mt-4">
    <div class="footer">
      © Anecito Randy E. Calunod Jr.
    </div>
  </footer>

<script src="js/jquery.min.js"></script>
<script src="js/popper.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script>
let lastNotifId = 0;

function loadNotifications() {
    $.ajax({
        url: 'fetch_notifications.php',
        method: 'GET',
        success: function(data) {
            let dropdown = $('#notifDropdown').next('.dropdown-menu');
            let html = '';

            if (data.notifs.length > 0) {
                data.notifs.forEach(function(notif) {
                    html += `
                        <li>
                            <a class="dropdown-item text-wrap small ${notif.is_read == 0 ? 'font-weight-bold' : ''}">
                                ${notif.message}<br>
                                <small class="text-muted">${notif.time}</small>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                    `;
                });

                // 🔈 Play sound only if there's a new notification
                if (data.notifs[0].id > lastNotifId) {
                    document.getElementById('notif-sound').play();
                    lastNotifId = data.notifs[0].id;
                }
            } else {
                html += `<li><a class="dropdown-item">No notifications.</a></li>`;
            }

            html += `<li><a class="dropdown-item text-center" href="all_notifications.php">View All</a></li>`;
            dropdown.html(html);

            // 🔴 Show or hide badge
            if (data.unread_count > 0) {
                $('#notif-count').text(data.unread_count).show();
            } else {
                $('#notif-count').hide();
            }
        }
    });
}

setInterval(loadNotifications, 5000);
loadNotifications(); // Initial load

$('#notifDropdown').on('click', function () {
    $.ajax({
        url: 'mark_notifications_read.php',
        method: 'POST',
        success: function () {
            $('#notif-count').hide(); // Hide the red badge
        }
    });
});

</script>
</body>
</html>
