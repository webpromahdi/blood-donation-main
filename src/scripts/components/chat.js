/**
 * Chat Component
 * Shared chat functionality for all portals
 *
 * @version 3.0.0 - Complete rewrite for stable message rendering
 *
 * FIXES:
 * - Messages now correctly appear left/right based on sender
 * - Polling appends new messages without clearing existing ones
 * - Optimistic UI for sent messages works properly
 * - No more disappearing messages after multiple sends
 */

// ===========================================
// CONFIGURATION
// ===========================================

const CHAT_CONFIG = {
  activeInterval: 3000,
  idleInterval: 10000,
  backgroundInterval: 30000,
  idleTimeout: 30000,
  maxMessageLength: 2000,
};

const API_BASE = "/api/chat";

// ===========================================
// STATE (Module-level, preserved across calls)
// ===========================================

let conversations = [];
let currentMessages = []; // Array of message objects for current conversation
let messageIdSet = new Set(); // Track message IDs to prevent duplicates
let selectedUserId = null;
let selectedUserName = null;
let selectedUserRole = null;
let lastTimestamp = null;
let pollingInterval = null;
let isLoading = false;
let lastActivity = Date.now();
let isTabVisible = true;
let currentRequestId = 0;
let currentUserId = null; // Logged-in user's ID from backend
let isInitialized = false; // Prevent double initialization

// ===========================================
// INITIALIZATION
// ===========================================

/**
 * Initialize chat for a portal
 * Gets current user ID first to ensure proper message alignment
 */
export async function initChat(
  portalType,
  targetUserId = null,
  targetUserName = null,
  targetUserRole = null,
) {
  // Prevent double initialization
  if (isInitialized) {
    console.warn("Chat already initialized");
    return;
  }
  isInitialized = true;

  // CRITICAL: Get current user ID first before anything else
  try {
    const authResponse = await fetch("/api/auth/check.php", {
      credentials: "include",
    });
    const authData = await authResponse.json();
    if (authData.success && authData.logged_in && authData.user) {
      currentUserId = parseInt(authData.user.id, 10);
      console.log("[Chat] Current user ID set to:", currentUserId);
    }
  } catch (error) {
    console.error("[Chat] Failed to get current user ID:", error);
  }

  // Setup visibility change handler
  document.addEventListener("visibilitychange", handleVisibilityChange);

  // Setup activity tracking
  document.addEventListener("mousemove", updateActivity);
  document.addEventListener("keydown", updateActivity);
  document.addEventListener("click", updateActivity);

  // Validate targetUserId
  const validTargetUserId =
    targetUserId && !isNaN(targetUserId) && targetUserId > 0
      ? parseInt(targetUserId, 10)
      : null;

  // Load conversations, then select target if specified
  loadConversations()
    .then(() => {
      if (validTargetUserId) {
        selectConversation(
          validTargetUserId,
          targetUserName || "User",
          targetUserRole,
        );
      }
    })
    .catch((error) => {
      console.error("Failed to load conversations:", error);
      if (validTargetUserId) {
        selectConversation(
          validTargetUserId,
          targetUserName || "User",
          targetUserRole,
        );
      }
    });

  // Start polling
  startPolling();

  // Setup send button
  const sendBtn = document.getElementById("sendMessageBtn");
  const messageInput = document.getElementById("messageInput");

  if (sendBtn) {
    sendBtn.addEventListener("click", sendMessage);
  }

  if (messageInput) {
    messageInput.addEventListener("keypress", (e) => {
      if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
      }
    });
    messageInput.addEventListener("input", updateCharCounter);
  }

  // Setup contact admin button (for non-admin portals)
  if (portalType !== "admin") {
    setupContactAdmin();
  }

  // Cleanup on page unload
  window.addEventListener("beforeunload", stopPolling);
}

// ===========================================
// CONVERSATION MANAGEMENT
// ===========================================

/**
 * Load all conversations
 */
async function loadConversations() {
  try {
    const response = await fetch(`${API_BASE}/conversations.php`, {
      credentials: "include",
    });

    if (!response.ok) throw new Error("Failed to load conversations");

    const data = await response.json();

    if (data.success) {
      conversations = data.conversations;
      renderConversationList();
      return true;
    }
    return false;
  } catch (error) {
    console.error("Failed to load conversations:", error);
    showChatError("Failed to load conversations");
    throw error;
  }
}

/**
 * Select a conversation
 * Clears previous conversation state and loads new messages
 */
export function selectConversation(userId, userName, userRole) {
  if (!userId || isNaN(userId) || userId <= 0) {
    console.error("Invalid userId for selectConversation:", userId);
    showMessagePanelError("Unable to load conversation. Invalid user ID.");
    return;
  }

  const newUserId = parseInt(userId, 10);

  // If same user already selected, just refresh
  if (selectedUserId === newUserId) {
    return;
  }

  // Increment request ID to invalidate any pending requests
  currentRequestId++;

  // COMPLETELY reset state for new conversation
  selectedUserId = newUserId;
  selectedUserName = userName || "User";
  selectedUserRole = userRole;
  lastTimestamp = null;

  // Clear message state completely
  currentMessages = [];
  messageIdSet.clear();
  isLoading = false;

  // Update UI
  updateConversationSelection();
  updateChatHeader();
  showMessagePanel();

  // Clear message container and show loading
  const container = document.getElementById("messageList");
  if (container) {
    container.innerHTML = `
      <div class="chat-loading flex items-center justify-center h-full text-gray-500">
        <div class="text-center">
          <i data-lucide="loader-2" class="size-8 mx-auto mb-2 animate-spin opacity-50"></i>
          <p class="text-sm">Loading messages...</p>
        </div>
      </div>
    `;
    if (window.lucide) window.lucide.createIcons();
  }

  // Load messages for new conversation (full load, not incremental)
  loadMessages(false);
}

/**
 * Render conversation list
 */
function renderConversationList() {
  const container = document.getElementById("conversationList");
  if (!container) return;

  if (conversations.length === 0) {
    container.innerHTML = `
      <div class="p-6 text-center text-gray-500">
        <i data-lucide="message-square" class="size-12 mx-auto mb-3 opacity-30"></i>
        <p class="text-sm">No conversations yet</p>
        <p class="text-xs mt-1">Start chatting with donors, hospitals, or support</p>
      </div>
    `;
    if (window.lucide) window.lucide.createIcons();
    return;
  }

  container.innerHTML = conversations
    .map((conv) => {
      const isSelected = conv.user_id === selectedUserId;
      const unreadCount = parseInt(conv.unread_count, 10) || 0;

      // Show unread badge only if there are unread messages
      const unreadBadge =
        unreadCount > 0
          ? `<span class="bg-red-600 text-white text-xs px-2 py-0.5 rounded-full">${unreadCount}</span>`
          : "";

      const roleColors = {
        admin: "bg-red-100 text-red-600",
        donor: "bg-green-100 text-green-600",
        hospital: "bg-blue-100 text-blue-600",
        seeker: "bg-orange-100 text-orange-600",
      };

      const roleColor =
        roleColors[conv.user_role] || "bg-gray-100 text-gray-600";
      const roleLabel =
        conv.user_role.charAt(0).toUpperCase() + conv.user_role.slice(1);

      // Message preview: show unread count only, nothing if all read
      let messagePreview = "";
      if (unreadCount > 0) {
        const msgText =
          unreadCount === 1 ? "unread message" : "unread messages";
        messagePreview = `<p class="text-sm text-red-600 font-medium truncate mt-1">${unreadCount} ${msgText}</p>`;
      }

      return `
        <div class="conversation-item p-4 border-b border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors ${isSelected ? "bg-red-50 border-l-4 border-l-red-600" : ""}"
             data-user-id="${conv.user_id}"
             data-user-name="${conv.user_name}"
             data-user-role="${conv.user_role}"
             onclick="window.chatSelectConversation(${conv.user_id}, '${conv.user_name.replace(/'/g, "\\'")}', '${conv.user_role}')">
          <div class="flex items-start gap-3">
            <div class="${roleColor} size-10 rounded-full flex items-center justify-center flex-shrink-0">
              <i data-lucide="${conv.user_role === "hospital" ? "building-2" : "user"}" class="size-5"></i>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between gap-2">
                <h4 class="font-medium text-gray-900 truncate">${conv.user_name}</h4>
                ${unreadBadge}
              </div>
              <p class="text-xs text-gray-500 mt-0.5">${roleLabel}</p>
              ${messagePreview}
              <p class="text-xs text-gray-400 mt-1">${conv.last_message_time}</p>
            </div>
          </div>
        </div>
      `;
    })
    .join("");

  if (window.lucide) window.lucide.createIcons();
}

/**
 * Update conversation selection UI
 */
function updateConversationSelection() {
  document.querySelectorAll(".conversation-item").forEach((item) => {
    const userId = parseInt(item.dataset.userId);
    if (userId === selectedUserId) {
      item.classList.add("bg-red-50", "border-l-4", "border-l-red-600");
    } else {
      item.classList.remove("bg-red-50", "border-l-4", "border-l-red-600");
    }
  });
}

// ===========================================
// MESSAGE MANAGEMENT
// ===========================================

/**
 * Load messages for selected conversation
 * @param {boolean} incremental - If true, only fetch new messages since lastTimestamp
 */
async function loadMessages(incremental = false) {
  if (!selectedUserId) {
    console.log("[Chat] No conversation selected, skipping load");
    return;
  }

  // Guard against concurrent requests for incremental loads
  if (incremental && isLoading) {
    console.log("[Chat] Already loading, skipping incremental load");
    return;
  }

  isLoading = true;

  // Capture current state for race condition detection
  const requestUserId = selectedUserId;
  const requestId = currentRequestId;

  try {
    let url = `${API_BASE}/messages.php?user_id=${requestUserId}`;
    if (incremental && lastTimestamp) {
      url += `&since=${encodeURIComponent(lastTimestamp)}`;
    }

    const response = await fetch(url, {
      credentials: "include",
    });

    // Check if conversation changed during fetch
    if (requestId !== currentRequestId || requestUserId !== selectedUserId) {
      console.log(
        "[Chat] Conversation changed during fetch, discarding results",
      );
      return;
    }

    const data = await response.json();

    // Double-check after parsing
    if (requestId !== currentRequestId || requestUserId !== selectedUserId) {
      console.log(
        "[Chat] Conversation changed after parse, discarding results",
      );
      return;
    }

    if (!response.ok) {
      if (response.status === 404) {
        showMessagePanelError("Unable to load conversation. User not found.");
      } else if (response.status === 401) {
        showMessagePanelError("Please log in to view messages.");
      } else {
        showMessagePanelError(data.message || "Failed to load messages.");
      }
      return;
    }

    if (data.success) {
      // Store current user ID from backend (authoritative source)
      if (data.current_user_id) {
        currentUserId = parseInt(data.current_user_id, 10);
      }

      if (incremental && data.messages && data.messages.length > 0) {
        // INCREMENTAL LOAD: Append only truly new messages
        const newMessages = [];

        for (const msg of data.messages) {
          const msgId = parseInt(msg.id, 10);
          if (!messageIdSet.has(msgId)) {
            messageIdSet.add(msgId);
            currentMessages.push(msg);
            newMessages.push(msg);
          }
        }

        if (newMessages.length > 0) {
          console.log(`[Chat] Appending ${newMessages.length} new messages`);
          appendMessagesToDOM(newMessages);
        }
      } else if (!incremental) {
        // FULL LOAD: Replace all messages
        currentMessages = data.messages || [];
        messageIdSet.clear();
        currentMessages.forEach((msg) =>
          messageIdSet.add(parseInt(msg.id, 10)),
        );

        console.log(`[Chat] Full load: ${currentMessages.length} messages`);
        renderAllMessages();
      }

      // Update timestamp for next incremental fetch
      if (data.last_timestamp) {
        lastTimestamp = data.last_timestamp;
      }

      // Update user info from backend
      if (data.other_user) {
        selectedUserName = data.other_user.name;
        selectedUserRole = data.other_user.role;
        updateChatHeader();
      }

      // Refresh conversation list on initial load only
      if (!incremental) {
        loadConversations();
      }
    }
  } catch (error) {
    console.error("Failed to load messages:", error);
    if (!incremental) {
      showMessagePanelError("Failed to load messages. Please try again.");
    }
  } finally {
    isLoading = false;
  }
}

/**
 * Render all messages (full re-render for initial load)
 * This completely replaces the message container content
 */
function renderAllMessages() {
  const container = document.getElementById("messageList");
  if (!container) {
    console.error("[Chat] Message container #messageList not found!");
    return;
  }

  // Clear container completely
  container.innerHTML = "";

  if (currentMessages.length === 0) {
    container.innerHTML = `
      <div class="flex items-center justify-center h-full text-gray-500">
        <div class="text-center">
          <i data-lucide="message-circle" class="size-16 mx-auto mb-4 opacity-30"></i>
          <p>No messages yet</p>
          <p class="text-sm mt-1">Send a message to start the conversation</p>
        </div>
      </div>
    `;
    if (window.lucide) window.lucide.createIcons();
    return;
  }

  // Create a document fragment for better performance
  const fragment = document.createDocumentFragment();
  let lastDate = null;

  currentMessages.forEach((msg) => {
    // Add date separator if date changed
    const msgDate = new Date(msg.created_at).toDateString();
    if (msgDate !== lastDate) {
      lastDate = msgDate;
      const separator = createDateSeparator(msg.created_at);
      fragment.appendChild(separator);
    }

    // Create and add message bubble
    const msgElement = createMessageElement(msg);
    fragment.appendChild(msgElement);
  });

  // Append all at once
  container.appendChild(fragment);

  // Scroll to bottom after render
  scrollToBottom(true);

  if (window.lucide) window.lucide.createIcons();
}

/**
 * Create a date separator element
 */
function createDateSeparator(dateString) {
  const dateLabel = isToday(dateString)
    ? "Today"
    : isYesterday(dateString)
      ? "Yesterday"
      : new Date(dateString).toLocaleDateString("en-US", {
          month: "short",
          day: "numeric",
          year: "numeric",
        });

  const separator = document.createElement("div");
  separator.className = "flex items-center justify-center my-4";
  separator.innerHTML = `<div class="bg-gray-200 text-gray-600 text-xs px-3 py-1 rounded-full">${dateLabel}</div>`;
  return separator;
}

/**
 * Append new messages to DOM (for incremental updates)
 * Only appends new messages without touching existing ones
 */
function appendMessagesToDOM(newMessages) {
  const container = document.getElementById("messageList");
  if (!container) {
    console.error("[Chat] Message container #messageList not found!");
    return;
  }

  if (!newMessages || newMessages.length === 0) {
    return;
  }

  // Remove empty state if present
  const emptyState = container.querySelector(
    ".flex.items-center.justify-center.h-full",
  );
  if (emptyState) {
    emptyState.remove();
  }

  // Determine last date shown in the existing messages
  const existingCount = currentMessages.length - newMessages.length;
  let lastDate = null;
  if (existingCount > 0) {
    lastDate = new Date(
      currentMessages[existingCount - 1].created_at,
    ).toDateString();
  }

  // Create fragment for new messages
  const fragment = document.createDocumentFragment();

  newMessages.forEach((msg) => {
    // Add date separator if date changed
    const msgDate = new Date(msg.created_at).toDateString();
    if (msgDate !== lastDate) {
      lastDate = msgDate;
      const separator = createDateSeparator(msg.created_at);
      fragment.appendChild(separator);
    }

    // Create and add message bubble
    const msgElement = createMessageElement(msg);
    fragment.appendChild(msgElement);
  });

  // Append all at once
  container.appendChild(fragment);

  // Scroll to bottom (not forced, only if user is near bottom)
  scrollToBottom(false);

  if (window.lucide) window.lucide.createIcons();
}

/**
 * Create a single message DOM element
 * Uses is_mine flag from backend or falls back to sender_id comparison
 * Theme: BloodConnect - Red for sent messages, neutral gray for received
 */
function createMessageElement(msg) {
  // Determine if message is mine
  const isMine = isMessageMine(msg);

  const wrapper = document.createElement("div");
  wrapper.className = `flex ${isMine ? "justify-end" : "justify-start"} mb-1 px-3`;
  wrapper.setAttribute("data-message-id", msg.id);

  if (isMine) {
    // My message - RIGHT side, RED bubble (BloodConnect theme)
    wrapper.innerHTML = `
      <div class="max-w-[70%] relative">
        <div class="bg-gradient-to-br from-red-500 to-red-600 text-white px-4 py-2 rounded-[20px] rounded-br-[4px] shadow-sm">
          <p class="text-[15px] leading-snug whitespace-pre-wrap break-words">${escapeHtml(msg.message)}</p>
        </div>
        <p class="text-[10px] text-gray-500 mt-1 text-right pr-1">${msg.time}</p>
      </div>
    `;
  } else {
    // Other user's message - LEFT side, LIGHT GRAY bubble
    wrapper.innerHTML = `
      <div class="max-w-[70%] relative">
        <div class="bg-white text-gray-800 px-4 py-2 rounded-[20px] rounded-bl-[4px] shadow-sm border border-gray-200">
          <p class="text-[15px] leading-snug whitespace-pre-wrap break-words">${escapeHtml(msg.message)}</p>
        </div>
        <p class="text-[10px] text-gray-500 mt-1 pl-1">${msg.time}</p>
      </div>
    `;
  }

  return wrapper;
}

/**
 * Determine if a message is from the current user
 * Checks is_mine flag from backend, with fallback to sender_id comparison
 */
function isMessageMine(msg) {
  // Debug log to trace issues
  // console.log('[Chat] isMessageMine check:', {
  //   msgId: msg.id,
  //   is_mine: msg.is_mine,
  //   is_mine_type: typeof msg.is_mine,
  //   sender_id: msg.sender_id,
  //   currentUserId: currentUserId
  // });

  // Backend sends is_mine as boolean - check it directly first
  if (typeof msg.is_mine === "boolean") {
    return msg.is_mine;
  }

  // Handle various truthy/falsy representations
  if (
    msg.is_mine === true ||
    msg.is_mine === 1 ||
    msg.is_mine === "1" ||
    msg.is_mine === "true"
  ) {
    return true;
  }
  if (
    msg.is_mine === false ||
    msg.is_mine === 0 ||
    msg.is_mine === "0" ||
    msg.is_mine === "false"
  ) {
    return false;
  }

  // Fallback: compare sender_id with currentUserId (both as integers)
  if (currentUserId && msg.sender_id) {
    const senderId = parseInt(msg.sender_id, 10);
    const myId = parseInt(currentUserId, 10);
    return senderId === myId;
  }

  // Default to false if we can't determine
  return false;
}

/**
 * Send a message
 */
async function sendMessage() {
  const input = document.getElementById("messageInput");
  const sendBtn = document.getElementById("sendMessageBtn");

  if (!input || !selectedUserId) return;

  const message = input.value.trim();
  if (!message) return;

  if (message.length > CHAT_CONFIG.maxMessageLength) {
    showChatError(
      `Message too long (max ${CHAT_CONFIG.maxMessageLength} characters)`,
    );
    return;
  }

  // Disable button
  if (sendBtn) {
    sendBtn.disabled = true;
    sendBtn.innerHTML =
      '<i data-lucide="loader-2" class="size-5 animate-spin"></i>';
    if (window.lucide) window.lucide.createIcons();
  }

  try {
    const response = await fetch(`${API_BASE}/send.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "include",
      body: JSON.stringify({
        receiver_id: selectedUserId,
        message: message,
      }),
    });

    const data = await response.json();

    if (data.success) {
      // Clear input immediately for better UX
      input.value = "";
      updateCharCounter();

      const msgId = parseInt(data.message_id, 10);
      const createdAt = data.created_at || new Date().toISOString();

      // Create optimistic message with explicit is_mine: true
      const newMsg = {
        id: msgId,
        sender_id: currentUserId,
        sender_name: "You",
        is_mine: true, // Explicitly mark as mine for optimistic UI
        message: message,
        created_at: createdAt,
        time: new Date(createdAt).toLocaleTimeString("en-US", {
          hour: "numeric",
          minute: "2-digit",
          hour12: true,
        }),
        is_read: false,
      };

      // Add to tracking set to prevent duplicate on poll
      messageIdSet.add(msgId);

      // Add to array and append to DOM
      currentMessages.push(newMsg);
      appendMessagesToDOM([newMsg]);

      // Force scroll to bottom after sending
      scrollToBottom(true);

      // Update timestamp for polling
      lastTimestamp = createdAt;

      // Refresh conversations (shows latest message in list)
      loadConversations();
    } else {
      showChatError(data.message || "Failed to send message");
    }
  } catch (error) {
    console.error("Failed to send message:", error);
    showChatError("Failed to send message");
  } finally {
    if (sendBtn) {
      sendBtn.disabled = false;
      sendBtn.innerHTML = '<i data-lucide="send" class="size-5"></i>';
      if (window.lucide) window.lucide.createIcons();
    }
  }
}

// ===========================================
// POLLING
// ===========================================

/**
 * Start polling for new messages
 */
function startPolling() {
  // Always stop any existing polling first
  stopPolling();

  const poll = () => {
    if (selectedUserId) {
      loadMessages(true);
    }
    loadUnreadCount();

    // Schedule next poll
    const interval = getPollingInterval();
    pollingInterval = setTimeout(poll, interval);
  };

  // Start polling
  pollingInterval = setTimeout(poll, getPollingInterval());
}

/**
 * Stop polling
 */
function stopPolling() {
  if (pollingInterval) {
    clearTimeout(pollingInterval);
    pollingInterval = null;
  }
}

/**
 * Get current polling interval based on activity
 */
function getPollingInterval() {
  if (!isTabVisible) {
    return CHAT_CONFIG.backgroundInterval;
  }

  const timeSinceActivity = Date.now() - lastActivity;
  if (timeSinceActivity > CHAT_CONFIG.idleTimeout) {
    return CHAT_CONFIG.idleInterval;
  }

  return CHAT_CONFIG.activeInterval;
}

/**
 * Handle visibility change
 */
function handleVisibilityChange() {
  isTabVisible = !document.hidden;

  if (isTabVisible) {
    if (selectedUserId) {
      loadMessages(true);
    }
    loadConversations();
  }
}

/**
 * Update last activity timestamp
 */
function updateActivity() {
  lastActivity = Date.now();
}

/**
 * Load unread count for badge
 */
async function loadUnreadCount() {
  try {
    const response = await fetch(`${API_BASE}/unread-count.php`, {
      credentials: "include",
    });

    if (!response.ok) return;

    const data = await response.json();

    if (data.success) {
      updateChatBadge(data.total_unread);
    }
  } catch (error) {
    console.error("Failed to load unread count:", error);
  }
}

// ===========================================
// UI HELPERS
// ===========================================

/**
 * Update chat header
 */
function updateChatHeader() {
  const header = document.getElementById("chatHeader");
  if (!header || !selectedUserId) return;

  const roleColors = {
    admin: "bg-red-100 text-red-600",
    donor: "bg-green-100 text-green-600",
    hospital: "bg-blue-100 text-blue-600",
    seeker: "bg-orange-100 text-orange-600",
  };

  const roleColor = roleColors[selectedUserRole] || "bg-gray-100 text-gray-600";
  const roleLabel = selectedUserRole
    ? selectedUserRole.charAt(0).toUpperCase() + selectedUserRole.slice(1)
    : "";

  header.innerHTML = `
    <div class="flex items-center gap-3">
      <div class="${roleColor} size-10 rounded-full flex items-center justify-center">
        <i data-lucide="${selectedUserRole === "hospital" ? "building-2" : "user"}" class="size-5"></i>
      </div>
      <div>
        <h3 class="font-semibold text-gray-900">${selectedUserName}</h3>
        <p class="text-sm text-gray-500">${roleLabel}</p>
      </div>
    </div>
  `;

  if (window.lucide) window.lucide.createIcons();
}

/**
 * Show message panel
 */
function showMessagePanel() {
  const emptyState = document.getElementById("chatEmptyState");
  const messagePanel = document.getElementById("messagePanel");

  if (emptyState) emptyState.classList.add("hidden");
  if (messagePanel) messagePanel.classList.remove("hidden");
}

/**
 * Scroll message list to bottom
 * @param {boolean} force - If true, always scroll. If false, only scroll if user is near bottom.
 */
function scrollToBottom(force = true) {
  const container = document.getElementById("messageList");
  if (!container) return;

  const isNearBottom =
    container.scrollHeight - container.scrollTop - container.clientHeight < 150;

  if (force || isNearBottom) {
    // Use double requestAnimationFrame to ensure DOM has updated
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        container.scrollTop = container.scrollHeight;
      });
    });
  }
}

/**
 * Update character counter
 */
function updateCharCounter() {
  const input = document.getElementById("messageInput");
  const counter = document.getElementById("charCounter");

  if (input && counter) {
    const length = input.value.length;
    counter.textContent = `${length}/${CHAT_CONFIG.maxMessageLength}`;

    if (length > CHAT_CONFIG.maxMessageLength * 0.9) {
      counter.classList.add("text-red-600");
    } else {
      counter.classList.remove("text-red-600");
    }
  }
}

/**
 * Update chat badge
 */
function updateChatBadge(count) {
  const badge = document.getElementById("chatUnreadBadge");
  if (badge) {
    if (count > 0) {
      badge.textContent = count > 99 ? "99+" : count;
      badge.classList.remove("hidden");
    } else {
      badge.classList.add("hidden");
    }
  }
}

/**
 * Setup contact admin button
 */
async function setupContactAdmin() {
  const btn = document.getElementById("contactAdminBtn");
  if (!btn) return;

  btn.addEventListener("click", async () => {
    try {
      selectConversation(15, "Admin", "admin");
    } catch (error) {
      console.error("Failed to contact admin:", error);
    }
  });
}

/**
 * Show error in message panel
 */
function showMessagePanelError(message) {
  const container = document.getElementById("messageList");
  if (!container) return;

  container.innerHTML = `
    <div class="flex items-center justify-center h-full">
      <div class="text-center p-6">
        <div class="size-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <i data-lucide="alert-circle" class="size-8 text-red-600"></i>
        </div>
        <p class="text-gray-900 font-medium mb-2">Unable to Load Conversation</p>
        <p class="text-gray-500 text-sm">${escapeHtml(message)}</p>
        <button onclick="window.location.reload()" class="mt-4 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm transition-colors">
          Try Again
        </button>
      </div>
    </div>
  `;

  if (window.lucide) window.lucide.createIcons();
}

/**
 * Show chat error notification
 */
function showChatError(message) {
  if (window.showError) {
    window.showError(message);
  } else {
    alert(message);
  }
}

/**
 * Escape HTML
 */
function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

/**
 * Check if date is today
 */
function isToday(dateString) {
  const date = new Date(dateString);
  const today = new Date();
  return date.toDateString() === today.toDateString();
}

/**
 * Check if date is yesterday
 */
function isYesterday(dateString) {
  const date = new Date(dateString);
  const yesterday = new Date();
  yesterday.setDate(yesterday.getDate() - 1);
  return date.toDateString() === yesterday.toDateString();
}

// ===========================================
// GLOBAL EXPORTS
// ===========================================

window.chatSelectConversation = selectConversation;
window.chatSendMessage = sendMessage;
window.chatLoadConversations = loadConversations;

export { loadConversations, loadMessages, sendMessage, loadUnreadCount };
