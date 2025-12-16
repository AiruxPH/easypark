<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'client') {
    header("Location: index.php");
    exit();
}

require_once 'includes/db.php';
require_once 'includes/constants.php';  // Assuming this exists, based on other files
require_once 'includes/functions.php'; // For logActivity if needed, though we are just reading here

$user_id = $_SESSION['user_id'];

// --- Search & Pagination & Filter ---
$search = trim($_GET['search'] ?? '');
$actionFilter = $_GET['action_filter'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15; // slightly different per page for client
$offset = ($page - 1) * $perPage;

// Build Query
$where = ["l.user_id = :user_id"];
$params = [':user_id' => $user_id];

if ($search) {
    $where[] = "(l.action LIKE :search OR l.details LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($actionFilter) {
    if ($actionFilter === 'login_logout') {
        $where[] = "(l.action = 'login' OR l.action = 'logout')";
    } else {
        $where[] = "l.action LIKE :action_filter";
        $params[':action_filter'] = "%$actionFilter%";
    }
}

$whereClause = 'WHERE ' . implode(' AND ', $where);

// Get Total Count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs l $whereClause");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

// Get Logs
$sql = "SELECT l.* 
        FROM activity_logs l 
        $whereClause 
        ORDER BY l.created_at DESC 
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>My Activity Logs - EasyPark</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }
        .bg-car {
            background-image: url('images/bg-car.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
        }

        /* Glassmorphism Card (similar to Reservations/Bookings) */
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
            transition: background-color 0.2s;
        }

        .badge-action {
            font-size: 0.85rem;
            padding: 0.4em 0.8em;
            width: 100px;
            /* Fixed width for alignment */
            display: inline-block;
            text-align: center;
        }
        
        .btn-back {
            background: transparent;
            color: #fff;
            border: 1px solid rgba(255,255,255,0.5);
            transition: all 0.3s;
        }
        .btn-back:hover {
            background: rgba(255,255,255,0.2);
            color: #fff;
            text-decoration: none;
        }
    </style>
</head>

<body class="bg-car">
    <?php include 'includes/client_navbar.php'; ?>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-white mb-0" style="text-shadow: 0 2px 4px rgba(0,0,0,0.6);">
                <i class="fa fa-history mr-2"></i> My Activity History
            </h2>
            <a href="profile.php" class="btn btn-back rounded-pill px-4">
                <i class="fa fa-arrow-left mr-2"></i> Back to Profile
            </a>
        </div>

        <div class="glass-card p-4">
            <!-- Search & Filter -->
            <form method="GET" class="mb-4">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white border-right-0"><i
                                        class="fa fa-search text-muted"></i></span>
                            </div>
                            <input type="text" name="search" class="form-control border-left-0"
                                placeholder="Search details..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                         <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white border-right-0"><i class="fa fa-filter text-muted"></i></span>
                            </div>
                            <select name="action_filter" class="form-control border-left-0">
                                <option value="">All Actions</option>
                                <option value="login_logout" <?= $actionFilter === 'login_logout' ? 'selected' : '' ?>>Login / Logout</option>
                                <option value="reservation" <?= $actionFilter === 'reservation' ? 'selected' : '' ?>>Reservations</option>
                                <option value="wallet" <?= $actionFilter === 'wallet' ? 'selected' : '' ?>>Wallet / Top-up</option>
                                <option value="profile" <?= $actionFilter === 'profile' ? 'selected' : '' ?>>Profile Updates</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button type="submit" class="btn btn-primary btn-block shadow-sm">Filter</button>
                    </div>
                    <?php if ($search || $actionFilter): ?>
                        <div class="col-md-2 mb-2">
                            <a href="activity_logs.php" class="btn btn-outline-secondary btn-block">Clear</a>
                        </div>
                    <?php endif; ?>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="thead-light">
                        <tr>
                            <th width="20%">Date & Time</th>
                            <th width="15%">Action</th>
                            <th width="65%">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($logs) > 0): ?>
                            <?php foreach ($logs as $log): ?>
                                <?php
                                // Badge logic depending on action keyword
                                $badgeClass = 'secondary';
                                $act = strtolower($log['action']);
                                if (strpos($act, 'login') !== false)
                                    $badgeClass = 'info';
                                elseif (strpos($act, 'logout') !== false)
                                    $badgeClass = 'light border';
                                elseif (strpos($act, 'reservation') !== false)
                                    $badgeClass = 'primary';
                                elseif (strpos($act, 'cancel') !== false || strpos($act, 'delete') !== false)
                                    $badgeClass = 'danger';
                                elseif (strpos($act, 'update') !== false || strpos($act, 'edit') !== false)
                                    $badgeClass = 'warning';
                                elseif (strpos($act, 'create') !== false || strpos($act, 'add') !== false || strpos($act, 'topup') !== false)
                                    $badgeClass = 'success';
                                ?>
                                <tr>
                                    <td>
                                        <div class="font-weight-bold text-dark">
                                            <?= date('M d, Y', strtotime($log['created_at'])) ?>
                                        </div>
                                        <small class="text-muted">
                                            <?= date('h:i A', strtotime($log['created_at'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $badgeClass ?> badge-action shadow-sm">
                                            <?= ucfirst(str_replace('_', ' ', $log['action'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-secondary">
                                        <?= htmlspecialchars($log['details']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center py-5 text-muted">
                                    <i class="fa fa-info-circle fa-2x mb-3 d-block text-gray-300"></i>
                                    No activity logs found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4 mb-2">
                    <ul class="pagination pagination-sm justify-content-center">
                        <?php
                        $q = $_GET; // preserve search params
                        function buildLink($p, $q)
                        {
                            $q['page'] = $p;
                            return '?' . http_build_query($q);
                        }
                        ?>

                        <!-- First & Prev -->
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= buildLink(1, $q) ?>">First</a>
                        </li>
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= buildLink($page - 1, $q) ?>">Prev</a>
                        </li>

                        <!-- Window Loop -->
                        <?php
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                        for ($i = $start; $i <= $end; $i++):
                            ?>
                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                <a class="page-link" href="<?= buildLink($i, $q) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <!-- Next & Last -->
                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= buildLink($page + 1, $q) ?>">Next</a>
                        </li>
                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= buildLink($totalPages, $q) ?>">Last</a>
                        </li>
                    </ul>

                    <!-- Jump to Page -->
                    <form action="" method="GET" class="form-inline justify-content-center mt-2">
                        <?php if ($search): ?>
                            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                        <?php endif; ?>

                        <label class="mr-2 text-muted small">Jump to:</label>
                        <input type="number" name="page" min="1" max="<?= $totalPages ?>"
                            class="form-control form-control-sm border-secondary" style="width: 70px;"
                            placeholder="<?= $page ?>">
                        <button type="submit" class="btn btn-sm btn-outline-primary ml-1">Go</button>
                    </form>
                </nav>
            <?php endif; ?>

        </div>
    </div>

    <!-- Include Footer if exists, otherwise assume scripts are end of body -->
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/ef9baa832e.js" crossorigin="anonymous"></script>
</body>

</html>