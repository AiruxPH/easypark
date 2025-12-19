/**
 * js/notifications.js
 * Handles real-time notification polling and UI updates.
 */

document.addEventListener('DOMContentLoaded', function () {
    const POLL_INTERVAL = 5000; // 5 seconds
    let lastUnreadCount = -1; // Initialize with -1 to force first update

    // Elements
    const badge = document.querySelector('.badge-counter');
    const dropdownList = document.querySelector('.dropdown-list');
    const toastContainer = document.getElementById('toast-container');
    const notificationSound = document.getElementById('notificationSound'); // Audio element

    // Initial Fetch
    fetchNotifications();

    // Start Polling
    setInterval(fetchNotifications, POLL_INTERVAL);

    function fetchNotifications() {
        if (!dropdownList) return; // Safety check if user not logged in or element missing

        fetch('fetch_notifications.php?limit=5')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateBadge(data.unread_count);
                    updateDropdown(data.notifications);

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
        const bell = document.getElementById('alertsDropdown');
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

    function updateDropdown(notifications) {
        // Target the inner scroll area
        const scrollArea = document.getElementById('notification-scroll-area');
        if (!scrollArea) return;

        scrollArea.innerHTML = ''; // Clear current list

        if (notifications.length === 0) {
            const noMsg = document.createElement('a');
            noMsg.className = 'dropdown-item d-flex align-items-center py-3 text-muted justify-content-center no-notif-msg';
            noMsg.href = '#';
            noMsg.innerHTML = '<small>No new notifications</small>';
            scrollArea.appendChild(noMsg);
        } else {
            // Sort Descending by Date
            notifications.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

            let lastDateLabel = '';

            notifications.forEach(notif => {
                const dateLabel = getRelativeDateLabel(notif.created_at);

                // sticky header if date changes
                if (dateLabel !== lastDateLabel) {
                    const header = document.createElement('h6');
                    header.className = 'dropdown-header pl-3 text-gray-500 font-weight-bold small mt-2 mb-1 border-0 bg-transparent';
                    header.style.fontSize = '0.7rem';
                    header.style.opacity = '0.9';
                    header.innerText = dateLabel;
                    scrollArea.appendChild(header);
                    lastDateLabel = dateLabel;
                }

                const el = createNotificationElement(notif);
                scrollArea.appendChild(el);
            });
        }
    }

    function getRelativeDateLabel(dateString) {
        const date = new Date(dateString);
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
        a.className = `dropdown-item d-flex align-items-center py-3 border-bottom notification-item ${bgClass}`;
        a.href = '#';
        a.dataset.id = notif.notification_id;
        a.dataset.link = notif.link || '';
        a.dataset.title = notif.title;
        a.dataset.message = notif.message;
        a.onclick = handleNotificationClick; // Uses global function from navbar

        a.innerHTML = `
            <div class="mr-3">
                <div class="icon-circle ${iconBg} d-flex align-items-center justify-content-center rounded-circle" style="width: 40px; height: 40px;">
                    <i class="fas fa-${iconClass}"></i>
                </div>
            </div>
            <div style="flex: 1; min-width: 0;">
                <div class="d-flex align-items-center mb-1">
                    ${unreadDot}
                    <div class="small text-gray-500">${formatDate(notif.created_at)}</div>
                </div>
                <div class="font-weight-bold text-truncate" style="font-size: 0.95rem;">${escapeHtml(notif.title)}</div>
                <div class="small text-dark text-truncate" style="font-size: 0.85rem;">${escapeHtml(notif.message)}</div>
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

