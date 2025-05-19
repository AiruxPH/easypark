<?php
session_start();
// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_email'])) {
    header('Location: ../login.php');
    exit;
} else if ($_SESSION['user_type'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}
require_once '../db.php';
// Handle AJAX request for parking slots table only
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    include __DIR__ . '/../admin-dashboard.php-table.php';
    exit;
}
// Fetch parking slot statistics using PDO
$totalSlots = $availableSlots = $reservedSlots = $occupiedSlots = 0;
$stmt = $pdo->query("SELECT COUNT(*) as total FROM parking_slots");
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $totalSlots = $row['total'];
}
$stmt = $pdo->query("SELECT COUNT(*) as available FROM parking_slots WHERE slot_status='available'");
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $availableSlots = $row['available'];
}
$stmt = $pdo->query("SELECT COUNT(*) as reserved FROM parking_slots WHERE slot_status='reserved'");
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $reservedSlots = $row['reserved'];
}
$stmt = $pdo->query("SELECT COUNT(*) as occupied FROM parking_slots WHERE slot_status='occupied'");
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $occupiedSlots = $row['occupied'];
}
$showParkingSlots = isset($_GET['page']) || isset($_GET['status']) || isset($_GET['type']);
// User management logic (pagination, search, etc.)
$usersPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $usersPerPage;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchBy = isset($_GET['searchBy']) ? $_GET['searchBy'] : 'all';
$filterType = isset($_GET['filterType']) ? $_GET['filterType'] : 'all';
$sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : 'user_id';
$sortOrder = isset($_GET['sortOrder']) ? $_GET['sortOrder'] : 'ASC';
$whereClause = [];
$params = [];
if ($search !== '') {
    switch($searchBy) {
        case 'user_id':
            $whereClause[] = "user_id = :search";
            $params[':search'] = $search;
            break;
        case 'first_name':
            $whereClause[] = "first_name LIKE :search";
            $params[':search'] = "%$search%";
            break;
        case 'middle_name':
            $whereClause[] = "middle_name LIKE :search";
            $params[':search'] = "%$search%";
            break;
        case 'last_name':
            $whereClause[] = "last_name LIKE :search";
            $params[':search'] = "%$search%";
            break;
        case 'email':
            $whereClause[] = "email LIKE :search";
            $params[':search'] = "%$search%";
            break;
        case 'all':
            $whereClause[] = "(first_name LIKE :search OR middle_name LIKE :search OR last_name LIKE :search OR email LIKE :search OR user_id = :search_id)";
            $params[':search'] = "%$search%";
            $params[':search_id'] = $search;
            break;
    }
}
if ($filterType !== 'all') {
    $whereClause[] = "user_type = :user_type";
    $params[':user_type'] = $filterType;
}
$whereSQL = !empty($whereClause) ? 'WHERE ' . implode(' AND ', $whereClause) : '';
$countSQL = "SELECT COUNT(*) as total FROM users $whereSQL";
$stmt = $pdo->prepare($countSQL);
$stmt->execute($params);
$totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalUsers / $usersPerPage);
$sql = "SELECT * FROM users $whereSQL ORDER BY $sortBy $sortOrder LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $usersPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
$loggedInUserEmail = $_SESSION['email'] ?? $_SESSION['user_email'];
$isSuperAdmin = $loggedInUserEmail === 'admin@gmail.com';
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>EasyPark Admin Dashboard</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/font-awesome.min.css">
  <style>
    /* ...existing styles, consider moving to a CSS file... */
  </style>
</head>
<body>
  <!-- Sidebar -->
  <nav class="sidebar d-flex flex-column position-fixed p-3" id="sidebarMenu">
    <!-- ...sidebar content... -->
  </nav>
  <!-- Main Content -->
  <div id="main-content">
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
      <!-- ...navbar content... -->
    </nav>
    <div class="container-fluid py-4">
      <div class="section-card">
        <?php include __DIR__ . '/dashboard-cards.php'; ?>
        <?php include __DIR__ . '/parking-slots-section.php'; ?>
        <?php include __DIR__ . '/users-section.php'; ?>
        <?php include __DIR__ . '/transactions-section.php'; ?>
      </div>
    </div>
  </div>
</body>
</html>
