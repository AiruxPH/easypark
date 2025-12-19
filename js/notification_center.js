
document.addEventListener('DOMContentLoaded', function () {
    const POLL_INTERVAL = 30000; // 30 seconds
    let lastUnreadCount = -1; // Initialize with -1 to force first update

    // Elements
    const badge = document.querySelector('.badge-counter');
    const scrollAreaCheck = document.getElementById('notification-scroll-area'); // New check target
    const toastContainer = document.getElementById('toast-container');
    const notificationSound = document.getElementById('notificationSound'); // Audio element

    // Initial Fetch
    fetchNotifications();

    // Start Polling
    setInterval(fetchNotifications, POLL_INTERVAL);

    function fetchNotifications() {
        if (!scrollAreaCheck) return; // Safety check if user not logged in or element missing

        fetch('fetch_notifications.php?limit=20')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateBadge(data.unread_count);
                    updateNotificationList(data.notifications);

                    // If count INCREASED (and it's not the initial load), show a toast and play sound
                    if (lastUnreadCount !== -1 && data.unread_count > lastUnreadCount) {
                        // Find the newest unread notification to show
                        const newest = data.notifications.find(n => n.is_read == 0);
                        if (newest) {
                            showToast(newest);
                            if (notificationSound) {
                                notificationSound.play().catch(e => console.log('Audio play failed (user interaction needed first):', e));
                            }
                        }
                    }
                    lastUnreadCount = data.unread_count;
                }
            })
            .catch(err => console.error('Notification Poll Error:', err));
    }

    function updateBadge(count) {
        const bell = document.getElementById('alertsTrigger');
        if (!bell) return;

        let badgeEl = bell.querySelector('.badge-counter');

        if (count > 0) {
            if (!badgeEl) {
                // Create if doesn't exist
                badgeEl = document.createElement('span');
                badgeEl.className = 'badge badge-danger badge-counter position-absolute badge-pulse';
                badgeEl.style.cssText = 'top: 0; right: 0; font-size: 0.6rem;';
                bell.appendChild(badgeEl);
            } else {
                badgeEl.classList.add('badge-pulse');
            }
            badgeEl.innerText = count > 9 ? '9+' : count;
        } else {
            if (badgeEl) badgeEl.remove();
        }
    }

    function updateNotificationList(notifications) {
        // Target the inner scroll area
        const scrollArea = document.getElementById('notification-scroll-area');
        if (!scrollArea) {
            console.error('CRITICAL: #notification-scroll-area NOT FOUND');
            return;
        }

        console.log('Rendering notifications:', notifications.length);
        scrollArea.innerHTML = ''; // Clear current list

        if (notifications.length === 0) {
            const noMsg = document.createElement('div');
            noMsg.className = 'd-flex align-items-center py-5 text-muted justify-content-center flex-column';
            noMsg.innerHTML = '<i class="far fa-bell-slash fa-2x mb-2 opacity-50"></i><small>No new notifications</small>';
            scrollArea.appendChild(noMsg);
        } else {
            try {
                // 1. Sort notifications safely
                notifications.sort((a, b) => {
                    const da = new Date(a.created_at.replace(' ', 'T'));
                    const db = new Date(b.created_at.replace(' ', 'T'));
                    return db - da; // Descending
                });

                // 2. Group by Date Label
                const groups = [];
                let currentGroup = null;

                notifications.forEach(notif => {
                    const label = getRelativeDateLabel(notif.created_at);
                    if (!currentGroup || currentGroup.label !== label) {
                        currentGroup = { label: label, items: [] };
                        groups.push(currentGroup);
                    }
                    currentGroup.items.push(notif);
                });

                console.log('Grouped notifications:', groups);

                // 3. Render Groups
                groups.forEach(group => {
                    // Render Header
                    const header = document.createElement('div');
                    header.className = 'px-4 text-white-50 font-weight-bold small mt-3 mb-2 text-uppercase';
                    header.innerHTML = group.label;
                    scrollArea.appendChild(header);

                    // Render Items
                    group.items.forEach(notif => {
                        const el = createNotificationElement(notif);
                        scrollArea.appendChild(el);
                        console.log('Appended item:', notif.notification_id);
                    });
                });
            } catch (e) {
                console.error('Error rendering notification list:', e);
            }
        }
    }

    function getRelativeDateLabel(dateString) {
        // Fix for Safari/Firefox: Replace space with T for ISO-8601 compliance if needed
        // Assuming format is 'YYYY-MM-DD HH:MM:SS' logic
        const safeDateString = dateString.replace(' ', 'T');
        const date = new Date(safeDateString);

        if (isNaN(date.getTime())) {
            // Fallback if replace didn't work or already valid
            return 'RECENT';
        }

        const now = new Date();
        const startOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const startOfYesterday = new Date(startOfToday);
        startOfYesterday.setDate(startOfYesterday.getDate() - 1);

        const notifDate = new Date(date.getFullYear(), date.getMonth(), date.getDate());

        if (notifDate.getTime() === startOfToday.getTime()) {
            return 'TODAY';
        } else if (notifDate.getTime() === startOfYesterday.getTime()) {
            return 'YESTERDAY';
        } else {
            // Return e.g. "DEC 18, 2025"
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).toUpperCase();
        }
    }

    function createNotificationElement(notif) {
        const bgClass = notif.is_read == 1 ? 'bg-white' : 'bg-light';
        // Add visual hierarchy for unread
        const unreadDot = notif.is_read == 0 ? '<div class="unread-indicator"></div>' : '';

        let iconClass = 'info text-white';
        let iconBg = 'bg-info';

        switch (notif.type) {
            case 'success':
                iconClass = 'check text-white';
                iconBg = 'bg-success';
                break;
            case 'warning':
                iconClass = 'exclamation-triangle text-white';
                iconBg = 'bg-warning';
                break;
            case 'error':
                iconClass = 'times text-white';
                iconBg = 'bg-danger';
                break;
        }

        const a = document.createElement('a');
        a.className = `d-block w-100 text-decoration-none px-3 py-2 border-bottom notification-item ${bgClass} text-dark`;
        a.href = '#';
        a.dataset.id = notif.notification_id;
        a.dataset.link = notif.link || '';
        a.dataset.title = notif.title;
        a.dataset.message = notif.message;
        a.onclick = handleNotificationClick; // Uses global function from navbar

        a.innerHTML = `
            <div class="mr-3">
                <div class="icon-circle ${iconBg} d-flex align-items-center justify-content-center rounded-circle" style="width: 32px; height: 32px;">
                    <i class="fas fa-${iconClass}" style="font-size: 0.8rem;"></i>
                </div>
            </div>
            <div style="flex: 1; min-width: 0;">
                <div class="d-flex align-items-center mb-1">
                    ${unreadDot}
                    <div class="small text-gray-500" style="font-size: 0.7rem;">${formatDate(notif.created_at)}</div>
                </div>
                <div class="font-weight-bold text-truncate" style="font-size: 0.85rem;">${escapeHtml(notif.title)}</div>
                <div class="small text-dark text-truncate" style="font-size: 0.75rem;">${escapeHtml(notif.message)}</div>
            </div>
        `;
        return a;
    }

    function showToast(notif) {
        if (!toastContainer) return;

        // Create toast element
        const toast = document.createElement('div');
        toast.className = 'toast show glass-toast';
        toast.role = 'alert';
        toast.style.cssText = 'min-width: 320px; margin-bottom: 15px; animation: slideIn 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);';

        let iconHtml = '<i class="fas fa-info-circle text-info fa-lg"></i>';
        if (notif.type === 'success') iconHtml = '<i class="fas fa-check-circle text-success fa-lg"></i>';
        if (notif.type === 'warning') iconHtml = '<i class="fas fa-exclamation-triangle text-warning fa-lg"></i>';
        if (notif.type === 'error') iconHtml = '<i class="fas fa-times-circle text-danger fa-lg"></i>';

        toast.innerHTML = `
            <div class="d-flex align-items-start p-3">
                <div class="mr-3 mt-1">${iconHtml}</div>
                <div class="flex-grow-1">
                    <strong class="d-block mb-1 text-white" style="font-size: 1.05rem;">${escapeHtml(notif.title)}</strong>
                    <div class="small text-white-50" style="line-height: 1.4;">${escapeHtml(notif.message)}</div>
                </div>
                <button type="button" class="ml-2 mb-1 close text-white" onclick="this.closest('.toast').remove()" style="opacity: 0.7;">
                    <span>&times;</span>
                </button>
            </div>
            <div class="toast-progress"></div>
        `;

        toastContainer.appendChild(toast);

        // Auto remove
        setTimeout(() => {
            toast.style.animation = 'fadeOut 0.5s ease-out';
            setTimeout(() => toast.remove(), 450);
        }, 5000);
    }

    function formatDate(dateString) {
        // Simple formatter, or use moment.js if available. 
        // Server sends 'Y-m-d H:i:s'.
        const d = new Date(dateString);
        return d.toLocaleString('en-US', { month: 'long', day: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true });
    }

    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    // Initial Render from Server Data (Pure JS)
    if (window.initialNotifications) {
        updateNotificationList(window.initialNotifications);
    }

    // Start Polling (Fixed: Removed duplicate interval call to undefined checkNotifications)
});

// Global functions for inline onclick handlers
window.handleNotificationClick = function (event) {
    event.preventDefault();
    const linkItem = event.currentTarget;
    const id = linkItem.getAttribute('data-id');
    const link = linkItem.getAttribute('data-link');
    const title = linkItem.getAttribute('data-title');
    const message = linkItem.getAttribute('data-message');

    // 1. Mark as Read (AJAX)
    fetch('mark_read.php', {
        method: 'POST',
        body: JSON.stringify({ notification_id: id }),
        headers: { 'Content-Type': 'application/json' }
    }).then(() => {
        // Update UI: Remove highlighting
        linkItem.classList.remove('bg-light');
        linkItem.classList.add('bg-white');

        // 2. Logic: If valid link -> Redirect. Else -> Open Modal.
        if (link && link !== "#" && link !== "") {
            window.location.href = link;
        } else {
            // Open Modal (Need jQuery for Bootstrap modal if using BS4)
            $('#notif-modal-title').text(title);
            $('#notif-modal-message').text(message);

            // Hide or update button in modal just in case
            $('#notif-modal-link').hide();

            $('#notificationModal').modal('show');
        }
    }).catch(err => console.error(err));
};

window.markAllRead = function (event) {
    if (event) event.preventDefault();
    fetch('mark_read.php', {
        method: 'POST',
        body: JSON.stringify({}),
        headers: { 'Content-Type': 'application/json' }
    }).then(() => {
        location.reload(); // Simple reload to clear badges
    });
};

