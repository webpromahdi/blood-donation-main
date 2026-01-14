/**
 * Notifications Component
 * Toast notification system
 */

/**
 * Shows a toast notification
 * @param {string} message - The message to display
 * @param {('success'|'error'|'info'|'warning')} type - Type of notification
 * @param {number} duration - Duration in ms before auto-dismiss (default: 3000)
 */
export function showNotification(message, type = 'success', duration = 3000) {
    const config = {
        success: {
            bg: 'bg-green-50 border-green-600',
            text: 'text-green-900',
            icon: 'text-green-600',
            iconName: 'check-circle'
        },
        error: {
            bg: 'bg-red-50 border-red-600',
            text: 'text-red-900',
            icon: 'text-red-600',
            iconName: 'x-circle'
        },
        info: {
            bg: 'bg-blue-50 border-blue-600',
            text: 'text-blue-900',
            icon: 'text-blue-600',
            iconName: 'info'
        },
        warning: {
            bg: 'bg-yellow-50 border-yellow-600',
            text: 'text-yellow-900',
            icon: 'text-yellow-600',
            iconName: 'alert-triangle'
        }
    };

    const style = config[type] || config.success;

    const notification = document.createElement('div');
    notification.className = `fixed top-20 right-8 p-4 ${style.bg} border-l-4 rounded-lg shadow-lg z-50 animate-slide-in`;
    notification.innerHTML = `
    <div class="flex items-center gap-3">
      <i data-lucide="${style.iconName}" class="size-5 ${style.icon}"></i>
      <p class="${style.text}">${message}</p>
      <button class="ml-2 hover:opacity-70" onclick="this.parentElement.parentElement.remove()">
        <i data-lucide="x" class="size-4 ${style.icon}"></i>
      </button>
    </div>
  `;

    document.body.appendChild(notification);

    // Initialize icons in notification
    if (window.lucide) {
        window.lucide.createIcons();
    }

    // Auto-dismiss
    if (duration > 0) {
        setTimeout(() => {
            notification.classList.add('opacity-0', 'transition-opacity');
            setTimeout(() => notification.remove(), 300);
        }, duration);
    }

    return notification;
}

/**
 * Show success notification
 */
export function showSuccess(message, duration = 3000) {
    return showNotification(message, 'success', duration);
}

/**
 * Show error notification
 */
export function showError(message, duration = 3000) {
    return showNotification(message, 'error', duration);
}

/**
 * Show info notification
 */
export function showInfo(message, duration = 3000) {
    return showNotification(message, 'info', duration);
}

/**
 * Show warning notification
 */
export function showWarning(message, duration = 3000) {
    return showNotification(message, 'warning', duration);
}

// Make functions available globally
window.showNotification = showNotification;
window.showSuccess = showSuccess;
window.showError = showError;
window.showInfo = showInfo;
window.showWarning = showWarning;
