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
<nav id="navbar" class="navbar navbar-expand-lg bg-image-dark navbar-dark sticky-top w-100 px-3">
    <a id="opp" class="navbar-brand" href="index.php">
        <h1 class="custom-size 75rem">EASYPARK</h1>
    </a>
    <button class="navbar-toggler ml-auto" type="button" data-toggle="collapse" data-target="#collapsibleNavbar">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="collapsibleNavbar">
        <ul id="opp" class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link <?= ($current_page == 'index.php') ? 'active' : '' ?>" href="index.php">Home</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($current_page == 'reservations.php') ? 'active' : '' ?>"
                    href="reservations.php">Reserve</a>
            </li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'bookings.php') ? 'active' : '' ?>" href="bookings.php">My
                        Bookings</a>
                </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link <?= ($current_page == 'how-it-works.php') ? 'active' : '' ?>"
                    href="how-it-works.php">How It Works</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($current_page == 'faq.php') ? 'active' : '' ?>" href="faq.php">FAQ</a>
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
                <li class="nav-item dropdown mr-3">
                    <a class="nav-link dropdown-toggle position-relative" href="#" id="alertsDropdown" role="button"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-bell fa-fw" style="font-size: 1.2rem;"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="badge badge-danger badge-counter position-absolute"
                                style="top: 0; right: 0; font-size: 0.6rem;"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in p-0"
                        aria-labelledby="alertsDropdown" style="width: 320px; max-height: 400px; overflow-y: auto;">
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
                                        break; // Matches 'error' enum
                                    case 'info':
                                    default:
                                        $iconClass = 'info text-white';
                                        $iconBg = 'bg-info';
                                        break;
                                }
                                ?>
                                <a class="dropdown-item d-flex align-items-center py-2 border-bottom notification-item <?= $bgClass ?>"
                                    href="<?= $notif['link'] ? htmlspecialchars($notif['link']) : '#' ?>"
                                    data-id="<?= $notif['notification_id'] ?>"
                                    onclick="markAsRead(event, <?= $notif['notification_id'] ?>, '<?= $notif['link'] ?>')">
                                    <div class="mr-3">
                                        <div class="icon-circle <?= $iconBg ?> d-flex align-items-center justify-content-center rounded-circle"
                                            style="width: 35px; height: 35px;">
                                            <i class="fas fa-<?= $iconClass ?>"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="small text-gray-500">
                                            <?= date('F j, Y, g:i a', strtotime($notif['created_at'])) ?>
                                        </div>
                                        <div class="font-weight-bold text-truncate" style="max-width: 200px;">
                                            <?= htmlspecialchars($notif['title']) ?>
                                        </div>
                                        <div class="small text-dark text-truncate" style="max-width: 200px;">
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
                        style="padding: 0.375rem 1rem;">
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
                        style="font-size: 1rem; color: #333; font-weight: 700; text-decoration: none;">
                        🪙 <span style="color: <?= $coinColor ?>;"><?= number_format($coins, 2) ?></span>
                    </a>
                </li>
                <li class="nav-item d-flex align-items-center ml-2">
                    <a href="logout.php" class="btn btn-danger btn-sm shadow-sm" style="padding: 0.375rem 0.75rem;">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            <?php else: ?>
                <li class="nav-item ml-2">
                    <a class="nav-link btn btn-primary px-4 text-white" href="login.php">Login/Sign Up</a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
    </div>
</nav>

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
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
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

    // Mark as Read JS
    function markAsRead(event, id, link) {
        event.preventDefault(); // Stop default link behavior momentarily

        fetch('mark_read.php', {
            method: 'POST',
            body: JSON.stringify({ notification_id: id }),
            headers: { 'Content-Type': 'application/json' }
        }).then(() => {
            // Find badge and decrement
            const item = event.currentTarget;
            item.classList.remove('bg-light');
            item.classList.add('bg-white');

            // Redirect if link exists
            if (link && link !== "#" && link !== "") {
                window.location.href = link;
            }
        }).catch(err => console.error(err));
    }

    function markAllRead(event) {
        event.preventDefault();
        fetch('mark_read.php', {
            method: 'POST',
            body: JSON.stringify({}),
            headers: { 'Content-Type': 'application/json' }
        }).then(() => {
            location.reload(); // Simple reload to clear badges
        });
    }
</script>