<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require_once 'includes/db.php';
require_once 'includes/notifications.php';

$user_id = $_SESSION['user_id'];

// Fetch all notifications using helper
$allNotifications = getAllNotifications($pdo, $user_id);

// Get user profile pic (standard boilerplate for consistency)
$stmt = $pdo->prepare('SELECT image FROM users WHERE user_id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$profilePic = (!empty($user['image']) && file_exists('images/' . $user['image'])) ? 'images/' . $user['image'] : 'images/default.jpg';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>All Notifications - EasyPark</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="images/favicon.png" type="image/png">
    <style>
        .bg-car {
            background-image: url('images/bg-car.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
        }

        /* Glassmorphism Panel */
        .glass-panel {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
        }

        .table-glass {
            color: #fff;
            margin-bottom: 0;
        }

        .table-glass thead th {
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
            border-top: none;
            color: #f0a500;
            font-weight: 600;
            cursor: pointer;
        }

        .table-glass td,
        .table-glass th {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            vertical-align: middle;
        }

        .table-glass tbody tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        /* Unread Highlight */
        .row-unread {
            background: rgba(255, 193, 7, 0.1) !important;
            border-left: 4px solid #ffc107;
        }

        .icon-circle {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        /* Search input style */
        .form-control-glass {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
        }

        .form-control-glass:focus {
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
            box-shadow: none;
            border-color: #f0a500;
        }
    </style>
</head>

<body class="bg-car">
    <?php include 'includes/client_navbar.php'; ?>
    <div class="container py-5">
        <div class="d-flex align-items-center mb-4">
            <h2 class="text-white mb-0" style="text-shadow: 0 2px 4px rgba(0,0,0,0.8);">Notifications</h2>
            <span class="badge badge-light ml-3 px-3 py-2" style="font-size: 1rem; border-radius: 20px; color: #333;">
                Total: <?= count($allNotifications) ?>
            </span>
        </div>

        <div class="glass-panel p-3 mb-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between">
                <div class="form-inline mb-2 mb-md-0">
                    <label for="typeFilter" class="mr-2 font-weight-bold text-white">Filter:</label>
                    <select id="typeFilter" class="form-control form-control-sm mr-2 form-control-glass">
                        <option value="">All Types</option>
                        <option value="success">Success</option>
                        <option value="warning">Warning</option>
                        <option value="error">Error</option>
                        <option value="info">Info</option>
                    </select>

                    <select id="statusFilter" class="form-control form-control-sm mr-2 form-control-glass">
                        <option value="">All Status</option>
                        <option value="unread">Unread</option>
                        <option value="read">Read</option>
                    </select>
                </div>
                <div class="form-inline">
                    <input type="text" id="searchInput" class="form-control form-control-sm mr-2 form-control-glass"
                        placeholder="Search notifications...">
                    <button class="btn btn-sm btn-outline-light" id="markAllReadBtn">
                        <i class="fas fa-check-double mr-1"></i> Mark All Read
                    </button>
                </div>
            </div>
        </div>

        <div class="glass-panel p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-glass align-middle" id="notifTable">
                    <thead>
                        <tr>
                            <th style="width: 50px;">Type</th>
                            <th>Details</th>
                            <th style="width: 150px;">Date</th>
                            <th style="width: 100px;" class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($allNotifications) === 0): ?>
                            <tr>
                                <td colspan="4" class="text-center py-5 text-white-50">You have no notifications.</td>
                            </tr>
                        <?php else:
                            foreach ($allNotifications as $n):
                                $bgClass = ($n['is_read'] == 0) ? 'row-unread' : '';

                                // Icon logic
                                $icon = 'info-circle';
                                $iconColor = 'text-info';
                                if ($n['type'] === 'success') {
                                    $icon = 'check-circle';
                                    $iconColor = 'text-success';
                                } elseif ($n['type'] === 'warning') {
                                    $icon = 'exclamation-triangle';
                                    $iconColor = 'text-warning';
                                } elseif ($n['type'] === 'error') {
                                    $icon = 'times-circle';
                                    $iconColor = 'text-danger';
                                }

                                $rowJson = htmlspecialchars(json_encode($n));
                                ?>
                                <tr class="notif-row <?= $bgClass ?>" data-type="<?= $n['type'] ?>"
                                    data-read="<?= $n['is_read'] ?>" data-json='<?= $rowJson ?>'>
                                    <td class="text-center">
                                        <div class="icon-circle bg-dark shadow-sm mx-auto">
                                            <i class="fas fa-<?= $icon ?> <?= $iconColor ?>"></i>
                                        </div>
                                    </td>
                                    <td>
                                        <h6 class="mb-1 font-weight-bold text-white notif-title">
                                            <?= htmlspecialchars($n['title']) ?>
                                        </h6>
                                        <p class="mb-0 small text-white-50 notif-message"><?= htmlspecialchars($n['message']) ?>
                                        </p>
                                    </td>
                                    <td class="small text-white-50">
                                        <?= date('M d, Y h:i A', strtotime($n['created_at'])) ?>
                                    </td>
                                    <td class="text-right">
                                        <?php if ($n['is_read'] == 0): ?>
                                            <button class="btn btn-sm btn-outline-warning btn-mark-read"
                                                data-id="<?= $n['notification_id'] ?>" title="Mark as Read">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if (!empty($n['link']) && $n['link'] !== '#'): ?>
                                            <a href="<?= htmlspecialchars($n['link']) ?>" class="btn btn-sm btn-outline-info"
                                                title="View Link">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <a href="dashboard.php" class="btn btn-secondary mt-4">Go back to Home</a>
    </div>

    <!-- Scripts -->
    <script src="js/jquery.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const table = document.getElementById('notifTable');
            const typeFilter = document.getElementById('typeFilter');
            const statusFilter = document.getElementById('statusFilter');
            const searchInput = document.getElementById('searchInput');
            const rows = table.querySelectorAll('.notif-row');

            function filterRows() {
                const type = typeFilter.value.toLowerCase();
                const status = statusFilter.value; // 'unread' or 'read'
                const search = searchInput.value.toLowerCase();

                rows.forEach(row => {
                    const rowType = row.getAttribute('data-type');
                    const isRead = row.getAttribute('data-read'); // 0 or 1

                    const title = row.querySelector('.notif-title').textContent.toLowerCase();
                    const msg = row.querySelector('.notif-message').textContent.toLowerCase();

                    let show = true;

                    // Type Filter
                    if (type && rowType !== type) show = false;

                    // Status Filter
                    if (status === 'unread' && isRead !== '0') show = false;
                    if (status === 'read' && isRead !== '1') show = false;

                    // Search
                    if (search && !title.includes(search) && !msg.includes(search)) show = false;

                    row.style.display = show ? '' : 'none';
                });
            }

            typeFilter.addEventListener('change', filterRows);
            statusFilter.addEventListener('change', filterRows);
            searchInput.addEventListener('input', filterRows);

            // Mark One Read
            document.querySelectorAll('.btn-mark-read').forEach(btn => {
                btn.addEventListener('click', function () {
                    const id = this.getAttribute('data-id');
                    const row = this.closest('tr');

                    fetch('mark_read.php', {
                        method: 'POST',
                        body: JSON.stringify({ notification_id: id }),
                        headers: { 'Content-Type': 'application/json' }
                    })
                        .then(() => {
                            // UI update
                            row.classList.remove('row-unread');
                            row.setAttribute('data-read', '1');
                            this.remove(); // Remove the check button
                            // Optionally update navbar badge? A reload is simpler for consistency or we accept it's "stale" until nav polling hits.
                        })
                        .catch(err => console.error(err));
                });
            });

            // Mark All Read
            document.getElementById('markAllReadBtn').addEventListener('click', function () {
                if (!confirm('Mark all notifications as read?')) return;

                fetch('mark_read.php', {
                    method: 'POST',
                    body: JSON.stringify({}),
                    headers: { 'Content-Type': 'application/json' }
                })
                    .then(() => {
                        location.reload();
                    });
            });
        });
    </script>
</body>

</html>