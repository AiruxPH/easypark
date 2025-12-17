<?php
// Check if user is logged in and is admin
session_start();
if (!isset($_SESSION['user_email']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: /login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>EasyPark Admin Dashboard</title>
    <link rel="icon" href="../images/favicon.png" type="image/png">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="css/admin-custom.css?v=<?= time() ?>" rel="stylesheet">
    <link href="css/parking-grid.css" rel="stylesheet">
</head>

<body class="bg-dark-theme">

    <script src="../js/ef9baa832e.js" crossorigin="anonymous"></script>
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm mb-4">
        <div class="container-fluid">
            <a class="navbar-brand font-weight-bold" href="index.php">EasyPark Admin</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#adminNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item">
                        <a class="nav-link<?= !isset($_GET['section']) ? ' active' : '' ?>" href="index.php">
                            <i class="fa fa-dashboard"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= isset($_GET['section']) && $_GET['section'] === 'parking' ? ' active' : '' ?>"
                            href="?section=parking">
                            <i class="fa fa-car"></i> Parking Slots
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= isset($_GET['section']) && $_GET['section'] === 'users' ? ' active' : '' ?>"
                            href="?section=users">
                            <i class="fa fa-users"></i> Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= isset($_GET['section']) && $_GET['section'] === 'vehicles' ? ' active' : '' ?>"
                            href="?section=vehicles">
                            <i class="fa fa-car"></i> Vehicles
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= isset($_GET['section']) && $_GET['section'] === 'payments' ? ' active' : '' ?>"
                            href="?section=payments">
                            <i class="fa fa-credit-card"></i> Payments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= isset($_GET['section']) && $_GET['section'] === 'activity_logs' ? ' active' : '' ?>"
                            href="?section=activity_logs">
                            <i class="fa fa-list-alt"></i> Activity Logs
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item d-flex align-items-center mr-3">
                        <span class="text-gray-400 small" id="server-clock">
                            <i class="fa fa-clock-o"></i> Loading time...
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="navbar-text mr-3">
                            <i class="fa fa-user-circle"></i> Admin
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="../logout.php">
                            <i class="fa fa-sign-out"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <script>
        // Server time sync
        const serverTime = new Date("<?= date('r') ?>");
        // 'r' format matches JS Date parsing (RFC 2822), e.g. "Thu, 21 Dec 2000 16:01:07 +0200"
        let clientOffset = new Date().getTime() - serverTime.getTime();

        function updateClock() {
            const now = new Date(new Date().getTime() - clientOffset);
            const options = {
                weekday: 'short',
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                timeZoneName: 'short'
            };
            document.getElementById('server-clock').innerHTML = '<i class="fa fa-clock-o"></i> ' + now.toLocaleString('en-US', options);
        }

        // Initial call and interval
        // Wait for load to ensure element exists if script runs early (though it's at end of body usually, here it is in body)
        // Since script is placed after nav, element exists.
        // Actually, logic correction:
        // We want to simulate SERVER time ticking.
        // We captured `serverTime`. 
        // To make it tick, we can just add elapsed time since load.

        const startTime = new Date().getTime();
        const initialServerTime = serverTime.getTime();

        function tick() {
            const elapsed = new Date().getTime() - startTime;
            const currentServerTime = new Date(initialServerTime + elapsed);

            const options = {
                weekday: 'short',
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            const timeString = currentServerTime.toLocaleString('en-US', options);

            const el = document.getElementById('server-clock');
            if (el) el.innerHTML = '<i class="fa fa-clock-o"></i> ' + timeString;
        }

        setInterval(tick, 1000);
        tick();
    </script>

    <!-- Main Content Wrapper -->
    <div class="main-content container-fluid" id="main-content">