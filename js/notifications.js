/**
 * Real Estate CRM Notifications JavaScript
 * Handles notification functionality including polling for new notifications
 */

// Global notification variables
let lastNotificationCheck = 0;
let notificationCheckInterval = 60000; // Check every 60 seconds by default
let notificationCount = 0;

// Initialize notifications when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    initTooltips();
    
    // Initialize notification polling
    initNotifications();
});

/**
 * Initialize tooltips for notification elements
 */
function initTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
}

/**
 * Initialize notification system
 */
function initNotifications() {
    // Get notification elements
    const notificationBell = document.getElementById('notificationsDropdown');
    
    if (!notificationBell) return; // Exit if notification bell doesn't exist
    
    // Set initial notification count
    const badgeElement = notificationBell.querySelector('.badge');
    if (badgeElement) {
        notificationCount = parseInt(badgeElement.textContent) || 0;
    }
    
    // Check for new notifications immediately
    checkForNewNotifications();
    
    // Set up polling for new notifications
    setInterval(checkForNewNotifications, notificationCheckInterval);
    
    // Add event listener for marking notifications as read
    setupNotificationReadEvents();
}

/**
 * Check for new notifications via AJAX
 */
function checkForNewNotifications() {
    // Record the time of this check
    lastNotificationCheck = Date.now();
    
    // Make AJAX request to check for new notifications
    fetch('modules/notifications/check_new.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationBadge(data.count);
                
                // If there are new notifications, update the dropdown
                if (data.count > notificationCount) {
                    updateNotificationDropdown(data.notifications);
                    notificationCount = data.count;
                    
                    // Show notification toast if enabled
                    if (data.new_notifications && data.new_notifications.length > 0) {
                        showNotificationToast(data.new_notifications[0]);
                    }
                }
            }
        })
        .catch(error => console.error('Error checking notifications:', error));
}

/**
 * Update the notification badge count
 * @param {number} count - The new notification count
 */
function updateNotificationBadge(count) {
    const notificationBell = document.getElementById('notificationsDropdown');
    if (!notificationBell) return;
    
    // Get or create badge element
    let badgeElement = notificationBell.querySelector('.badge');
    
    if (count > 0) {
        if (!badgeElement) {
            badgeElement = document.createElement('span');
            badgeElement.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
            notificationBell.appendChild(badgeElement);
        }
        badgeElement.textContent = count;
    } else if (badgeElement) {
        badgeElement.remove();
    }
}

/**
 * Update the notification dropdown with new notifications
 * @param {Array} notifications - Array of notification objects
 */
function updateNotificationDropdown(notifications) {
    const dropdownMenu = document.querySelector('.notification-dropdown');
    if (!dropdownMenu) return;
    
    // Get the notifications container
    const notificationsContainer = dropdownMenu.querySelector('.dropdown-menu-content') || dropdownMenu;
    
    // Clear existing notifications
    const existingItems = notificationsContainer.querySelectorAll('.dropdown-item:not(.text-center)');
    existingItems.forEach(item => item.remove());
    
    // Add new notifications
    if (notifications && notifications.length > 0) {
        // Remove "no notifications" message if it exists
        const noNotificationsMsg = notificationsContainer.querySelector('.dropdown-item.text-center');
        if (noNotificationsMsg) {
            noNotificationsMsg.remove();
        }
        
        // Add each notification
        notifications.forEach(notification => {
            const notificationItem = createNotificationElement(notification);
            
            // Insert after the header
            const header = notificationsContainer.querySelector('.border-bottom');
            if (header) {
                header.after(notificationItem);
            } else {
                notificationsContainer.prepend(notificationItem);
            }
        });
        
        // Add "View all" link if it doesn't exist
        let viewAllLink = notificationsContainer.querySelector('.dropdown-item.text-center.small');
        if (!viewAllLink) {
            const divider = document.createElement('div');
            divider.className = 'dropdown-divider';
            notificationsContainer.appendChild(divider);
            
            viewAllLink = document.createElement('a');
            viewAllLink.className = 'dropdown-item text-center small text-muted py-2';
            viewAllLink.href = 'modules/notifications/index.php';
            viewAllLink.textContent = 'View all notifications';
            notificationsContainer.appendChild(viewAllLink);
        }
    } else {
        // Show "no notifications" message
        const noNotificationsMsg = document.createElement('div');
        noNotificationsMsg.className = 'dropdown-item text-center py-3';
        noNotificationsMsg.innerHTML = '<i class="fas fa-bell-slash text-muted"></i><br><span class="text-muted">No notifications</span>';
        notificationsContainer.appendChild(noNotificationsMsg);
    }
}

/**
 * Create a notification element for the dropdown
 * @param {Object} notification - The notification object
 * @returns {HTMLElement} - The notification element
 */
function createNotificationElement(notification) {
    // Determine icon based on notification type
    let icon = 'info-circle text-primary';
    switch (notification.type) {
        case 'Task':
            icon = 'tasks text-warning';
            break;
        case 'Payment':
            icon = 'money-bill-wave text-success';
            break;
        case 'System':
            icon = 'cog text-secondary';
            break;
    }
    
    // Create notification item
    const item = document.createElement('a');
    item.className = `dropdown-item py-2 ${notification.is_read ? '' : 'bg-light'}`;
    item.href = `modules/notifications/view.php?id=${notification.id}`;
    item.dataset.notificationId = notification.id;
    
    // Create content
    item.innerHTML = `
        <div class="d-flex align-items-center">
            <div class="me-3">
                <i class="fas fa-${icon} fa-lg"></i>
            </div>
            <div>
                <div class="fw-bold small">${escapeHtml(notification.title)}</div>
                <div class="small text-muted">${escapeHtml(notification.message.substring(0, 50))}${notification.message.length > 50 ? '...' : ''}</div>
                <div class="small text-muted">${formatDateTime(notification.created_at)}</div>
            </div>
        </div>
    `;
    
    return item;
}

/**
 * Show a toast notification for a new notification
 * @param {Object} notification - The notification object
 */
function showNotificationToast(notification) {
    // Create toast container if it doesn't exist
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    // Determine icon based on notification type
    let icon = 'info-circle text-primary';
    switch (notification.type) {
        case 'Task':
            icon = 'tasks text-warning';
            break;
        case 'Payment':
            icon = 'money-bill-wave text-success';
            break;
        case 'System':
            icon = 'cog text-secondary';
            break;
    }
    
    // Create toast element
    const toastElement = document.createElement('div');
    toastElement.className = 'toast';
    toastElement.setAttribute('role', 'alert');
    toastElement.setAttribute('aria-live', 'assertive');
    toastElement.setAttribute('aria-atomic', 'true');
    
    toastElement.innerHTML = `
        <div class="toast-header">
            <i class="fas fa-${icon} me-2"></i>
            <strong class="me-auto">${escapeHtml(notification.title)}</strong>
            <small>${formatDateTime(notification.created_at)}</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            ${escapeHtml(notification.message)}
            <div class="mt-2 pt-2 border-top">
                <a href="modules/notifications/view.php?id=${notification.id}" class="btn btn-sm btn-primary">View</a>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="toast">Close</button>
            </div>
        </div>
    `;
    
    // Add to container
    toastContainer.appendChild(toastElement);
    
    // Initialize and show toast
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: 5000
    });
    toast.show();
    
    // Remove toast from DOM after it's hidden
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}

/**
 * Set up event listeners for marking notifications as read
 */
function setupNotificationReadEvents() {
    // Mark notification as read when clicked
    document.addEventListener('click', function(event) {
        const notificationItem = event.target.closest('[data-notification-id]');
        if (notificationItem) {
            const notificationId = notificationItem.dataset.notificationId;
            markNotificationAsRead(notificationId);
        }
    });
}

/**
 * Mark a notification as read via AJAX
 * @param {string|number} notificationId - The ID of the notification to mark as read
 */
function markNotificationAsRead(notificationId) {
    fetch(`modules/notifications/mark_read.php?id=${notificationId}&ajax=1`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update notification count
                updateNotificationBadge(data.count);
                notificationCount = data.count;
            }
        })
        .catch(error => console.error('Error marking notification as read:', error));
}

/**
 * Format a datetime string
 * @param {string} datetime - The datetime string to format
 * @returns {string} - The formatted datetime string
 */
function formatDateTime(datetime) {
    const date = new Date(datetime);
    return date.toLocaleString();
}

/**
 * Escape HTML special characters
 * @param {string} text - The text to escape
 * @returns {string} - The escaped text
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}