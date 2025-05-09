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

<?php 
include 'db.php'; 
include 'functions.php';
?>
<!DOCTYPE html>
<html>
<head>
  <title>Edit | Pets Organizer</title>
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <style>
    img.thumb {
      width: 100px;
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
    <h2>Edit Pet Info</h2>
    <form method="GET" class="mb-4">
      <label class="form-label">Enter Pet ID to Edit</label>
      <div class="input-group">
        <input type="number" name="id" class="form-control" required>
        <button type="submit" class="btn btn-primary">Search</button>
      </div>
    </form>

    <?php
    if (isset($_GET['id'])) {
        $id = $_GET['id'];
        $res = $conn->query("SELECT * FROM pets_rec WHERE id=$id");
        if ($res->num_rows > 0) {
            $pet = $res->fetch_assoc();
    ?>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="id" value="<?= $pet['id'] ?>">
      <input type="hidden" name="existing_photo" value="<?= $pet['photo'] ?>">
      <div class="mb-3">
        <label class="form-label">Pet Name</label>
        <input type="text" name="name" class="form-control" value="<?= $pet['name'] ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Breed</label>
        <input type="text" name="breed" class="form-control" value="<?= $pet['breed'] ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Current Photo</label><br>
        <?php if (!empty($pet['photo']) && file_exists("uploads/{$pet['photo']}")): ?>
          <img src="uploads/<?= $pet['photo'] ?>" class="thumb"><br>
        <?php else: ?>
          No image
        <?php endif; ?>
      </div>
      <div class="mb-3">
        <label class="form-label">Upload New Photo (optional)</label>
        <input type="file" name="photo" class="form-control">
      </div>
      <button type="submit" class="btn btn-warning">Update</button>
    </form>
    <?php
        } else {
            echo "<div class='alert alert-danger'>No pet found with ID $id.</div>";
        }
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
      $id = $_POST['id'];
      $name = $_POST['name'];
      $breed = $_POST['breed'];
      $photo = $_POST['existing_photo']; // fallback to existing photo

      if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
          $target_dir = "uploads/";
          if (!file_exists($target_dir)) {
              mkdir($target_dir, 0777, true);
          }

          // Generate unique filename
          $extension = pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION);
          $photo = uniqid('img_', true) . '.' . $extension;
          $target_file = $target_dir . $photo;

          move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file);
      }

      // Now, $photo is either the new file name or existing one
      $sql = "UPDATE pets_rec SET name='$name', breed='$breed', photo='$photo' WHERE id=$id";
      $conn->query($sql);
      addNotification($conn, "$username updated pet ID $id to $name ($breed)", 'admin');
      addNotification($conn, "$username updated pet ID $id to $name ($breed)", 'staff');
      addNotification($conn, "Pet ID $id is updated to $name ($breed)", 'admin');

      // Optional: You can use session flash messages instead of echo
      echo "<div class='alert alert-success mt-3'>Pet info updated successfully.</div>";

      header("Location: view.php");
      exit();
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
