<?php
require_once '../db.php';
require_once 'includes/header.php';

$section = $_GET['section'] ?? 'dashboard';

switch($section) {
    case 'parking':
        require_once 'sections/parking.php';
        break;
    case 'users':
        require_once 'sections/users.php';
        break;
    case 'reservations':
        require_once 'sections/reservations.php';
        break;
    case 'vehicles':
        require_once 'sections/vehicles.php';
        break;
    case 'transactions':
        require_once 'sections/transactions.php';
        break;
    default:
        require_once 'sections/dashboard.php';
}

require_once 'includes/footer.php';
