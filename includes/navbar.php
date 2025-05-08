<?php
if(session_status() == PHP_SESSION_NONE)
    session_start();

$user_type = $_SESSION['user_type'] ?? null;

$user = "";

if($user_type === 'admin' || $user_type === 'staff' || $user_type === 'user') {

    if($user_type === 'admin') {
        $user = "Admin";
    }

    if($user_type === 'staff') {
      $user = "Staff";
  }

  if($user_type === 'user') {
    $user = $_SESSION['username'];
}
    }
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand" href="index.php">Pet Organizer</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav">

      <?php
include_once 'db.php';

$notif_stmt = $conn->prepare("SELECT message, created_at FROM notifications WHERE visibility = ? OR visibility = 'all' ORDER BY created_at DESC LIMIT 5");
$notif_stmt->bind_param("s", $user_type);
$notif_stmt->execute();
$notifs = $notif_stmt->get_result();
?>

<li class="nav-item dropdown">
  <a class="nav-link dropdown-toggle position-relative" href="#" id="notifDropdown" role="button" data-toggle="dropdown">
    🔔 Notifications
    <span id="notif-count" class="badge badge-danger position-absolute top-0 start-100 translate-middle" style="display:none;"></span>
</a>

  <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notifDropdown" style="min-width: 300px;">
    <?php if ($notifs->num_rows > 0): ?>
      <?php while ($row = $notifs->fetch_assoc()): ?>
        <li>
          <a class="dropdown-item text-wrap small"><?php echo $row['message']; ?><br>
          <small class="text-muted"><?php echo date("M d, H:i", strtotime($row['created_at'])); ?></small></a>
        </li>
        <li><hr class="dropdown-divider"></li>
      <?php endwhile; ?>
    <?php else: ?>
      <li><a class="dropdown-item">No notifications.</a></li>
    <?php endif; ?>
    <li><a class="dropdown-item text-center" href="all_notifications.php">View All</a></li>
  </ul>
</li>


        <!-- Home is available to everyone -->
        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>

        <?php if ($user_type === 'admin' || $user_type === 'staff'): ?>
          <li class="nav-item"><a class="nav-link" href="add.php">Add</a></li>
          <li class="nav-item"><a class="nav-link" href="edit.php">Edit</a></li>
        <?php endif; ?>

        <?php if ($user_type === 'admin' || $user_type === 'staff' || $user_type === 'user'): ?>
          <li class="nav-item"><a class="nav-link" href="search.php">Search</a></li>
          <li class="nav-item"><a class="nav-link" href="view.php">View</a></li>
        <?php endif; ?>

        <?php if ($user_type === 'admin'): ?>
          <li class="nav-item"><a class="nav-link" href="delete.php">Delete</a></li>
        <?php endif; ?>

        <!-- Logout is available to everyone -->
        <li class="nav-item"><a class="nav-link" href="../logout.php">Logout (<?php echo "$user" ?>)</a></li>
      </ul>
    </div>
  </div>

</nav>
