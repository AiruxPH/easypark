<?php
session_start();

if (!isset($_SESSION['username'])){
  header("Location: ../index.php?msg=error2");
  exit();
}

if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'staff', 'user'])) {
    header("Location: index.php");
    exit;
}
?>

<?php include 'db.php'; ?>
<!DOCTYPE html>
<html>
<head>
  <title>View | Pets</title>
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <style>
    img.thumb {
      width: 80px;
      height: auto;
    }

    footer {
      position: fixed;
  left: 0;
  bottom: 0;
  width: 100%;
    }
  </style>
</head>
<body>
  <?php include 'includes/navbar.php'; ?>
  <div class="container">
    <h2>All Pets</h2>
    <table class="table table-bordered table-hover">
      <thead class="table-dark">
        <tr>
          <th>ID</th>
          <th>Photo</th>
          <th>Name</th>
          <th>Breed</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $res = $conn->query("SELECT * FROM pets_rec");
        while ($row = $res->fetch_assoc()) {
          echo "<tr>
            <td>{$row['id']}</td>
            <td>";
          if (!empty($row['photo']) && file_exists("uploads/{$row['photo']}")) {
            echo "<img src='uploads/{$row['photo']}' class='thumb'>";
          } else {
            echo "No image";
          }
          echo "</td>
            <td>{$row['name']}</td>
            <td>{$row['breed']}</td>
          </tr>";
        }
        ?>
      </tbody>
    </table>
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
