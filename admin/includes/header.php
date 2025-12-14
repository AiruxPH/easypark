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
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/font-awesome.min.css">
    <link rel="stylesheet" href="css/admin-custom.css">
    <style>
        /* Base inline overrides removed - migrated to css/admin-custom.css */
    </style>
</head>

<body>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="p-3">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="text-light m-0 sidebar-brand">EasyPark</h3>
                <button class="btn btn-link text-light p-0 d-md-none" id="sidebar-close">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link<?= !isset($_GET['section']) ? ' active' : '' ?>" href="index.php">
                        <i class="fa fa-dashboard"></i> <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= isset($_GET['section']) && $_GET['section'] === 'parking' ? ' active' : '' ?>"
                        href="?section=parking">
                        <i class="fa fa-car"></i> <span>Parking Slots</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= isset($_GET['section']) && $_GET['section'] === 'users' ? ' active' : '' ?>"
                        href="?section=users">
                        <i class="fa fa-users"></i> <span>Users</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= isset($_GET['section']) && $_GET['section'] === 'vehicles' ? ' active' : '' ?>"
                        href="?section=vehicles">
                        <i class="fa fa-car"></i> <span>Vehicles</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= isset($_GET['section']) && $_GET['section'] === 'transactions' ? ' active' : '' ?>"
                        href="?section=transactions">
                        <i class="fa fa-money"></i> <span>Transactions</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../logout.php">
                        <i class="fa fa-sign-out"></i> <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content Wrapper -->
    <div class="main-content" id="main-content">
        <!-- Top Navbar for Toggle -->
        <nav class="navbar navbar-light bg-white shadow-sm mb-4">
            <button class="btn btn-link text-primary" id="sidebar-toggle">
                <i class="fa fa-bars fa-lg"></i>
            </button>
            <span class="navbar-text ml-auto">
                <i class="fa fa-user-circle"></i> Admin
            </span>
        </nav>