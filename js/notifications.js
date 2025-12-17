/**
 * js/notifications.js
 * Handles real-time notification polling and UI updates.
 */

document.addEventListener('DOMContentLoaded', function () {
    const POLL_INTERVAL = 30000; // 30 seconds
    let lastUnreadCount = -1; // Initialize with -1 to force first update

    // Elements
    const badge = document.querySelector('.badge-counter');
    const dropdownList = document.querySelector('.dropdown-list');
    const toastContainer = document.getElementById('toast-container');

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

                    // If count INCREASED (and it's not the initial load), show a toast
                    if (lastUnreadCount !== -1 && data.unread_count > lastUnreadCount) {
                        // Find the newest unread notification to show
                        const newest = data.notifications.find(n => n.is_read == 0);
                        if (newest) {
                            showToast(newest);
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
                badgeEl.className = 'badge badge-danger badge-counter position-absolute';
                badgeEl.style.cssText = 'top: 0; right: 0; font-size: 0.6rem;';
                bell.appendChild(badgeEl);
            }
            badgeEl.innerText = count > 9 ? '9+' : count;
        } else {
            if (badgeEl) badgeEl.remove();
        }
    }

    function updateDropdown(notifications) {
        // Clear existing list (except header and footer if possible, but easier to rebuild body)
        // Current structure has header -> items -> footer.
        // Let's identify the content area. 
        // We will remove all "notification-item" and "no-notif-msg" elements and re-insert them after the header.

        const items = dropdownList.querySelectorAll('.notification-item, .no-notif-msg');
        items.forEach(el => el.remove());

        const header = dropdownList.querySelector('.dropdown-header');
        const footer = dropdownList.querySelector('a.dropdown-item.text-center'); // Mark all read

        if (notifications.length === 0) {
            const noMsg = document.createElement('a');
            noMsg.className = 'dropdown-item d-flex align-items-center py-3 text-muted justify-content-center no-notif-msg';
            noMsg.href = '#';
            noMsg.innerHTML = '<small>No new notifications</small>';
            header.insertAdjacentElement('afterend', noMsg);
        } else {
            // Insert in reverse order so they appear top-down (newest first is usually default in array)
            // But insertAdjacentElement('afterend') on header adds to the top of the list.
            // So we iterate in REVERSE of the array if the array is sorted DESC (newest 0).
            // Wait, standard append approach:

            // We need to insert AFTER header.
            let refNode = header;

            notifications.forEach(notif => {
                const el = createNotificationElement(notif);
                refNode.insertAdjacentElement('afterend', el);
                refNode = el; // Append next one after this one
            });
        }
    }

    function createNotificationElement(notif) {
        const bgClass = notif.is_read == 1 ? 'bg-white' : 'bg-light';
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
                <div class="small text-gray-500">${formatDate(notif.created_at)}</div>
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
        toast.style.cssText = 'min-width: 300px; margin-bottom: 10px; animation: slideIn 0.3s ease-out;';

        let iconHtml = '<i class="fas fa-info-circle text-info"></i>';
        if (notif.type === 'success') iconHtml = '<i class="fas fa-check-circle text-success"></i>';
        if (notif.type === 'warning') iconHtml = '<i class="fas fa-exclamation-triangle text-warning"></i>';
        if (notif.type === 'error') iconHtml = '<i class="fas fa-times-circle text-danger"></i>';

        toast.innerHTML = `
            <div class="toast-header bg-transparent border-bottom border-secondary text-white">
                <strong class="mr-auto">${iconHtml} &nbsp; ${escapeHtml(notif.title)}</strong>
                <small class="text-white-50">Just now</small>
                <button type="button" class="ml-2 mb-1 close text-white" onclick="this.parentElement.parentElement.remove()">
                    <span>&times;</span>
                </button>
            </div>
            <div class="toast-body text-white">
                ${escapeHtml(notif.message)}
            </div>
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

