/**
<<<<<<< HEAD
 * Chat Component
 * Shared chat functionality for all portals
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
    return;
  }

  // Guard against concurrent requests for incremental loads
  if (incremental && isLoading) {
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
      return;
    }

    const data = await response.json();

    // Double-check after parsing
    if (requestId !== currentRequestId || requestUserId !== selectedUserId) {
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
          appendMessagesToDOM(newMessages);
        }
      } else if (!incremental) {
        // FULL LOAD: Replace all messages
        currentMessages = data.messages || [];
        messageIdSet.clear();
        currentMessages.forEach((msg) =>
          messageIdSet.add(parseInt(msg.id, 10)),
        );

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
=======
 * Chat Module - AJAX Polling Implementation
 * Manages real-time messaging with smooth UI and 2-second polling
 * 
 * Features:
 *   - Proper loading/empty states
 *   - Smooth message rendering with sender/receiver distinction
 *   - Read receipts and timestamps
 *   - Disabled states during sending
 *   - Auto-scroll on new messages
 * 
 * Usage:
 *   import { initChat, stopPolling, initChatModule } from '/src/scripts/components/chat.js';
 *   initChatModule(); // On page load
 *   initChat(receiverId, receiverName, requestId); // To open specific chat
 */

const CHAT_API = '/api/chat';

let chatState = {
    currentUserId: null,
    currentUserName: null,
    currentUserRole: null,
    otherUserId: null,
    otherUserName: null,
    otherUserRole: null,
    requestId: null,
    donationId: null,
    voluntaryId: null,
    lastMessageId: 0,
    pollInterval: 2000,  // 2 seconds
    isPolling: false,
    pollTimeoutId: null,
    renderedMessageIds: new Set(),
    currentView: 'conversations', // 'conversations' or 'search'
    searchTimeout: null,
    eventListenersAdded: false,
    isSending: false,
    hasActiveConversation: false,
    hasPermission: true
};

// Role color mapping for badges
const ROLE_COLORS = {
    'donor': { bg: 'bg-green-100', text: 'text-green-700', border: 'border-green-200' },
    'hospital': { bg: 'bg-blue-100', text: 'text-blue-700', border: 'border-blue-200' },
    'seeker': { bg: 'bg-orange-100', text: 'text-orange-700', border: 'border-orange-200' },
    'admin': { bg: 'bg-purple-100', text: 'text-purple-700', border: 'border-purple-200' }
};

/**
 * Fetch current user info from session
 */
async function fetchCurrentUser() {
    if (chatState.currentUserId) return true;
    
    try {
        const response = await fetch('/api/auth/check.php', {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.success && data.logged_in) {
            chatState.currentUserId = data.user.id;
            chatState.currentUserName = data.user.name;
            chatState.currentUserRole = data.user.role;
            return true;
        }
    } catch (error) {
        console.error('Failed to fetch current user:', error);
    }
    return false;
}

/**
 * Show loading state in messages container
 */
function showMessagesLoading() {
    const container = document.getElementById('messagesContainer');
    if (!container) return;
    
    container.innerHTML = `
        <div class="flex flex-col items-center justify-center h-full">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-red-600 mb-4"></div>
            <p class="text-gray-400 text-sm">Loading messages...</p>
        </div>
    `;
}

/**
 * Show empty chat state (no conversation selected)
 */
function showEmptyChatState() {
    const container = document.getElementById('messagesContainer');
    if (!container) return;
    
    container.innerHTML = `
        <div class="flex flex-col items-center justify-center h-full text-center px-8">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-6">
                <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No conversation selected</h3>
            <p class="text-gray-500 text-sm max-w-xs">Choose a conversation from the list or start a new one by searching for users.</p>
        </div>
    `;
    
    // Reset chat header
    const title = document.getElementById('chatTitle');
    const subtitle = document.getElementById('chatSubtitle');
    const headerBadge = document.getElementById('chatHeaderBadge');
    
    if (title) title.textContent = 'Select a conversation';
    if (subtitle) subtitle.textContent = '';
    if (headerBadge) headerBadge.innerHTML = '';
    
    // Disable input
    updateInputState(false);
}

/**
 * Show empty messages state (conversation selected but no messages)
 */
function showNoMessagesState(userName) {
    const container = document.getElementById('messagesContainer');
    if (!container) return;
    
    container.innerHTML = `
        <div class="flex flex-col items-center justify-center h-full text-center px-8">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
            </div>
            <h3 class="text-base font-medium text-gray-900 mb-1">No messages yet</h3>
            <p class="text-gray-500 text-sm">Say hi to ${escapeHtml(userName)} to start the conversation!</p>
        </div>
    `;
}

/**
 * Update message input state (enabled/disabled)
 */
function updateInputState(enabled, sending = false) {
    const input = document.getElementById('messageInput');
    const sendBtn = document.getElementById('sendMessageBtn');
    
    if (input) {
        input.disabled = !enabled || sending;
        if (!enabled) {
            input.placeholder = 'Select a conversation to send messages...';
        } else if (sending) {
            input.placeholder = 'Sending...';
        } else {
            input.placeholder = 'Type a message... (Press Enter to send)';
        }
    }
    
    if (sendBtn) {
        sendBtn.disabled = !enabled || sending;
        if (sending) {
            sendBtn.classList.add('opacity-50', 'cursor-not-allowed');
            sendBtn.innerHTML = `
                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            `;
        } else {
            sendBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            sendBtn.innerHTML = `
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                </svg>
            `;
        }
    }
}

/**
 * Initialize chat for a conversation
 */
export async function initChat(receiverId, receiverName, requestId = null, donationId = null, voluntaryId = null, receiverRole = null) {
    // Fetch current user if not already done
    await fetchCurrentUser();
    
    // Stop any existing polling
    stopPolling();
    
    // Show loading
    showMessagesLoading();
    
    chatState.otherUserId = receiverId;
    chatState.otherUserName = receiverName;
    chatState.otherUserRole = receiverRole;
    chatState.requestId = requestId;
    chatState.donationId = donationId;
    chatState.voluntaryId = voluntaryId;
    chatState.lastMessageId = 0;
    chatState.renderedMessageIds.clear();
    chatState.hasActiveConversation = true;

    setupEventListeners();
    updateChatHeader();
    
    // Enable input for this conversation
    updateInputState(true);
    
    await loadInitialMessages();
    startPolling();
}

/**
 * Stop polling
 */
export function stopPolling() {
    chatState.isPolling = false;
    if (chatState.pollTimeoutId) {
        clearTimeout(chatState.pollTimeoutId);
        chatState.pollTimeoutId = null;
    }
}

/**
 * Setup chat UI event listeners (only once)
 */
function setupEventListeners() {
    // Prevent adding event listeners multiple times
    if (chatState.eventListenersAdded) return;
    chatState.eventListenersAdded = true;
    
    const sendBtn = document.getElementById('sendMessageBtn');
    const input = document.getElementById('messageInput');

    if (sendBtn) {
        sendBtn.addEventListener('click', sendMessage);
    }

    if (input) {
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        
        // Auto-resize textarea
        input.addEventListener('input', () => {
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 120) + 'px';
        });
    }
}

/**
 * Update chat header with other user info
 */
function updateChatHeader() {
    const title = document.getElementById('chatTitle');
    const subtitle = document.getElementById('chatSubtitle');
    const headerBadge = document.getElementById('chatHeaderBadge');

    if (title) {
        title.textContent = chatState.otherUserName || 'Select a conversation';
    }
    
    if (subtitle && chatState.otherUserRole) {
        const roleLabel = chatState.otherUserRole.charAt(0).toUpperCase() + chatState.otherUserRole.slice(1);
        subtitle.textContent = roleLabel;
    } else if (subtitle) {
        subtitle.textContent = '';
    }
    
    // Add role badge
    if (headerBadge && chatState.otherUserRole) {
        const colors = ROLE_COLORS[chatState.otherUserRole] || ROLE_COLORS.admin;
        headerBadge.innerHTML = `<span class="px-2 py-0.5 text-xs rounded-full ${colors.bg} ${colors.text}">${chatState.otherUserRole.charAt(0).toUpperCase() + chatState.otherUserRole.slice(1)}</span>`;
    }
    
    // Show context badge if available
    if (chatState.requestId) {
        const contextEl = document.getElementById('chatContext');
        if (contextEl) {
            contextEl.innerHTML = `<span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">Request #${chatState.requestId}</span>`;
        }
    }
}

/**
 * Load initial messages for conversation
 */
async function loadInitialMessages() {
    const container = document.getElementById('messagesContainer');
    
    try {
        const params = new URLSearchParams({
            user_id: chatState.otherUserId,
            limit: 50,
            offset: 0
        });

        const response = await fetch(`${CHAT_API}/get-messages.php?${params}`, {
            credentials: 'include'
        });

        if (!response.ok) {
            console.error('Failed to load initial messages:', response.status);
            if (container) {
                container.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-full text-center px-8">
                        <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-base font-medium text-gray-900 mb-1">Failed to load messages</h3>
                        <p class="text-gray-500 text-sm">Please try again later.</p>
                        <button onclick="location.reload()" class="mt-4 text-red-600 text-sm hover:underline">Refresh page</button>
                    </div>
                `;
            }
            return;
        }

        const data = await response.json();
        
        // Clear container
        if (container) {
            container.innerHTML = '';
        }
        
        if (data.success && data.conversation.messages.length > 0) {
            const messages = data.conversation.messages;
            chatState.lastMessageId = messages[messages.length - 1].id;
            
            // Add date separator at start
            const firstDate = formatDateSeparator(messages[0].created_at);
            addDateSeparator(container, firstDate);
            
            renderMessages(messages);
            scrollToBottom();
        } else if (data.success) {
            // No messages yet - show empty state
            showNoMessagesState(chatState.otherUserName);
        } else {
            console.error('Load messages failed:', data.error);
            if (container) {
                container.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-full text-center px-8">
                        <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <p class="text-gray-500 text-sm">${escapeHtml(data.error || 'Failed to load messages')}</p>
                    </div>
                `;
            }
        }
    } catch (error) {
        console.error('Load Initial Messages Error:', error);
        if (container) {
            container.innerHTML = `
                <div class="flex flex-col items-center justify-center h-full text-center px-8">
                    <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-base font-medium text-gray-900 mb-1">Connection error</h3>
                    <p class="text-gray-500 text-sm">Please check your internet connection.</p>
                </div>
            `;
        }
    }
}

/**
 * Add date separator to container
 */
function addDateSeparator(container, dateText) {
    const separator = document.createElement('div');
    separator.className = 'flex items-center justify-center my-4';
    separator.innerHTML = `
        <div class="flex-1 h-px bg-gray-200"></div>
        <span class="px-3 text-xs text-gray-400 font-medium">${escapeHtml(dateText)}</span>
        <div class="flex-1 h-px bg-gray-200"></div>
    `;
    container.appendChild(separator);
}

/**
 * Format date for separator
 */
function formatDateSeparator(timestamp) {
    const date = new Date(timestamp);
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    
    if (date.toDateString() === today.toDateString()) {
        return 'Today';
    } else if (date.toDateString() === yesterday.toDateString()) {
        return 'Yesterday';
    } else {
        return date.toLocaleDateString('en-US', { 
            weekday: 'long', 
            month: 'short', 
            day: 'numeric' 
        });
    }
}

/**
 * Start polling for new messages
 */
function startPolling() {
    chatState.isPolling = true;
    pollForNewMessages();
}

/**
 * Poll for new messages recursively
 */
function pollForNewMessages() {
    if (!chatState.isPolling || !chatState.hasActiveConversation) {
        return;
    }

    fetchNewMessages();

    chatState.pollTimeoutId = setTimeout(() => {
        pollForNewMessages();
    }, chatState.pollInterval);
}

/**
 * Fetch new messages from server
 */
async function fetchNewMessages() {
    if (!chatState.otherUserId) return;
    
    try {
        const params = new URLSearchParams({
            user_id: chatState.otherUserId,
            since_id: chatState.lastMessageId,
            limit: 50
        });

        const response = await fetch(`${CHAT_API}/get-messages.php?${params}`, {
            credentials: 'include'
        });

        if (!response.ok) {
            console.error('Poll error:', response.status);
            return;
        }

        const data = await response.json();

        if (data.success && data.conversation.messages.length > 0) {
            const newMessages = data.conversation.messages;

            // Update last message ID
            chatState.lastMessageId = newMessages[newMessages.length - 1].id;

            // Filter out duplicates and own sent messages (already rendered optimistically)
            const uniqueMessages = newMessages.filter(msg => !chatState.renderedMessageIds.has(msg.id));
            
            if (uniqueMessages.length > 0) {
                // Check if we need to remove the empty state
                const container = document.getElementById('messagesContainer');
                if (container && container.querySelector('.text-center.flex-col')) {
                    container.innerHTML = '';
                }
                
                renderMessages(uniqueMessages);
                
                // Only auto-scroll if user is near the bottom
                if (isNearBottom()) {
                    scrollToBottom();
                }
            }
        }
    } catch (error) {
        console.error('Fetch New Messages Error:', error);
    }
}

/**
 * Check if scrolled near bottom
 */
function isNearBottom() {
    const container = document.getElementById('messagesContainer');
    if (!container) return true;
    
    const threshold = 100;
    return container.scrollHeight - container.scrollTop - container.clientHeight < threshold;
}

/**
 * Render messages in chat area
 */
function renderMessages(messages) {
    const container = document.getElementById('messagesContainer');
    if (!container) return;

    // Use current user ID from chatState
    const currentUserId = chatState.currentUserId;

    messages.forEach(msg => {
        // Skip if already rendered
        if (chatState.renderedMessageIds.has(msg.id)) {
            return;
        }

        chatState.renderedMessageIds.add(msg.id);

        const isOwn = currentUserId && parseInt(msg.sender_id) === parseInt(currentUserId);
        const messageEl = createMessageElement(msg, isOwn);
        container.appendChild(messageEl);
    });
}

/**
 * Create message element with enhanced styling
 */
function createMessageElement(msg, isOwn) {
    const wrapper = document.createElement('div');
    wrapper.className = `flex ${isOwn ? 'justify-end' : 'justify-start'} mb-3 message-appear`;
    wrapper.setAttribute('data-message-id', msg.id);

    const contentDiv = document.createElement('div');
    contentDiv.className = `max-w-[75%] rounded-2xl px-4 py-2.5 shadow-sm ${
        isOwn
            ? 'bg-red-600 text-white rounded-br-md'
            : 'bg-white text-gray-900 rounded-bl-md border border-gray-100'
    }`;

    // Message text
    const messageP = document.createElement('p');
    messageP.className = 'text-sm break-words whitespace-pre-wrap';
    messageP.textContent = msg.message;
    contentDiv.appendChild(messageP);

    // Time and read status footer
    const footerDiv = document.createElement('div');
    footerDiv.className = `flex items-center gap-1.5 mt-1 ${isOwn ? 'justify-end' : 'justify-start'}`;
    
    const timeSpan = document.createElement('span');
    timeSpan.className = `text-[10px] ${isOwn ? 'text-red-200' : 'text-gray-400'}`;
    timeSpan.textContent = formatTime(msg.created_at);
    footerDiv.appendChild(timeSpan);

    // Add read receipt for own messages
    if (isOwn) {
        const readIcon = document.createElement('span');
        readIcon.className = 'text-[10px]';
        if (msg.is_read) {
            readIcon.innerHTML = `<svg class="w-3.5 h-3.5 text-red-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>`;
            readIcon.title = 'Read';
        } else {
            readIcon.innerHTML = `<svg class="w-3.5 h-3.5 text-red-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
            </svg>`;
            readIcon.title = 'Sent';
        }
        footerDiv.appendChild(readIcon);
    }

    contentDiv.appendChild(footerDiv);
    wrapper.appendChild(contentDiv);
    
    return wrapper;
>>>>>>> 4a2d98e84ac74c58d418328fd399f3b8c0f065fb
}

/**
 * Send a message
 */
async function sendMessage() {
<<<<<<< HEAD
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
=======
    const input = document.getElementById('messageInput');
    const message = input ? input.value.trim() : '';

    if (!message || !chatState.otherUserId || chatState.isSending) {
        return;
    }

    // Set sending state
    chatState.isSending = true;
    updateInputState(true, true);

    try {
        const payload = {
            receiver_id: chatState.otherUserId,
            message: message
        };

        if (chatState.requestId) {
            payload.request_id = chatState.requestId;
        }
        if (chatState.donationId) {
            payload.donation_id = chatState.donationId;
        }
        if (chatState.voluntaryId) {
            payload.voluntary_donation_id = chatState.voluntaryId;
        }

        const response = await fetch(`${CHAT_API}/send-message.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (data.success) {
            // Clear input
            if (input) {
                input.value = '';
                input.style.height = 'auto';
            }

            // Update last message id
            chatState.lastMessageId = data.message.id;

            // Check if we need to clear empty state
            const container = document.getElementById('messagesContainer');
            if (container) {
                const emptyState = container.querySelector('.text-center.flex-col');
                if (emptyState) {
                    container.innerHTML = '';
                }
                
                // Render sent message immediately (optimistic)
                const msgEl = createMessageElement(data.message, true);
                container.appendChild(msgEl);
                chatState.renderedMessageIds.add(data.message.id);
            }

            scrollToBottom();

            // Refresh conversations list to update last message
            loadConversations();

        } else {
            showToast('Error sending message: ' + (data.error || 'Unknown error'), 'error');
        }
    } catch (error) {
        console.error('Send Message Error:', error);
        showToast('Failed to send message. Please try again.', 'error');
    } finally {
        chatState.isSending = false;
        updateInputState(true, false);
        // Focus back on input
        if (input) input.focus();
    }
}

/**
 * Show a toast notification
 */
function showToast(message, type = 'info') {
    // Remove existing toast
    const existing = document.querySelector('.chat-toast');
    if (existing) existing.remove();
    
    const toast = document.createElement('div');
    toast.className = `chat-toast fixed bottom-24 left-1/2 transform -translate-x-1/2 px-4 py-2 rounded-lg shadow-lg z-50 text-sm font-medium transition-all duration-300 ${
        type === 'error' ? 'bg-red-600 text-white' : 'bg-gray-800 text-white'
    }`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    // Auto remove
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/**
 * Scroll to bottom smoothly
 */
function scrollToBottom() {
    const container = document.getElementById('messagesContainer');
    if (container) {
        setTimeout(() => {
            container.scrollTo({
                top: container.scrollHeight,
                behavior: 'smooth'
            });
        }, 50);
    }
}

/**
 * Format timestamp
 */
function formatTime(timestamp) {
    try {
        const date = new Date(timestamp);
        const now = new Date();
        const isToday = date.toDateString() === now.toDateString();
        
        if (isToday) {
            return date.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        } else {
            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric'
            }) + ' ' + date.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        }
    } catch (e) {
        return timestamp;
    }
}

/**
 * Show loading state in conversations list
 */
function showConversationsLoading() {
    const container = document.getElementById('conversationsList');
    if (!container) return;
    
    container.innerHTML = `
        <div class="p-4">
            ${[1,2,3].map(() => `
                <div class="flex items-center gap-3 p-3 animate-pulse">
                    <div class="w-10 h-10 bg-gray-200 rounded-full"></div>
                    <div class="flex-1">
                        <div class="h-4 bg-gray-200 rounded w-24 mb-2"></div>
                        <div class="h-3 bg-gray-100 rounded w-32"></div>
                    </div>
                </div>
            `).join('')}
        </div>
    `;
}

/**
 * Load and display conversations list
 */
export async function loadConversations() {
    showConversationsLoading();
    
    try {
        const response = await fetch(`${CHAT_API}/conversations.php`, {
            credentials: 'include'
        });

        const data = await response.json();

        if (data.success) {
            chatState.currentView = 'conversations';
            renderConversations(data.data);
        } else {
            const container = document.getElementById('conversationsList');
            if (container) {
                container.innerHTML = `
                    <div class="p-6 text-center">
                        <p class="text-gray-500 text-sm">${escapeHtml(data.error || 'Failed to load conversations')}</p>
                        <button onclick="location.reload()" class="mt-2 text-red-600 text-sm hover:underline">Try again</button>
                    </div>
                `;
            }
        }
    } catch (error) {
        console.error('Load Conversations Error:', error);
        const container = document.getElementById('conversationsList');
        if (container) {
            container.innerHTML = `
                <div class="p-6 text-center">
                    <p class="text-gray-500 text-sm">Connection error</p>
                    <button onclick="location.reload()" class="mt-2 text-red-600 text-sm hover:underline">Try again</button>
                </div>
            `;
        }
    }
}

/**
 * Search for users to start new conversations
 */
export async function searchUsers(query = '', roleFilter = '') {
    const container = document.getElementById('conversationsList');
    if (!container) return;
    
    // Show loading
    container.innerHTML = `
        <div class="p-3 border-b bg-gray-50 cursor-pointer hover:bg-gray-100 flex items-center gap-2 text-gray-600" id="backToConversations">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            <span class="text-sm font-medium">Back to Conversations</span>
        </div>
        <div class="p-4 flex justify-center">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-red-600"></div>
        </div>
    `;
    
    // Add back button handler
    document.getElementById('backToConversations')?.addEventListener('click', () => loadConversations());
    
    try {
        const params = new URLSearchParams();
        if (query) params.append('search', query);
        if (roleFilter) params.append('role', roleFilter);
        params.append('limit', '30');

        const response = await fetch(`${CHAT_API}/search-users.php?${params}`, {
            credentials: 'include'
        });

        const data = await response.json();

        if (data.success) {
            chatState.currentView = 'search';
            renderUserSearchResults(data.data.users, data.data.allowed_roles);
        }
    } catch (error) {
        console.error('Search Users Error:', error);
    }
}

/**
 * Render user search results
 */
function renderUserSearchResults(users, allowedRoles) {
    const container = document.getElementById('conversationsList');
    if (!container) return;

    container.innerHTML = '';

    // Add back button to return to conversations
    const backBtn = document.createElement('div');
    backBtn.className = 'p-3 border-b bg-gray-50 cursor-pointer hover:bg-gray-100 flex items-center gap-2 text-gray-600 sticky top-0';
    backBtn.innerHTML = `
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
        <span class="text-sm font-medium">Back to Conversations</span>
    `;
    backBtn.onclick = () => loadConversations();
    container.appendChild(backBtn);

    // Add role filter tabs
    if (allowedRoles && allowedRoles.length > 0) {
        const filterDiv = document.createElement('div');
        filterDiv.className = 'p-2 border-b flex flex-wrap gap-1 bg-white sticky top-[44px]';
        filterDiv.innerHTML = `
            <button class="role-filter-btn px-2.5 py-1 text-xs rounded-full bg-red-600 text-white" data-role="">All</button>
            ${allowedRoles.map(role => `
                <button class="role-filter-btn px-2.5 py-1 text-xs rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors" data-role="${role}">${role.charAt(0).toUpperCase() + role.slice(1)}s</button>
            `).join('')}
        `;
        container.appendChild(filterDiv);

        // Add filter button handlers
        filterDiv.querySelectorAll('.role-filter-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                // Update active state
                filterDiv.querySelectorAll('.role-filter-btn').forEach(b => {
                    b.className = 'role-filter-btn px-2.5 py-1 text-xs rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors';
                });
                btn.className = 'role-filter-btn px-2.5 py-1 text-xs rounded-full bg-red-600 text-white';
                
                const searchInput = document.getElementById('searchInput');
                const query = searchInput ? searchInput.value : '';
                searchUsers(query, btn.dataset.role);
            });
        });
    }

    if (users.length === 0) {
        const emptyDiv = document.createElement('div');
        emptyDiv.className = 'p-8 text-center';
        emptyDiv.innerHTML = `
            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <p class="text-gray-500 text-sm">No users found</p>
            <p class="text-gray-400 text-xs mt-1">Try a different search term</p>
        `;
        container.appendChild(emptyDiv);
        return;
    }

    // Section header
    const headerDiv = document.createElement('div');
    headerDiv.className = 'px-3 py-2 bg-gray-50 border-b';
    headerDiv.innerHTML = `<span class="text-xs font-medium text-gray-500 uppercase tracking-wide">${users.length} user${users.length !== 1 ? 's' : ''} found</span>`;
    container.appendChild(headerDiv);

    // Render users
    users.forEach(user => {
        const item = document.createElement('div');
        item.className = 'cursor-pointer hover:bg-gray-50 p-3 border-b transition-colors';
        item.onclick = () => startNewConversation(user);

        const colors = ROLE_COLORS[user.role] || ROLE_COLORS.admin;

        const contextInfo = user.context_label ? `<span class="text-xs text-gray-400">${escapeHtml(user.context_label)}</span>` : '';
        const bloodType = user.blood_type ? `<span class="text-xs bg-red-50 text-red-600 px-1.5 py-0.5 rounded font-medium">${user.blood_type}</span>` : '';
        const hospitalName = user.hospital_name ? `<span class="text-xs text-blue-500">${escapeHtml(user.hospital_name)}</span>` : '';

        item.innerHTML = `
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 ${colors.bg} rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="${colors.text} font-semibold text-sm">${escapeHtml(user.name.charAt(0).toUpperCase())}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h4 class="font-medium text-gray-900 text-sm truncate">${escapeHtml(user.name)}</h4>
                        <span class="px-1.5 py-0.5 text-[10px] rounded-full ${colors.bg} ${colors.text} font-medium">${user.role_label || user.role}</span>
                        ${bloodType}
                    </div>
                    <div class="flex items-center gap-2 mt-0.5">
                        ${contextInfo}
                        ${hospitalName}
                    </div>
                </div>
                <svg class="w-5 h-5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </div>
        `;

        container.appendChild(item);
    });
}

/**
 * Start a new conversation with a user
 */
function startNewConversation(user) {
    chatState.currentView = 'conversations';
    
    // Initialize chat with this user (includes role info)
    initChat(user.id, user.name, null, null, null, user.role);
}

/**
 * Render conversations list with enhanced UI
 */
function renderConversations(conversations) {
    const container = document.getElementById('conversationsList');
    if (!container) return;

    container.innerHTML = '';

    // Add "New Conversation" button at top
    const newChatBtn = document.createElement('div');
    newChatBtn.className = 'p-3 border-b bg-gradient-to-r from-red-50 to-red-100 cursor-pointer hover:from-red-100 hover:to-red-200 transition-all';
    newChatBtn.innerHTML = `
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-red-600 rounded-full flex items-center justify-center shadow-sm">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
            </div>
            <div>
                <h4 class="font-medium text-red-700 text-sm">Start New Chat</h4>
                <p class="text-xs text-red-500">Find users to message</p>
            </div>
        </div>
    `;
    newChatBtn.onclick = () => {
        // Switch to search mode
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.value = '';
            searchInput.placeholder = 'Search users by name...';
            searchInput.focus();
        }
        searchUsers('');
    };
    container.appendChild(newChatBtn);

    if (!conversations || conversations.length === 0) {
        const emptyDiv = document.createElement('div');
        emptyDiv.className = 'p-8 text-center';
        emptyDiv.innerHTML = `
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
            </div>
            <h3 class="text-gray-700 font-medium mb-1">No conversations yet</h3>
            <p class="text-gray-400 text-sm">Start a new chat to connect with others</p>
        `;
        container.appendChild(emptyDiv);
        return;
    }

    // Section header
    const headerDiv = document.createElement('div');
    headerDiv.className = 'px-3 py-2 bg-gray-50 border-b';
    headerDiv.innerHTML = `<span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Recent Messages</span>`;
    container.appendChild(headerDiv);

    conversations.forEach(conv => {
        const item = document.createElement('div');
        const isActive = chatState.otherUserId === conv.other_user.id;
        const hasUnread = conv.unread_count > 0;
        
        item.className = `cursor-pointer p-3 border-b transition-all ${
            isActive 
                ? 'bg-red-50 border-l-4 border-l-red-600' 
                : hasUnread 
                    ? 'bg-blue-50 hover:bg-gray-50' 
                    : 'hover:bg-gray-50'
        }`;
        item.onclick = () => openConversation(conv.other_user.id, conv.other_user.name, conv.context?.request_id, conv.other_user.role);

        const colors = ROLE_COLORS[conv.other_user.role] || ROLE_COLORS.admin;

        // Format last message time
        let timeDisplay = '';
        if (conv.last_message_at) {
            const date = new Date(conv.last_message_at);
            const now = new Date();
            const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));
            
            if (diffDays === 0) {
                timeDisplay = date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
            } else if (diffDays === 1) {
                timeDisplay = 'Yesterday';
            } else if (diffDays < 7) {
                timeDisplay = date.toLocaleDateString('en-US', { weekday: 'short' });
            } else {
                timeDisplay = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            }
        }

        const unreadBadge = hasUnread
            ? `<span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold text-white bg-red-600 rounded-full">${conv.unread_count > 99 ? '99+' : conv.unread_count}</span>`
            : '';

        const lastMessage = conv.last_message 
            ? (conv.last_message.length > 35 ? conv.last_message.substring(0, 35) + '...' : conv.last_message)
            : 'No messages yet';

        item.innerHTML = `
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 ${colors.bg} rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="${colors.text} font-semibold text-sm">${escapeHtml((conv.other_user.name || '?').charAt(0).toUpperCase())}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-1.5 min-w-0">
                            <h4 class="font-medium text-gray-900 text-sm truncate ${hasUnread ? 'font-semibold' : ''}">${escapeHtml(conv.other_user.name || 'Unknown')}</h4>
                            <span class="px-1 py-0.5 text-[9px] rounded ${colors.bg} ${colors.text} font-medium flex-shrink-0">${(conv.other_user.role || '').charAt(0).toUpperCase() + (conv.other_user.role || '').slice(1)}</span>
                        </div>
                        <span class="text-[10px] text-gray-400 flex-shrink-0">${timeDisplay}</span>
                    </div>
                    <div class="flex items-center justify-between gap-2 mt-0.5">
                        <p class="text-xs ${hasUnread ? 'text-gray-700 font-medium' : 'text-gray-500'} truncate">${escapeHtml(lastMessage)}</p>
                        ${unreadBadge}
                    </div>
                </div>
            </div>
        `;

        container.appendChild(item);
    });
}

/**
 * Open a conversation
 */
window.openConversation = function (userId, userName, requestId = null, userRole = null) {
    initChat(userId, userName, requestId, null, null, userRole);
};

/**
 * Utility: Escape HTML
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Update unread badge
 */
export async function updateUnreadBadge() {
    try {
        const response = await fetch(`${CHAT_API}/unread-count.php?filter=total`, {
            credentials: 'include'
        });

        const data = await response.json();

        if (data.success) {
            const badge = document.getElementById('chatUnreadBadge');
            if (badge) {
                const count = data.data.unread_count || 0;
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : count;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            }
        }
    } catch (error) {
        console.error('Update Badge Error:', error);
    }
}

/**
 * Initialize chat module on page load
 */
export async function initChatModule() {
    // Fetch current user first
    await fetchCurrentUser();
    
    // Show empty state initially
    showEmptyChatState();
    
    // Disable input until conversation selected
    updateInputState(false);
    
    // Load conversations
    loadConversations();

    // Setup search input handler
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.trim();
            
            // Clear previous timeout
            if (chatState.searchTimeout) {
                clearTimeout(chatState.searchTimeout);
            }

            // Debounce search
            chatState.searchTimeout = setTimeout(() => {
                if (chatState.currentView === 'search' || query.length >= 2) {
                    searchUsers(query);
                } else if (query.length === 0 && chatState.currentView === 'conversations') {
                    loadConversations();
                }
            }, 300);
        });

        // Handle Enter key to switch to search mode
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const query = searchInput.value.trim();
                searchUsers(query);
            }
        });
    }

    // Update unread badge on load and periodically
    updateUnreadBadge();
    setInterval(updateUnreadBadge, 30000);

    // Cleanup on page unload
    window.addEventListener('beforeunload', stopPolling);
}

/**
 * Open chat from external page (e.g., donor list, request details)
 * @param {Object} options - { userId, userName, userRole, requestId, donationId, voluntaryId }
 */
export function openChatWith(options) {
    const { userId, userName, userRole, requestId, donationId, voluntaryId } = options;
    initChat(userId, userName, requestId, donationId, voluntaryId, userRole);
}
>>>>>>> 4a2d98e84ac74c58d418328fd399f3b8c0f065fb
