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

    <nav class="sidebar">
        <div class="p-3">
            <h3 class="text-light mb-4">EasyPark Admin</h3>
            <ul class="nav flex-column">
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
                    <a class="nav-link<?= isset($_GET['section']) && $_GET['section'] === 'transactions' ? ' active' : '' ?>"
                        href="?section=transactions">
                        <i class="fa fa-money"></i> Transactions
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../logout.php">
                        <i class="fa fa-sign-out"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="main-content">