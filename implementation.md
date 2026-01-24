# 🔬 CHAT FEATURE IMPLEMENTATION PLAN

## Blood Donation & Emergency Request Management System

---

## 📋 SECTION 1: FRONTEND CHAT AUDIT REPORT

### 1.1 Chat Pages Inventory

| Portal       | File Path                      | Current Status                 |
| ------------ | ------------------------------ | ------------------------------ |
| **Admin**    | `src/pages/admin/chat.html`    | ❌ Placeholder ("Coming Soon") |
| **Donor**    | `src/pages/donor/chat.html`    | ❌ Placeholder ("Coming Soon") |
| **Hospital** | `src/pages/hospital/chat.html` | ❌ Placeholder ("Coming Soon") |
| **Seeker**   | `src/pages/seeker/chat.html`   | ❌ Placeholder ("Coming Soon") |

### 1.2 Existing Chat Entry Points (Already Wired)

| Location                                           | Entry Point                              | Status                             |
| -------------------------------------------------- | ---------------------------------------- | ---------------------------------- |
| `src/pages/hospital/donors.html` (L576-579)        | "Chat" button per donor row              | ✅ Wired to `startChat()` function |
| `src/pages/seeker/request-details.html` (L756-761) | "Chat with Donor" button in modal footer | ✅ Links to chat page              |
| `src/pages/seeker/request-details.html` (L312-316) | "Chat with Donor" button in status card  | ✅ Links to chat page              |
| All sidebar navigations                            | Chat menu item                           | ✅ Links exist in all 4 portals    |

### 1.3 Shared Chat Components

| Component                        | Status                                          |
| -------------------------------- | ----------------------------------------------- |
| Dedicated chat JavaScript module | ❌ Does NOT exist                               |
| Chat CSS styles                  | ❌ No dedicated styles (but Tailwind available) |
| Chat UI template/component       | ❌ No reusable component                        |

### 1.4 UI Consistency Assessment

| Aspect                | Finding                                                           |
| --------------------- | ----------------------------------------------------------------- |
| Layout consistency    | ✅ All 4 chat pages use identical sidebar + header layout         |
| Styling consistency   | ✅ All use same Tailwind classes, colors, spacing                 |
| Notification dropdown | ✅ Present in Donor, Hospital, Seeker (Admin has simpler version) |
| User info display     | ✅ Consistent header pattern across all portals                   |

### 1.5 Verdict: Frontend Readiness

| Criteria       | Status     | Notes                                          |
| -------------- | ---------- | ---------------------------------------------- |
| Page structure | ✅ Ready   | Consistent layout across all 4 portals         |
| Chat UI design | ❌ Missing | Only placeholder exists - needs UI structure   |
| Entry points   | ✅ Ready   | Buttons already wired in Hospital/Seeker pages |
| Navigation     | ✅ Ready   | Sidebar links functional                       |

**CONCLUSION: Frontend structure is ready but requires replacing placeholder content with actual chat UI. NO redesign needed - only content replacement.**

---

## 📋 SECTION 2: DATABASE READINESS

### 2.1 Existing `chat_messages` Table

```sql
-- Already exists in database.sql (lines 402-431)
CREATE TABLE chat_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,           -- FK to users.id
    receiver_id INT NOT NULL,         -- FK to users.id
    message TEXT NOT NULL,            -- Message content
    request_id INT NULL,              -- Optional: link to blood_request
    donation_id INT NULL,             -- Optional: link to donation
    is_read BOOLEAN DEFAULT FALSE,    -- Read status
    read_at TIMESTAMP NULL,           -- When read
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Indexes for fast lookups
    INDEX idx_chat_sender (sender_id),
    INDEX idx_chat_receiver (receiver_id),
    INDEX idx_chat_conversation (sender_id, receiver_id)
);
```

### 2.2 Schema Assessment

| Aspect          | Status        | Notes                                                   |
| --------------- | ------------- | ------------------------------------------------------- |
| Basic messaging | ✅ Sufficient | sender_id, receiver_id, message, timestamp              |
| Context linking | ✅ Sufficient | request_id, donation_id for context                     |
| Read tracking   | ✅ Sufficient | is_read, read_at fields exist                           |
| Indexes         | ✅ Sufficient | Conversation index for fast queries                     |
| Missing         | ⚠️ Optional   | No `conversation_id` but can be derived from user pairs |

**VERDICT: Database schema is PRODUCTION-READY for chat. No modifications needed.**

---

## 📋 SECTION 3: CHAT FLOW DESIGN

### 3.1 Communication Matrix: Who Can Chat With Whom

```
┌─────────────┬─────────┬───────────┬──────────┬────────┐
│             │  Admin  │  Hospital │   Donor  │ Seeker │
├─────────────┼─────────┼───────────┼──────────┼────────┤
│    Admin    │   ❌    │    ✅     │    ✅    │   ✅   │
│   Hospital  │   ✅    │    ❌     │    ✅*   │   ❌   │
│    Donor    │   ✅    │    ✅*    │    ❌    │   ✅*  │
│   Seeker    │   ✅    │    ❌     │    ✅*   │   ❌   │
└─────────────┴─────────┴───────────┴──────────┴────────┘

Legend:
✅  = Can initiate chat freely
✅* = Can chat ONLY if linked via request/donation
❌  = Cannot chat (no business need)
```

### 3.2 Chat Initiation Rules

| Scenario                          | Who Initiates | With Whom       | Context Required                                                     |
| --------------------------------- | ------------- | --------------- | -------------------------------------------------------------------- |
| Hospital needs donor coordination | Hospital      | Donor           | ✅ Donor must be in hospital's donor list OR have accepted a request |
| Seeker needs to contact donor     | Seeker        | Donor           | ✅ Donor must have accepted seeker's request                         |
| Donor has questions               | Donor         | Hospital/Seeker | ✅ Must have active donation linkage                                 |
| Admin support                     | Admin         | Anyone          | ❌ No context required (admin can contact anyone)                    |
| User support                      | Anyone        | Admin           | ❌ Can always contact admin for support                              |

### 3.3 Context-Aware Chat Flow

```
┌──────────────────────────────────────────────────────────────────┐
│                    CONTEXT-BASED CHAT FLOW                       │
└──────────────────────────────────────────────────────────────────┘

1. HOSPITAL → DONOR (via donors page)
   ┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
   │ Hospital views  │────▶│ Clicks "Chat"   │────▶│ Opens chat with │
   │ donor list      │     │ button on donor │     │ donor context   │
   └─────────────────┘     └─────────────────┘     └─────────────────┘

2. SEEKER → DONOR (via request-details)
   ┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
   │ Seeker views    │────▶│ Clicks "Chat    │────▶│ Opens chat with │
   │ request details │     │ with Donor"     │     │ request context │
   └─────────────────┘     └─────────────────┘     └─────────────────┘

3. DONOR → HOSPITAL/SEEKER (via history page)
   ┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
   │ Donor views     │────▶│ Clicks "Chat"   │────▶│ Opens chat with │
   │ donation history│     │ on past request │     │ requester       │
   └─────────────────┘     └─────────────────┘     └─────────────────┘

4. ANY USER → ADMIN (support)
   ┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
   │ User on any     │────▶│ Opens chat page │────▶│ "Contact Admin" │
   │ portal          │     │ via sidebar     │     │ button always   │
   └─────────────────┘     └─────────────────┘     └─────────────────┘
```

---

## 📋 SECTION 4: MESSAGE FLOW ARCHITECTURE

### 4.1 Send Message Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                      SEND MESSAGE FLOW                          │
└─────────────────────────────────────────────────────────────────┘

    ┌─────────────┐
    │   User UI   │
    │ (chat page) │
    └──────┬──────┘
           │ 1. User types message + clicks Send
           ▼
    ┌─────────────────────────────────────────────────────────────┐
    │                    Frontend JavaScript                       │
    │  - Validate message (not empty, max length)                 │
    │  - Disable send button (prevent double-submit)              │
    │  - Show "sending" indicator                                 │
    └──────┬──────────────────────────────────────────────────────┘
           │ 2. POST /api/chat/send.php
           │    Body: { receiver_id, message, request_id?, donation_id? }
           ▼
    ┌─────────────────────────────────────────────────────────────┐
    │                    Backend PHP API                           │
    │  - Validate session (auth middleware)                       │
    │  - Validate receiver exists                                 │
    │  - Validate conversation allowed (context check)            │
    │  - Sanitize message content                                 │
    │  - INSERT into chat_messages                                │
    │  - Create notification for receiver                         │
    └──────┬──────────────────────────────────────────────────────┘
           │ 3. Response: { success, message_id, created_at }
           ▼
    ┌─────────────────────────────────────────────────────────────┐
    │                    Frontend JavaScript                       │
    │  - Append message to chat window (optimistic UI)            │
    │  - Clear input field                                        │
    │  - Re-enable send button                                    │
    │  - Scroll to bottom                                         │
    └─────────────────────────────────────────────────────────────┘
```

### 4.2 Fetch Messages Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                      FETCH MESSAGES FLOW                        │
└─────────────────────────────────────────────────────────────────┘

    ┌─────────────┐
    │ Page Load / │
    │  Polling    │
    └──────┬──────┘
           │ 1. GET /api/chat/messages.php?user_id=X&since=TIMESTAMP
           ▼
    ┌─────────────────────────────────────────────────────────────┐
    │                    Backend PHP API                           │
    │  - Validate session                                         │
    │  - Query messages WHERE (sender=me AND receiver=X)          │
    │                      OR (sender=X AND receiver=me)          │
    │  - Filter by "since" timestamp for incremental fetch        │
    │  - Mark received messages as read                           │
    │  - ORDER BY created_at ASC                                  │
    └──────┬──────────────────────────────────────────────────────┘
           │ 2. Response: { messages: [...], last_timestamp }
           ▼
    ┌─────────────────────────────────────────────────────────────┐
    │                    Frontend JavaScript                       │
    │  - Render messages (mine=right, theirs=left)                │
    │  - Update "since" for next poll                             │
    │  - Show typing indicator if applicable                      │
    │  - Scroll to bottom if at bottom                            │
    └─────────────────────────────────────────────────────────────┘
```

### 4.3 Read/Unread Handling

```
┌─────────────────────────────────────────────────────────────────┐
│                    READ/UNREAD HANDLING                         │
└─────────────────────────────────────────────────────────────────┘

MARK AS READ (automatic when viewing):
1. When fetching messages, backend marks all messages FROM the other user as read
2. Updates: is_read = TRUE, read_at = NOW()
3. No separate API call needed (read is implicit on fetch)

UNREAD COUNT (for badges):
GET /api/chat/unread-count.php
- Returns: { total_unread, conversations: [ { user_id, count } ] }
- Used for:
  • Chat sidebar badge (total unread)
  • Conversation list (per-conversation count)
```

---

## 📋 SECTION 5: NOTIFICATION INTEGRATION

### 5.1 Integration with Existing NotificationService

The existing `NotificationService.php` handles 40 notification types. Chat notifications will follow the same pattern:

```
NEW CHAT NOTIFICATION TYPES (to be added):
┌────────────────────────────────────────────────────────────────┐
│ C1: New Message Received                                       │
│     Trigger: When user receives a chat message                 │
│     Type: info                                                 │
│     Related: chat_message                                      │
│     Title: "New Message from {SenderName}"                     │
│     Message: "{Preview of message...}"                         │
│     Action: Navigate to chat page                              │
└────────────────────────────────────────────────────────────────┘
```

### 5.2 Notification Flow

```
Message Sent → NotificationService::notifyNewChatMessage()
            │
            ▼
     ┌──────────────────────────────────────────┐
     │ INSERT INTO notifications                │
     │   user_id = receiver_id                  │
     │   title = "New Message from {Sender}"    │
     │   message = "Preview text..."            │
     │   type = 'info'                          │
     │   related_type = 'chat_message'          │
     │   related_id = message_id                │
     └──────────────────────────────────────────┘
            │
            ▼
     Existing notification polling picks it up
     (30-second interval already implemented)
```

### 5.3 Deduplication Strategy

To avoid notification spam during active conversations:

```
RULE: Only create notification if:
  1. Receiver is NOT currently viewing the conversation
     (Determined by: no message fetch in last 60 seconds for that conversation)

IMPLEMENTATION:
  - Track last_active_at per conversation in session or DB
  - If active: Skip notification (they'll see it immediately)
  - If inactive: Create notification
```

---

## 📋 SECTION 6: POLLING STRATEGY

### 6.1 PHP + AJAX Polling vs WebSocket

| Criteria                  | PHP + AJAX Polling          | WebSocket                         |
| ------------------------- | --------------------------- | --------------------------------- |
| Implementation complexity | ✅ Low                      | ❌ High (needs Node.js/Ratchet)   |
| Server requirements       | ✅ Standard XAMPP           | ❌ Persistent process required    |
| Message latency           | ⚠️ 3-5 seconds              | ✅ Real-time (<100ms)             |
| Server load               | ⚠️ Moderate (many requests) | ✅ Lower (persistent connections) |
| Academic demo suitable    | ✅ Yes                      | ❌ Overkill                       |
| Production scalability    | ⚠️ Needs optimization       | ✅ Better at scale                |
| Mobile-friendly           | ✅ Works on all browsers    | ⚠️ Some mobile issues             |

### 6.2 VERDICT: PHP + AJAX Polling is SUFFICIENT

**Justification:**

1. **Academic project scope**: Real-time chat at <3 second latency is acceptable
2. **Infrastructure constraint**: XAMPP doesn't easily support WebSocket servers
3. **Code consistency**: Follows existing notification polling pattern (30s interval)
4. **No external dependencies**: Aligns with project requirement (no Firebase/Pusher)

### 6.3 Polling Configuration

```javascript
// Recommended polling intervals:
const CHAT_POLLING_CONFIG = {
  // When user is ACTIVE on chat page (viewing conversation)
  activeInterval: 3000, // 3 seconds - fast refresh

  // When user is IDLE on chat page (no interaction for 30s)
  idleInterval: 10000, // 10 seconds - reduced load

  // When chat page is in background tab
  backgroundInterval: 30000, // 30 seconds - minimal load

  // For unread badge in sidebar (follows existing pattern)
  badgeInterval: 30000, // 30 seconds (match notifications)
};
```

### 6.4 Performance Safeguards

```
┌─────────────────────────────────────────────────────────────────┐
│                    POLLING OPTIMIZATIONS                        │
└─────────────────────────────────────────────────────────────────┘

1. INCREMENTAL FETCH
   - Only fetch messages since last timestamp
   - Reduces data transfer by 90%+

2. VISIBILITY API
   - Pause polling when tab is hidden
   - Resume when tab becomes visible

3. CONNECTION DETECTION
   - Pause on network loss
   - Auto-resume on reconnection

4. DEBOUNCE
   - Prevent multiple parallel requests
   - Use "isLoading" flag (matches notification pattern)

5. CONVERSATION CACHE
   - Cache conversation list in memory
   - Only update on new message received
```

---

## 📋 SECTION 7: BACKEND API DESIGN

### 7.1 API Endpoint List

| Endpoint                      | Method | Purpose                                  |
| ----------------------------- | ------ | ---------------------------------------- |
| `/api/chat/conversations.php` | GET    | List all conversations for current user  |
| `/api/chat/messages.php`      | GET    | Get messages for a specific conversation |
| `/api/chat/send.php`          | POST   | Send a new message                       |
| `/api/chat/unread-count.php`  | GET    | Get unread message count                 |
| `/api/chat/mark-read.php`     | POST   | Mark conversation as read                |

### 7.2 API Specifications

#### `GET /api/chat/conversations.php`

```
Purpose: List all conversations for current user with last message preview

Query Params: None (uses session for user_id)

Response:
{
    "success": true,
    "conversations": [
        {
            "user_id": 15,
            "user_name": "City Hospital",
            "user_role": "hospital",
            "last_message": "Thank you for accepting the request",
            "last_message_time": "2 hours ago",
            "unread_count": 3,
            "request_id": 45,          // Optional context
            "request_code": "REQ-2026-0045"
        }
    ]
}
```

#### `GET /api/chat/messages.php`

```
Purpose: Get messages for a conversation

Query Params:
  - user_id (required): The other user's ID
  - since (optional): Timestamp for incremental fetch
  - limit (optional): Max messages (default 50)

Response:
{
    "success": true,
    "messages": [
        {
            "id": 123,
            "sender_id": 14,
            "sender_name": "John Doe",
            "is_mine": false,
            "message": "Hello, I accepted your request",
            "created_at": "2026-01-24T10:30:00",
            "time": "10:30 AM",
            "is_read": true,
            "request_id": 45
        }
    ],
    "last_timestamp": "2026-01-24T10:30:00",
    "other_user": {
        "id": 14,
        "name": "John Doe",
        "role": "donor"
    }
}
```

#### `POST /api/chat/send.php`

```
Purpose: Send a message

Request Body:
{
    "receiver_id": 15,
    "message": "Thank you for your help!",
    "request_id": 45,      // Optional: link to blood request
    "donation_id": 23      // Optional: link to donation
}

Response:
{
    "success": true,
    "message_id": 124,
    "created_at": "2026-01-24T10:35:00"
}
```

#### `GET /api/chat/unread-count.php`

```
Purpose: Get total unread count for chat badge

Response:
{
    "success": true,
    "total_unread": 5,
    "conversations": [
        { "user_id": 15, "unread": 3 },
        { "user_id": 18, "unread": 2 }
    ]
}
```

---

## 📋 SECTION 8: FRONTEND ↔ BACKEND MAPPING

### 8.1 Page-to-API Mapping

| Frontend Page                   | APIs Called                                                         | UI Components                     |
| ------------------------------- | ------------------------------------------------------------------- | --------------------------------- |
| **admin/chat.html**             | `conversations.php`, `messages.php`, `send.php`, `unread-count.php` | Conversation list + Message panel |
| **donor/chat.html**             | `conversations.php`, `messages.php`, `send.php`, `unread-count.php` | Conversation list + Message panel |
| **hospital/chat.html**          | `conversations.php`, `messages.php`, `send.php`, `unread-count.php` | Conversation list + Message panel |
| **seeker/chat.html**            | `conversations.php`, `messages.php`, `send.php`, `unread-count.php` | Conversation list + Message panel |
| **hospital/donors.html**        | (existing) + Chat button navigation                                 | Uses `startChat()` function       |
| **seeker/request-details.html** | (existing) + Chat button navigation                                 | Uses `chatWithDonorBtn` link      |

### 8.2 UI Component Structure (Text-Based)

```
┌─────────────────────────────────────────────────────────────────┐
│                        CHAT PAGE LAYOUT                         │
├─────────────────────────────────────────────────────────────────┤
│  ┌───────────────────┐  ┌─────────────────────────────────────┐ │
│  │                   │  │ Header: Recipient Name + Status     │ │
│  │   CONVERSATION    │  ├─────────────────────────────────────┤ │
│  │       LIST        │  │                                     │ │
│  │                   │  │         MESSAGE AREA                │ │
│  │ ┌───────────────┐ │  │       (scrollable)                  │ │
│  │ │ User Name     │ │  │                                     │ │
│  │ │ Last message  │ │  │  ┌─────────────────────┐            │ │
│  │ │ 2m ago    (3) │ │  │  │ Their message       │            │ │
│  │ └───────────────┘ │  │  └─────────────────────┘            │ │
│  │ ┌───────────────┐ │  │            ┌─────────────────────┐  │ │
│  │ │ User Name     │ │  │            │ My message          │  │ │
│  │ │ Preview...    │ │  │            └─────────────────────┘  │ │
│  │ └───────────────┘ │  │                                     │ │
│  │                   │  ├─────────────────────────────────────┤ │
│  │ [Contact Admin]   │  │ ┌─────────────────────────────┐ [↗] │ │
│  │                   │  │ │ Type a message...           │     │ │
│  └───────────────────┘  │ └─────────────────────────────┘     │ │
│        (300px)          └─────────────────────────────────────┘ │
│                                   (remaining width)             │
└─────────────────────────────────────────────────────────────────┘
```

---

## 📋 SECTION 9: FILE CREATION PLAN

### 9.1 Backend Files to Create

| File                         | Purpose                |
| ---------------------------- | ---------------------- |
| `api/chat/conversations.php` | List conversations     |
| `api/chat/messages.php`      | Get/mark-read messages |
| `api/chat/send.php`          | Send message           |
| `api/chat/unread-count.php`  | Unread count for badge |

### 9.2 Frontend Files to Create/Modify

| File                             | Action     | Purpose                     |
| -------------------------------- | ---------- | --------------------------- |
| `src/scripts/components/chat.js` | **CREATE** | Shared chat functionality   |
| `src/pages/admin/chat.html`      | **MODIFY** | Replace placeholder with UI |
| `src/pages/donor/chat.html`      | **MODIFY** | Replace placeholder with UI |
| `src/pages/hospital/chat.html`   | **MODIFY** | Replace placeholder with UI |
| `src/pages/seeker/chat.html`     | **MODIFY** | Replace placeholder with UI |

### 9.3 NotificationService Additions

| Method                                                     | Purpose                  |
| ---------------------------------------------------------- | ------------------------ |
| `notifyNewChatMessage($receiverId, $senderName, $preview)` | Create chat notification |

---

## 📋 SECTION 10: FINAL VERDICT

### ✅ READY TO IMPLEMENT

| Criteria            | Status   | Evidence                                            |
| ------------------- | -------- | --------------------------------------------------- |
| Database schema     | ✅ Ready | `chat_messages` table exists with all needed fields |
| Frontend structure  | ✅ Ready | 4 chat pages exist with consistent layout           |
| Entry points        | ✅ Ready | Chat buttons wired in Hospital/Seeker pages         |
| Notification system | ✅ Ready | `NotificationService` + polling pattern established |
| Auth middleware     | ✅ Ready | Session-based auth with role checking               |
| Design patterns     | ✅ Ready | Follow existing notification-dropdown.js patterns   |

### ⚠️ Minor Adjustments Before Implementation

| Adjustment                              | Reason                                    | Effort |
| --------------------------------------- | ----------------------------------------- | ------ |
| Add chat unread badge to sidebar        | Consistency with notification badge       | 5 min  |
| Add "Contact Admin" option              | All users should be able to reach support | 10 min |
| Update seeker request-details chat link | Pass donor user_id as query param         | 5 min  |

### 📊 Implementation Estimate

| Component                   | Estimated Time |
| --------------------------- | -------------- |
| Backend APIs (4 endpoints)  | 3-4 hours      |
| Frontend chat.js component  | 2-3 hours      |
| UI implementation (4 pages) | 2-3 hours      |
| Integration + testing       | 2 hours        |
| **TOTAL**                   | **9-12 hours** |

---

## 📋 SECTION 11: IMPLEMENTATION ORDER

```
PHASE 1: Backend Foundation
├── 1.1 Create api/chat/conversations.php
├── 1.2 Create api/chat/messages.php
├── 1.3 Create api/chat/send.php
├── 1.4 Create api/chat/unread-count.php
└── 1.5 Add notifyNewChatMessage() to NotificationService

PHASE 2: Frontend Foundation
├── 2.1 Create src/scripts/components/chat.js
├── 2.2 Define polling logic (reuse notification pattern)
└── 2.3 Define message rendering functions

PHASE 3: UI Implementation
├── 3.1 Update admin/chat.html (replace placeholder)
├── 3.2 Update donor/chat.html (replace placeholder)
├── 3.3 Update hospital/chat.html (replace placeholder)
└── 3.4 Update seeker/chat.html (replace placeholder)

PHASE 4: Integration
├── 4.1 Wire hospital/donors.html chat button with user_id
├── 4.2 Wire seeker/request-details.html chat button
├── 4.3 Add chat unread badge to sidebars
└── 4.4 End-to-end testing all 4 portals
```

---

**END OF IMPLEMENTATION PLAN**
