<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php'; // Ensure DB connection

// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);

// Initial profile pic logic
$profilePic = 'images/default.jpg';
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    // Check if $pdo is available, if not, it should have been included by db.php
    if (isset($pdo)) {
        $stmt = $pdo->prepare('SELECT image FROM users WHERE user_id = ?');
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && !empty($user['image']) && file_exists(__DIR__ . '/../images/' . $user['image'])) {
            $profilePic = 'images/' . $user['image'];
        }
    }
}
?>
<script src="js/ef9baa832e.js" crossorigin="anonymous"></script>
<nav id="navbar" class="navbar navbar-expand-lg bg-image-dark navbar-dark sticky-top w-100 px-3">
    <a id="opp" class="navbar-brand" href="index.php" data-toggle="tooltip" title="Back to Home"
        data-placement="bottom">
        <h1 class="custom-size 75rem">EASYPARK</h1>
    </a>
    <button class="navbar-toggler ml-auto" type="button" data-toggle="collapse" data-target="#collapsibleNavbar">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="collapsibleNavbar">
        <ul id="opp" class="navbar-nav">
            <!-- Home link removed (redundant with logo) -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'reservations.php') ? 'active' : '' ?>" href="reservations.php"
                        data-toggle="tooltip" title="Book a parking slot" data-placement="bottom">Reserve</a>
                </li>
            <?php endif; ?>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'bookings.php') ? 'active' : '' ?>" href="bookings.php"
                        data-toggle="tooltip" title="View your reservation history" data-placement="bottom">My Bookings</a>
                </li>
            <?php endif; ?>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'how-it-works.php') ? 'active' : '' ?>" href="how-it-works.php"
                        data-toggle="tooltip" title="Learn how to use EasyPark" data-placement="bottom">How It Works</a>
                </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link <?= ($current_page == 'faq.php') ? 'active' : '' ?>" href="faq.php"
                    data-toggle="tooltip" title="Frequently Asked Questions" data-placement="bottom">FAQ</a>
            </li>

            <?php if (isset($_SESSION['user_id'])): ?>
                <?php
                // Include Notifications Helper
                require_once __DIR__ . '/notifications.php';

                // Fetch Notifications
                $unreadCount = 0;
                $notifications = [];
                if (isset($pdo)) {
                    $unreadCount = countUnreadNotifications($pdo, $_SESSION['user_id']);
                    $notifications = getUnreadNotifications($pdo, $_SESSION['user_id'], 5);
                }
                ?>
                <!-- Server Clock (Hover to view) -->
                <li class="nav-item d-flex align-items-center mr-3 text-white small position-relative group-clock">
                    <div class="d-flex align-items-center" style="cursor: pointer;" title="Hover to see local time">
                        <i class="fas fa-clock text-white-50"></i>
                        <span id="server-clock" class="ml-2 text-white bg-dark rounded px-2 py-1 shadow-sm"
                            style="display: none; position: absolute; top: 100%; right: 0; white-space: nowrap; margin-top: 10px; z-index: 1050;">
                            Loading...
                        </span>
                    </div>
                </li>
                <style>
                    .group-clock:hover #server-clock {
                        display: block !important;
                    }
                </style>

                <li class="nav-item dropdown mr-3">
                    <a class="nav-link dropdown-toggle position-relative" href="#" id="alertsDropdown" role="button"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Notifications">
                        <i class="fas fa-bell fa-fw" style="font-size: 1.2rem;"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="badge badge-danger badge-counter position-absolute"
                                style="top: 0; right: 0; font-size: 0.6rem;"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in p-0"
                        aria-labelledby="alertsDropdown" style="width: 25rem; max-height: 400px; overflow-y: auto;">
                        <h6 class="dropdown-header bg-primary text-white py-2 px-3 m-0 border-bottom">
                            Notifications Center
                        </h6>
                        <?php if (empty($notifications)): ?>
                            <a class="dropdown-item d-flex align-items-center py-3 text-muted justify-content-center" href="#">
                                <small>No new notifications</small>
                            </a>
                        <?php else: ?>
                            <?php foreach ($notifications as $notif): ?>
                                <?php
                                $bgClass = $notif['is_read'] ? 'bg-white' : 'bg-light';
                                $iconClass = 'info-circle text-primary';
                                $iconBg = 'bg-primary';
                                switch ($notif['type']) {
                                    case 'success':
                                        $iconClass = 'check text-white';
                                        $iconBg = 'bg-success';
                                        break;
                                    case 'warning':
                                        $iconClass = 'exclamation-triangle text-white';
                                        $iconBg = 'bg-warning';
                                        break;
                                    case 'error':
                                        $iconClass = 'times text-white';
                                        $iconBg = 'bg-danger';
                                        break;
                                    case 'info':
                                    default:
                                        $iconClass = 'info text-white';
                                        $iconBg = 'bg-info';
                                        break;
                                }
                                // Ensure link is not null
                                $link = $notif['link'] ? $notif['link'] : '';
                                ?>
                                <a class="dropdown-item d-flex align-items-center py-3 border-bottom notification-item <?= $bgClass ?>"
                                    href="#" data-id="<?= $notif['notification_id'] ?>" data-link="<?= htmlspecialchars($link) ?>"
                                    data-title="<?= htmlspecialchars($notif['title']) ?>"
                                    data-message="<?= htmlspecialchars($notif['message']) ?>"
                                    onclick="handleNotificationClick(event)">
                                    <div class="mr-3">
                                        <div class="icon-circle <?= $iconBg ?> d-flex align-items-center justify-content-center rounded-circle"
                                            style="width: 40px; height: 40px;">
                                            <i class="fas fa-<?= $iconClass ?>"></i>
                                        </div>
                                    </div>
                                    <div style="flex: 1; min-width: 0;">
                                        <div class="small text-gray-500">
                                            <?= date('F j, Y, g:i a', strtotime($notif['created_at'])) ?>
                                        </div>
                                        <div class="font-weight-bold text-truncate" style="font-size: 0.95rem;">
                                            <?= htmlspecialchars($notif['title']) ?>
                                        </div>
                                        <div class="small text-dark text-truncate" style="font-size: 0.85rem;">
                                            <?= htmlspecialchars($notif['message']) ?>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <a class="dropdown-item text-center small text-gray-500 py-2 bg-light" href="#"
                            onclick="markAllRead(event)">Mark all as Read</a>
                    </div>
                </li>

                <li class="nav-item">
                    <a class="btn btn-primary d-flex align-items-center" href="profile.php" id="accountButton"
                        style="padding: 0.375rem 1rem;" data-toggle="tooltip" title="Manage your profile"
                        data-placement="bottom">
                        <img src="<?= htmlspecialchars($profilePic) ?>" alt="Profile"
                            style="width:32px;height:32px;object-fit:cover;border-radius:50%;border:2px solid #fff;margin-right:8px;">
                        My Account (<?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>)
                    </a>
                </li>
                <?php
                // Fetch user coins
                $coins = 0.00;
                if (isset($_SESSION['user_id']) && isset($pdo)) {
                    $stmt = $pdo->prepare('SELECT coins FROM users WHERE user_id = ?');
                    $stmt->execute([$_SESSION['user_id']]);
                    $coins = $stmt->fetchColumn() ?: 0.00;
                }
                $coinColor = ($coins >= 0) ? '#28a745' : '#dc3545';
                ?>
                <li class="nav-item d-flex align-items-center ml-2">
                    <a href="wallet.php" class="badge badge-light px-3 py-2 border shadow-sm"
                        style="font-size: 1rem; color: #333; font-weight: 700; text-decoration: none;" data-toggle="tooltip"
                        title="View Wallet Balance" data-placement="bottom">
                        🪙 <span style="color: <?= $coinColor ?>;"><?= number_format($coins, 2) ?></span>
                    </a>
                </li>
                <li class="nav-item d-flex align-items-center ml-2">
                    <a href="logout.php" class="btn btn-danger btn-sm shadow-sm" style="padding: 0.375rem 0.75rem;"
                        data-toggle="tooltip" title="Sign Out securely" data-placement="bottom">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            <?php else: ?>
                <li class="nav-item ml-2">
                    <a class="nav-link btn btn-primary px-4 text-white" href="login.php" data-toggle="tooltip"
                        title="Log in or Register" data-placement="bottom">Login/Sign Up</a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
    </div>
    </div>
</nav>

<!-- Audio for Notifications -->
<audio id="notificationSound" src="https://cdn.freesound.org/previews/536/536108_11566085-lq.mp3"
    preload="auto"></audio>

<!-- Notification Details Modal -->
<div class="modal fade" id="notificationModal" tabindex="-1" role="dialog" aria-labelledby="notificationModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="notificationModalLabel">Notification Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h6 id="notif-modal-title" class="font-weight-bold mb-2">Subject</h6>
                <p id="notif-modal-message" class="text-dark">Message goes here...</p>
            </div>
            <div class="modal-footer">
                <a href="#" id="notif-modal-link" class="btn btn-primary" style="display: none;">Go to Page</a>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Navbar Base Styles */
    #navbar {
        position: sticky;
        top: 0;
        z-index: 1030;
        background-color: rgba(0, 0, 0, 0.3);
        /* The underlying "scrolled" color */
        /* CSS Variable for opacity, defaults to 1 (fully visible image) */
        --nav-image-opacity: 1;
    }

    /* Pseudo-element for the Background Image */
    #navbar::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: -1;
        background-image: url('images/nav-bg.jpg');
        background-size: cover;
        background-position: top center;
        background-repeat: no-repeat;
        opacity: var(--nav-image-opacity);
        transition: opacity 0.1s linear;
        /* Smooths out scroll updates slightly */
        pointer-events: none;
        /* Let clicks pass through if needed */
    }

    /* Keep brand/nav text colors */
    .navbar-dark .navbar-brand,
    .navbar-dark .navbar-nav .nav-link {
        color: #fff;
    }

    .navbar-dark .navbar-brand:hover,
    .navbar-dark .navbar-nav .nav-link:hover {
        color: #ccc;
    }

    .navbar-nav .nav-item {
        margin-right: 15px;
    }

    /* Custom size class from original dashboard */
    .custom-size {
        color: #ffc107;
        transition: text-shadow 0.3s ease-in-out, color 0.3s ease-in-out;
    }

    .custom-size:hover {
        text-shadow: 0 0 10px #ffd700, 0 0 20px #ffd700, 0 0 30px #ffd700;
        color: white;
    }

    /* FLOATING NOTIFICATION CARD */
    .dropdown-list {
        background: rgba(30, 30, 30, 0.95) !important;
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 20px;
        overflow: hidden;
        margin-top: 15px !important;
        /* Detach from navbar */
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.6) !important;
    }

    /* ARROW (Caret pointing up) */
    .nav-link[aria-expanded="true"]::after {
        content: '';
        position: absolute;
        bottom: -15px;
        /* Position below the bell */
        left: 50%;
        transform: translateX(-50%);
        border-width: 0 10px 10px 10px;
        border-style: solid;
        border-color: transparent transparent rgba(240, 165, 0, 0.9) transparent;
        /* Match header color */
        z-index: 1050;
        animation: fadeInUp 0.2s ease-out;
    }

    /* Custom Scrollbar for Dropdown */
    .dropdown-list::-webkit-scrollbar {
        width: 6px;
    }

    .dropdown-list::-webkit-scrollbar-track {
        background: transparent;
    }

    .dropdown-list::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 10px;
    }

    .dropdown-list::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.4);
    }

    .dropdown-header {
        background: rgba(240, 165, 0, 0.9) !important;
        /* Primary color */
        color: #000 !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .notification-item {
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        transition: background 0.2s;
    }

    .notification-item:hover {
        background: rgba(255, 255, 255, 0.1) !important;
        text-decoration: none;
    }

    .notification-item .text-gray-500 {
        color: #aaa !important;
    }

    .notification-item .font-weight-bold {
        color: #fff !important;
    }

    .notification-item .text-dark {
        color: #ccc !important;
    }

    .notification-item.bg-light {
        background: rgba(255, 255, 255, 0.05) !important;
        /* Unread */
    }

    .notification-item.bg-white {
        background: transparent !important;
        /* Read */
    }

    /* Mark all as read button */
    a.dropdown-item.text-center {
        color: #f0a500 !important;
        background: rgba(0, 0, 0, 0.2) !important;
        transition: all 0.2s;
    }

    a.dropdown-item.text-center:hover {
        background: rgba(0, 0, 0, 0.4) !important;
        color: #fff !important;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize Bootstrap Tooltips
        $('[data-toggle="tooltip"]').tooltip();

        const navbar = document.getElementById('navbar');

        function updateNavbarOpacity() {
            const scrollY = window.scrollY;
            const fadeDistance = 300; // Pixels to scroll before image is fully transparent

            // Calculate opacity: 1 at top, 0 at fadeDistance
            let opacity = 1 - (scrollY / fadeDistance);

            // Clamp between 0 and 1
            opacity = Math.max(0, Math.min(1, opacity));

            // Apply to CSS variable
            navbar.style.setProperty('--nav-image-opacity', opacity);
        }

        // Listen for scroll events
        window.addEventListener('scroll', updateNavbarOpacity);

        // Initial call
        updateNavbarOpacity();
    });

</script>
<script src="js/notifications.js"></script>

<!-- Toast Container (Fixed) -->
<div id="toast-container" aria-live="polite" aria-atomic="true"
    style="position: fixed; bottom: 20px; left: 20px; z-index: 9999; pointer-events: none;">
    <!-- Toasts will be appended here -->
</div>
<style>
    /* ANIMATIONS */
    @keyframes pulse-red {
        0% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
        }

        70% {
            transform: scale(1.1);
            box-shadow: 0 0 0 6px rgba(220, 53, 69, 0);
        }

        100% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
        }
    }

    .badge-pulse {
        animation: pulse-red 2s infinite;
    }

    /* UNREAD DOT */
    .unread-indicator {
        width: 8px;
        height: 8px;
        background-color: #00c4cc;
        /* Cyan/Teal for contrast */
        border-radius: 50%;
        margin-right: 10px;
        box-shadow: 0 0 5px #00c4cc;
    }

    /* Glass Toast Enhanced */
    .glass-toast {
        background: rgba(20, 20, 20, 0.85) !important;
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.15);
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        border-radius: 16px;
        color: #fff;
        overflow: hidden;
        position: relative;
    }

    .toast-progress {
        position: absolute;
        bottom: 0;
        left: 0;
        height: 3px;
        background: linear-gradient(90deg, #f0a500, #ffc107);
        width: 100%;
        animation: toastTimer 5s linear forwards;
    }

    @keyframes slideIn {
        from {
            transform: translateX(-100%) scale(0.9);
            opacity: 0;
        }

        to {
            transform: translateX(0) scale(1);
            opacity: 1;
        }
    }

    @keyframes fadeOut {
        from {
            opacity: 1;
            transform: scale(1);
        }

        to {
            opacity: 0;
            transform: scale(0.95);
        }
    }

    @keyframes toastTimer {
        from {
            width: 100%;
        }

        to {
            width: 0%;
        }
    }
</style>
<script>
    // Local Time Clock (Device Time)
    function tick() {
        const now = new Date();

        const options = {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        const timeString = now.toLocaleString('en-US', options);

        const el = document.getElementById('server-clock');
        if (el) el.innerHTML = '<i class="fas fa-clock"></i> ' + timeString;
    }

    setInterval(tick, 1000);
    tick();
</script>