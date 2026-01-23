/**
 * Notification Dropdown Component
 * Shared notification bell with dropdown panel for all portals
 * 
 * Fetches notifications from the API and displays them dynamically.
 * Implements polling for real-time updates.
 */

// Store current notifications
let currentNotifications = [];
let pollingInterval = null;
let isLoading = false;

// API base path
const API_BASE = '/api/notifications';

// Polling interval in milliseconds (30 seconds)
const POLLING_INTERVAL = 30000;

/**
 * Initialize notification dropdown for a portal
 * @param {string} portalType - 'admin', 'seeker', 'donor', or 'hospital' (optional, for backwards compatibility)
 */
export function initNotificationDropdown(portalType = null) {
  // Initial load
  loadNotifications();
  
  // Start polling for updates
  startPolling();
  
  // Setup click outside to close
  document.addEventListener("click", handleOutsideClick);
  
  // Cleanup on page unload
  window.addEventListener('beforeunload', stopPolling);
}

/**
 * Start polling for notification updates
 */
function startPolling() {
  if (pollingInterval) {
    clearInterval(pollingInterval);
  }
  
  pollingInterval = setInterval(() => {
    fetchUnreadCount();
    // Only fetch full list if panel is open
    const panel = document.getElementById("notificationPanel");
    if (panel && !panel.classList.contains("hidden")) {
      loadNotifications();
    }
  }, POLLING_INTERVAL);
}

/**
 * Stop polling
 */
function stopPolling() {
  if (pollingInterval) {
    clearInterval(pollingInterval);
    pollingInterval = null;
  }
}

/**
 * Fetch unread count from API
 */
async function fetchUnreadCount() {
  try {
    const response = await fetch(API_BASE + '/unread-count.php', {
      credentials: 'include'
    });
    
    if (!response.ok) return;
    
    const data = await response.json();
    
    if (data.success) {
      updateBadge(data.unread_count);
    }
  } catch (error) {
    console.error('Failed to fetch unread count:', error);
  }
}

/**
 * Load notifications from API
 */
async function loadNotifications(limit = 20) {
  if (isLoading) return;
  isLoading = true;
  
  try {
    const response = await fetch(API_BASE + '/list.php?limit=' + limit, {
      credentials: 'include'
    });
    
    if (!response.ok) {
      throw new Error('Failed to fetch notifications');
    }
    
    const data = await response.json();
    
    if (data.success) {
      currentNotifications = data.notifications;
      renderNotifications();
    }
  } catch (error) {
    console.error('Failed to load notifications:', error);
    renderError();
  } finally {
    isLoading = false;
  }
}

/**
 * Toggle notification panel visibility
 */
export function toggleNotifications() {
  const panel = document.getElementById("notificationPanel");
  if (!panel) return;

  panel.classList.toggle("hidden");

  if (!panel.classList.contains("hidden")) {
    loadNotifications();
  }
}

/**
 * Mark a single notification as read
 * @param {number} id - Notification ID
 * @param {string} relatedType - Related entity type
 * @param {number} relatedId - Related entity ID
 */
export async function markAsRead(id, relatedType, relatedId) {
  try {
    const response = await fetch(API_BASE + '/mark-read.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      credentials: 'include',
      body: JSON.stringify({ notification_id: id })
    });
    
    if (response.ok) {
      // Update local state
      const notification = currentNotifications.find((n) => n.id === id);
      if (notification) {
        notification.read = true;
        renderNotifications();
      }
      
      // Update badge count
      fetchUnreadCount();
      
      // Navigate to related page if applicable
      if (relatedType && relatedId) {
        navigateToRelated(relatedType, relatedId);
      }
    }
  } catch (error) {
    console.error('Failed to mark notification as read:', error);
  }
}

/**
 * Mark all notifications as read
 */
export async function markAllAsRead() {
  try {
    const response = await fetch(API_BASE + '/mark-all-read.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      credentials: 'include'
    });
    
    if (response.ok) {
      // Update local state
      currentNotifications.forEach((n) => (n.read = true));
      renderNotifications();
      
      // Update badge count
      updateBadge(0);
    }
  } catch (error) {
    console.error('Failed to mark all notifications as read:', error);
  }
}

/**
 * Navigate to the related page based on notification type
 */
function navigateToRelated(relatedType, relatedId) {
  // Determine current portal from URL
  const path = window.location.pathname;
  let basePath = '/src/pages';
  
  if (path.includes('/admin/')) {
    basePath = '/src/pages/admin';
  } else if (path.includes('/donor/')) {
    basePath = '/src/pages/donor';
  } else if (path.includes('/hospital/')) {
    basePath = '/src/pages/hospital';
  } else if (path.includes('/seeker/')) {
    basePath = '/src/pages/seeker';
  }
  
  // Navigate based on related type
  switch (relatedType) {
    case 'request':
      if (path.includes('/admin/')) {
        window.location.href = basePath + '/dashboard.html';
      } else if (path.includes('/donor/')) {
        window.location.href = basePath + '/dashboard.html';
      } else if (path.includes('/hospital/')) {
        window.location.href = basePath + '/request.html';
      } else if (path.includes('/seeker/')) {
        window.location.href = basePath + '/request-details.html?id=' + relatedId;
      }
      break;
      
    case 'donation':
      if (path.includes('/donor/')) {
        window.location.href = basePath + '/history.html';
      } else if (path.includes('/hospital/')) {
        window.location.href = basePath + '/appointments.html';
      }
      break;
      
    case 'appointment':
      if (path.includes('/donor/')) {
        window.location.href = basePath + '/history.html';
      } else if (path.includes('/hospital/')) {
        window.location.href = basePath + '/appointments.html';
      }
      break;
      
    case 'voluntary_donation':
      if (path.includes('/donor/')) {
        window.location.href = basePath + '/voluntary.html';
      } else if (path.includes('/hospital/')) {
        window.location.href = basePath + '/voluntary.html';
      } else if (path.includes('/admin/')) {
        window.location.href = basePath + '/voluntary.html';
      }
      break;
      
    case 'user':
      if (path.includes('/admin/')) {
        window.location.href = basePath + '/donors.html';
      }
      break;
      
    case 'certificate':
      if (path.includes('/donor/')) {
        window.location.href = basePath + '/certificates.html';
      }
      break;
      
    default:
      // No navigation for unknown types
      break;
  }
}

/**
 * Update the badge count
 */
function updateBadge(count) {
  const badge = document.getElementById("notificationBadge");
  
  if (badge) {
    if (count > 0) {
      badge.textContent = count > 99 ? '99+' : count;
      badge.classList.remove("hidden");
    } else {
      badge.classList.add("hidden");
    }
  }
}

/**
 * Render notifications in the panel
 */
function renderNotifications() {
  const container = document.getElementById("notificationList");
  const badge = document.getElementById("notificationBadge");

  if (!container) return;

  const unreadCount = currentNotifications.filter((n) => !n.read).length;

  // Update badge
  updateBadge(unreadCount);

  // Render notifications list
  if (currentNotifications.length === 0) {
    container.innerHTML = '<div class="p-8 text-center text-gray-500">' +
      '<i data-lucide="bell-off" class="size-8 mx-auto mb-2 opacity-50"></i>' +
      '<p class="text-sm">No notifications</p>' +
      '</div>';
  } else {
    container.innerHTML = currentNotifications
      .map((notification) => {
        // Add emergency styling
        const emergencyClass = notification.isEmergency 
          ? 'border-l-4 border-red-500 bg-red-50' 
          : '';
        
        const readClass = notification.read ? "opacity-70" : "";
        const titleClass = notification.read
          ? "font-normal text-gray-700"
          : "font-semibold text-gray-900";
        const emergencyTitleClass = notification.isEmergency ? 'text-red-700' : '';
        const unreadDot = !notification.read
          ? '<span class="size-2 bg-red-600 rounded-full flex-shrink-0 mt-1.5"></span>'
          : "";
        
        const relatedType = notification.relatedType || '';
        const relatedId = notification.relatedId || 'null';
        
        return '<div class="p-4 border-b border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors ' + readClass + ' ' + emergencyClass + '"' +
          ' data-id="' + notification.id + '"' +
          ' onclick="window.markNotificationAsRead(' + notification.id + ', \'' + relatedType + '\', ' + relatedId + ')">' +
          '<div class="flex gap-3">' +
          '<div class="' + notification.iconBg + ' p-2 rounded-lg h-fit">' +
          '<i data-lucide="' + notification.icon + '" class="size-4 ' + notification.iconColor + '"></i>' +
          '</div>' +
          '<div class="flex-1 min-w-0">' +
          '<div class="flex items-start justify-between gap-2">' +
          '<p class="text-sm ' + titleClass + ' ' + emergencyTitleClass + '">' + notification.title + '</p>' +
          unreadDot +
          '</div>' +
          '<p class="text-xs text-gray-500 mt-0.5 line-clamp-2">' + notification.message + '</p>' +
          '<p class="text-xs text-gray-400 mt-1">' + notification.time + '</p>' +
          '</div>' +
          '</div>' +
          '</div>';
      })
      .join("");
  }

  // Re-initialize icons
  if (window.lucide) {
    window.lucide.createIcons();
  }
}

/**
 * Render error state
 */
function renderError() {
  const container = document.getElementById("notificationList");
  
  if (container) {
    container.innerHTML = '<div class="p-8 text-center text-gray-500">' +
      '<i data-lucide="alert-circle" class="size-8 mx-auto mb-2 opacity-50"></i>' +
      '<p class="text-sm">Failed to load notifications</p>' +
      '<button onclick="window.retryLoadNotifications()" class="mt-2 text-xs text-red-600 hover:text-red-700">Try again</button>' +
      '</div>';
    
    if (window.lucide) {
      window.lucide.createIcons();
    }
  }
}

/**
 * Handle clicks outside the dropdown to close it
 */
function handleOutsideClick(event) {
  const dropdown = document.getElementById("notificationDropdown");
  const panel = document.getElementById("notificationPanel");

  if (
    dropdown &&
    panel &&
    !dropdown.contains(event.target) &&
    !panel.classList.contains("hidden")
  ) {
    panel.classList.add("hidden");
  }
}

/**
 * Retry loading notifications
 */
function retryLoadNotifications() {
  loadNotifications();
}

// Make functions globally available for onclick handlers
window.toggleNotifications = toggleNotifications;
window.markNotificationAsRead = markAsRead;
window.markAllAsRead = markAllAsRead;
window.retryLoadNotifications = retryLoadNotifications;

// Export for module usage
export { loadNotifications, fetchUnreadCount, startPolling, stopPolling };
