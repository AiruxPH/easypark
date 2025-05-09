<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php?msg=error2");
    exit();
}

include 'db.php';

$user_type = $_SESSION['user_type'] ?? 'user';

// Optional pagination setup
$limit = 10; // notifications per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch notifications visible to user or 'all'
$stmt = $conn->prepare("SELECT message, created_at FROM notifications WHERE visibility = ? OR visibility = 'all' ORDER BY created_at DESC LIMIT ?, ?");
$stmt->bind_param("sii", $user_type, $offset, $limit);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>All Notifications | Pet Organizer</title>
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
<div class="container mt-4">
    <h3>All Notifications</h3>
    <ul class="list-group mb-4">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <li class="list-group-item">
                    <div><?php echo htmlspecialchars($row['message']); ?></div>
                    <small class="text-muted"><?php echo date("M d, Y H:i", strtotime($row['created_at'])); ?></small>
                </li>
            <?php endwhile; ?>
        <?php else: ?>
            <li class="list-group-item">No notifications found.</li>
        <?php endif; ?>
    </ul>

    <!-- Pagination controls -->
    <?php
    // Get total notifications count
    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM notifications WHERE visibility = ? OR visibility = 'all'");
    $countStmt->bind_param("s", $user_type);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $total = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($total / $limit);
    ?>

    <?php if ($totalPages > 1): ?>
        <nav>
            <ul class="pagination">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item <?php echo ($p == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $p; ?>"><?php echo $p; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>
<footer class="text-center mt-4">
    <div class="footer">
      © Anecito Randy E. Calunod Jr.
    </div>
  </footer>
<script src="js/jquery.min.js"></script>
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
