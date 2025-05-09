
<?php
session_start();

if (!isset($_SESSION['username'])){
  header("Location: ../index.php?msg=error2");
  exit();
}

if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'staff'])) {
    header("Location: index.php");
    exit;
}
?>

<?php include 'db.php'; 
  require_once 'functions.php';
?>
<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $breed = $_POST['breed'];
    $photo = "";
    $username = $_SESSION['username'];

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
      $extension = pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION);
      $photo = uniqid('img_', true) . '.' . $extension;
      $target_file = "uploads/" . $photo;
      move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file);
  } else {
      $photo = "default.jpg";
  }


    $sql = "INSERT INTO pets_rec (name, breed, photo) VALUES ('$name', '$breed', '$photo')";
    $conn->query($sql);

    addNotification($conn, "$username added a new pet: $name ($breed)", 'admin');
    addNotification($conn, "$username added a new pet: $name ($breed)", 'staff');
    addNotification($conn, "A new pet was added: $name ($breed)", 'user');

    header("Location: view.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Add | Pets Manager</title>
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
    <h2>Add New Pet</h2>
    <form method="post" enctype="multipart/form-data">
      <div class="mb-3">
        <label class="form-label">Pet Name</label>
        <input type="text" name="name" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Breed</label>
        <input type="text" name="breed" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Upload Photo</label>
        <input type="file" name="photo" class="form-control">
      </div>
      <button type="submit" class="btn btn-success">Add Pet</button>
    </form>
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