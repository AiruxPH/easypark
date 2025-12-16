<?php
require_once '../includes/db.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once 'includes/header.php';

$section = $_GET['section'] ?? 'dashboard';

switch ($section) {
    case 'parking':
        require_once 'sections/parking.php';
        break;
    case 'users':
        require_once 'sections/users.php';
        break;
    case 'vehicles':
        global $conn;
        require_once 'sections/vehicles.php';
        break;
    case 'payments':
        global $conn;
        require_once 'sections/payments.php';
        break;
    case 'activity_logs':
        global $conn;
        require_once 'sections/activity_logs.php';
        break;
    default:
        require_once 'sections/dashboard.php';
}

require_once 'includes/footer.php';
