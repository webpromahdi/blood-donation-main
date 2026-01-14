/**
 * Notification Dropdown Component
 * Shared notification bell with dropdown panel for all portals
 */

// Default notifications - can be customized per portal
const defaultNotifications = {
    admin: [
        {
            id: 1,
            title: "New Donor Registration",
            message: "Sarah Johnson just registered as a blood donor",
            time: "5 min ago",
            read: false,
            icon: "user-plus",
            iconBg: "bg-green-100",
            iconColor: "text-green-600",
        },
        {
            id: 2,
            title: "Hospital Verified",
            message: "City General Hospital has been verified",
            time: "30 min ago",
            read: false,
            icon: "check-circle",
            iconBg: "bg-blue-100",
            iconColor: "text-blue-600",
        },
        {
            id: 3,
            title: "Urgent Blood Request",
            message: "Emergency O- blood needed at St. Mary Hospital",
            time: "1 hour ago",
            read: false,
            icon: "alert-circle",
            iconBg: "bg-red-100",
            iconColor: "text-red-600",
        },
    ],
    seeker: [
        {
            id: 1,
            title: "Donor Assigned",
            message: "A donor has accepted your blood request",
            time: "10 min ago",
            read: false,
            icon: "user-check",
            iconBg: "bg-green-100",
            iconColor: "text-green-600",
        },
        {
            id: 2,
            title: "Donor On the Way",
            message: "Michael is traveling to City Hospital",
            time: "25 min ago",
            read: false,
            icon: "navigation",
            iconBg: "bg-blue-100",
            iconColor: "text-blue-600",
        },
        {
            id: 3,
            title: "Request Approved",
            message: "Your blood request REQ001 has been approved",
            time: "2 hours ago",
            read: true,
            icon: "check-circle",
            iconBg: "bg-green-100",
            iconColor: "text-green-600",
        },
    ],
    donor: [
        {
            id: 1,
            title: "New Blood Request",
            message: "O+ blood needed urgently at City Hospital",
            time: "5 min ago",
            read: false,
            icon: "droplet",
            iconBg: "bg-red-100",
            iconColor: "text-red-600",
        },
        {
            id: 2,
            title: "Donation Reminder",
            message: "You are eligible to donate again!",
            time: "1 day ago",
            read: false,
            icon: "calendar",
            iconBg: "bg-blue-100",
            iconColor: "text-blue-600",
        },
        {
            id: 3,
            title: "Thank You!",
            message: "Your recent donation helped save 3 lives",
            time: "3 days ago",
            read: true,
            icon: "heart",
            iconBg: "bg-pink-100",
            iconColor: "text-pink-600",
        },
    ],
    hospital: [
        {
            id: 1,
            title: "Donor Arrived",
            message: "Michael Johnson has arrived for donation",
            time: "5 min ago",
            read: false,
            icon: "map-pin",
            iconBg: "bg-green-100",
            iconColor: "text-green-600",
        },
        {
            id: 2,
            title: "Request Approved",
            message: "Your blood request has been approved by admin",
            time: "30 min ago",
            read: false,
            icon: "check-circle",
            iconBg: "bg-blue-100",
            iconColor: "text-blue-600",
        },
        {
            id: 3,
            title: "Low Stock Alert",
            message: "O- blood stock is running low",
            time: "2 hours ago",
            read: false,
            icon: "alert-triangle",
            iconBg: "bg-yellow-100",
            iconColor: "text-yellow-600",
        },
    ],
};

let currentNotifications = [];

/**
 * Initialize notification dropdown for a portal
 * @param {string} portalType - 'admin', 'seeker', 'donor', or 'hospital'
 * @param {Array} customNotifications - Optional custom notifications array
 */
export function initNotificationDropdown(portalType = 'admin', customNotifications = null) {
    currentNotifications = customNotifications || defaultNotifications[portalType] || [];

    // Initial render
    renderNotifications();

    // Setup click outside to close
    document.addEventListener('click', handleOutsideClick);
}

/**
 * Toggle notification panel visibility
 */
export function toggleNotifications() {
    const panel = document.getElementById('notificationPanel');
    if (!panel) return;

    panel.classList.toggle('hidden');

    if (!panel.classList.contains('hidden')) {
        renderNotifications();
    }
}

/**
 * Mark a single notification as read
 */
export function markAsRead(id) {
    const notification = currentNotifications.find(n => n.id === id);
    if (notification) {
        notification.read = true;
        renderNotifications();
    }
}

/**
 * Mark all notifications as read
 */
export function markAllAsRead() {
    currentNotifications.forEach(n => n.read = true);
    renderNotifications();
}

/**
 * Render notifications in the panel
 */
function renderNotifications() {
    const container = document.getElementById('notificationList');
    const badge = document.getElementById('notificationBadge');

    if (!container) return;

    const unreadCount = currentNotifications.filter(n => !n.read).length;

    // Update badge
    if (badge) {
        if (unreadCount > 0) {
            badge.textContent = unreadCount;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }

    // Render notifications list
    if (currentNotifications.length === 0) {
        container.innerHTML = `
      <div class="p-8 text-center text-gray-500">
        <i data-lucide="bell-off" class="size-8 mx-auto mb-2 opacity-50"></i>
        <p class="text-sm">No notifications</p>
      </div>
    `;
    } else {
        container.innerHTML = currentNotifications.map(notification => `
      <div 
        class="p-4 border-b border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors ${notification.read ? 'opacity-70' : ''}"
        data-id="${notification.id}"
        onclick="window.markNotificationAsRead(${notification.id})"
      >
        <div class="flex gap-3">
          <div class="${notification.iconBg} p-2 rounded-lg h-fit">
            <i data-lucide="${notification.icon}" class="size-4 ${notification.iconColor}"></i>
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-2">
              <p class="text-sm ${notification.read ? 'font-normal text-gray-700' : 'font-semibold text-gray-900'} truncate">
                ${notification.title}
              </p>
              ${!notification.read ? '<span class="size-2 bg-red-600 rounded-full flex-shrink-0 mt-1.5"></span>' : ''}
            </div>
            <p class="text-xs text-gray-500 mt-0.5 line-clamp-1">${notification.message}</p>
            <p class="text-xs text-gray-400 mt-1">${notification.time}</p>
          </div>
        </div>
      </div>
    `).join('');
    }

    // Re-initialize icons
    if (window.lucide) {
        window.lucide.createIcons();
    }
}

/**
 * Handle clicks outside the dropdown to close it
 */
function handleOutsideClick(event) {
    const dropdown = document.getElementById('notificationDropdown');
    const panel = document.getElementById('notificationPanel');

    if (dropdown && panel && !dropdown.contains(event.target) && !panel.classList.contains('hidden')) {
        panel.classList.add('hidden');
    }
}

// Make functions globally available for onclick handlers
window.toggleNotifications = toggleNotifications;
window.markNotificationAsRead = markAsRead;
window.markAllAsRead = markAllAsRead;
