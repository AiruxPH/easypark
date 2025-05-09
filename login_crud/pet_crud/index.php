<?php
session_start();

if (!isset($_SESSION['username'])){
    header("Location: ../index.php?msg=error2");
    exit();
}
?>

<?php include 'db.php'; ?>
<!DOCTYPE html>
<html>
<head>
  <title>Home | Pet Organizer</title>
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <style>
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
  <?php if (isset($_GET['msg']) && $_GET['msg'] == 'error2'):?>
                <script>
                    alert("Logout first to register as a new user.");
                </script>
                <?php endif;?>
    <h1 class="mt-4">Welcome to Pet Manager</h1>
  </div>
  <footer>
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