/**
 * Chat Badge Utility
 * Fetches unread chat count and updates the sidebar badge
 * Include this script on any page that needs to show unread chat count
 */

let chatBadgeInterval = null;

/**
 * Fetch unread chat count and update badge
 */
async function updateChatBadge() {
  try {
    const response = await fetch("/api/chat/unread-count.php", {
      credentials: "include",
    });

    if (!response.ok) return;

    const data = await response.json();
    if (!data.success) return;

    const badge = document.getElementById("chatUnreadBadge");
    if (badge) {
      const count = data.total_unread || 0;
      if (count > 0) {
        badge.textContent = count > 99 ? "99+" : count;
        badge.classList.remove("hidden");
      } else {
        badge.classList.add("hidden");
      }
    }
  } catch (error) {
    console.error("Error fetching chat unread count:", error);
  }
}

/**
 * Initialize chat badge polling
 * @param {number} interval - Polling interval in ms (default 30000)
 */
function initChatBadge(interval = 30000) {
  // Initial fetch
  updateChatBadge();

  // Start polling
  if (chatBadgeInterval) {
    clearInterval(chatBadgeInterval);
  }
  chatBadgeInterval = setInterval(updateChatBadge, interval);

  // Pause when page is hidden
  document.addEventListener("visibilitychange", () => {
    if (document.hidden) {
      if (chatBadgeInterval) {
        clearInterval(chatBadgeInterval);
        chatBadgeInterval = null;
      }
    } else {
      updateChatBadge();
      chatBadgeInterval = setInterval(updateChatBadge, interval);
    }
  });
}

// Export for module usage
export { initChatBadge, updateChatBadge };
