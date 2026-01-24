<?php
/**
 * Chat Utility Functions
 * Implements permission matrix, context validation, conversation helpers, and rate limiting
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../services/NotificationService.php';

/**
 * Return JSON response with status code
 */
function jsonResponse(int $statusCode, array $payload)
{
    if (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

/**
 * Get PDO connection or fail with 500
 */
function getDbConnection()
{
    $database = new Database();
    $conn = $database->getConnection();
    if (!$conn) {
        jsonResponse(500, ['success' => false, 'error' => 'Database connection failed']);
    }
    return $conn;
}

/**
 * Generate conversation id (order-independent)
 */
function generateConversationId(int $userA, int $userB): string
{
    $min = min($userA, $userB);
    $max = max($userA, $userB);
    return 'CONV_' . $min . '_' . $max;
}

/**
 * Fetch user by id
 */
function getUserById(PDO $conn, int $userId): ?array
{
    $stmt = $conn->prepare('SELECT id, name, email, role, status FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}

function ensureUser(PDO $conn, int $userId): array
{
    $user = getUserById($conn, $userId);
    if (!$user) {
        jsonResponse(404, ['success' => false, 'error' => 'User not found']);
    }
    return $user;
}

function getDonorIdByUserId(PDO $conn, int $userId): ?int
{
    $stmt = $conn->prepare('SELECT id FROM donors WHERE user_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['id'] : null;
}

function getHospitalIdByUserId(PDO $conn, int $userId): ?int
{
    $stmt = $conn->prepare('SELECT id FROM hospitals WHERE user_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['id'] : null;
}

function getSeekerIdByUserId(PDO $conn, int $userId): ?int
{
    $stmt = $conn->prepare('SELECT id FROM seekers WHERE user_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['id'] : null;
}

function fetchRequest(PDO $conn, int $requestId): ?array
{
    $stmt = $conn->prepare('SELECT * FROM blood_requests WHERE id = ? LIMIT 1');
    $stmt->execute([$requestId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function fetchDonation(PDO $conn, int $donationId): ?array
{
    $stmt = $conn->prepare('SELECT d.*, dn.user_id AS donor_user_id FROM donations d JOIN donors dn ON d.donor_id = dn.id WHERE d.id = ? LIMIT 1');
    $stmt->execute([$donationId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function fetchVoluntary(PDO $conn, int $voluntaryId): ?array
{
    $stmt = $conn->prepare('SELECT vd.*, dn.user_id AS donor_user_id, h.user_id AS hospital_user_id FROM voluntary_donations vd JOIN donors dn ON vd.donor_id = dn.id LEFT JOIN hospitals h ON vd.hospital_id = h.id WHERE vd.id = ? LIMIT 1');
    $stmt->execute([$voluntaryId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function getDonorUserIdsForRequest(PDO $conn, int $requestId): array
{
    $stmt = $conn->prepare('SELECT u.id FROM donations d JOIN donors dn ON d.donor_id = dn.id JOIN users u ON dn.user_id = u.id WHERE d.request_id = ? AND d.status NOT IN ("cancelled")');
    $stmt->execute([$requestId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function getHospitalUserIdForRequest(PDO $conn, array $request): ?int
{
    if (!empty($request['hospital_id'])) {
        $stmt = $conn->prepare('SELECT user_id FROM hospitals WHERE id = ? LIMIT 1');
        $stmt->execute([$request['hospital_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['user_id'] : null;
    }
    if ($request['requester_type'] === 'hospital') {
        return (int)$request['requester_id'];
    }
    return null;
}

function getRequestParticipants(PDO $conn, array $request): array
{
    $participants = [];
    $participants[] = (int)$request['requester_id'];
    $hospitalUser = getHospitalUserIdForRequest($conn, $request);
    if ($hospitalUser) {
        $participants[] = $hospitalUser;
    }
    $donorUsers = getDonorUserIdsForRequest($conn, (int)$request['id']);
    $participants = array_merge($participants, $donorUsers);
    return array_values(array_unique($participants));
}

function getDonationParticipants(PDO $conn, array $donation): array
{
    $participants = [];
    $participants[] = (int)$donation['donor_user_id'];
    $request = fetchRequest($conn, (int)$donation['request_id']);
    if ($request) {
        $participants[] = (int)$request['requester_id'];
        $hospitalUser = getHospitalUserIdForRequest($conn, $request);
        if ($hospitalUser) {
            $participants[] = $hospitalUser;
        }
    }
    return array_values(array_unique($participants));
}

function getVoluntaryParticipants(PDO $conn, array $voluntary): array
{
    $participants = [(int)$voluntary['donor_user_id']];
    if (!empty($voluntary['hospital_user_id'])) {
        $participants[] = (int)$voluntary['hospital_user_id'];
    }
    return array_values(array_unique($participants));
}

function isRequestActiveForSend(string $status, bool $isAdmin): bool
{
    if ($isAdmin) {
        return true;
    }
    if (in_array($status, ['rejected', 'cancelled', 'completed'], true)) {
        return false;
    }
    if ($status === 'pending') {
        return false; // Only admin allowed during pending
    }
    return true; // approved or in_progress
}

function isDonationActiveForSend(string $status, bool $isAdmin): bool
{
    if ($isAdmin) {
        return true;
    }
    return !in_array($status, ['cancelled', 'completed'], true);
}

function isVoluntaryActiveForSend(string $status, bool $isAdmin): bool
{
    if ($isAdmin) {
        return true;
    }
    return !in_array($status, ['rejected', 'cancelled', 'completed'], true);
}

/**
 * Check chat permission according to design
 * Returns array [bool, reason_code, context]
 * 
 * Permission Rules:
 * 1. Self-chat not allowed
 * 2. Admin-to-admin not allowed  
 * 3. Same role (donor-donor, hospital-hospital, seeker-seeker) not allowed
 * 4. Admin can chat with anyone (except other admins)
 * 5. Different roles CAN chat with each other (no context required)
 * 6. If context is provided (request/donation/voluntary), validate participation
 */
function canUserChat(PDO $conn, array $sender, array $receiver, ?int $requestId, ?int $donationId, ?int $voluntaryId): array
{
    $senderId = (int)$sender['id'];
    $receiverId = (int)$receiver['id'];
    $senderRole = $sender['role'];
    $receiverRole = $receiver['role'];

    if ($senderId === $receiverId) {
        return [false, 'SELF_CHAT_NOT_ALLOWED', null];
    }

    // Admin-to-admin prohibited
    if ($senderRole === 'admin' && $receiverRole === 'admin') {
        return [false, 'ADMIN_TO_ADMIN_BLOCKED', null];
    }

    $isSenderAdmin = $senderRole === 'admin';
    $isReceiverAdmin = $receiverRole === 'admin';

    // Same-role prohibition (except admin override)
    if (!$isSenderAdmin && !$isReceiverAdmin && $senderRole === $receiverRole) {
        return [false, 'SAME_ROLE_PROHIBITED', null];
    }

    // Admin override (can chat anyone, any time, except other admin)
    if ($isSenderAdmin) {
        return [true, 'ADMIN_OVERRIDE', null];
    }

    // If chatting with admin, always allowed
    if ($isReceiverAdmin) {
        return [true, 'CHAT_WITH_ADMIN', null];
    }

    // Different roles can chat without context (role-based permission)
    // Donor <-> Hospital, Donor <-> Seeker, Hospital <-> Seeker all allowed
    if ($senderRole !== $receiverRole) {
        // If context provided, validate it; otherwise allow general chat
        
        // Donation context (optional validation)
        if ($donationId) {
            $donation = fetchDonation($conn, $donationId);
            if (!$donation) {
                return [false, 'DONATION_NOT_FOUND', null];
            }
            $participants = getDonationParticipants($conn, $donation);
            $active = isDonationActiveForSend($donation['status'], $isSenderAdmin);
            if (!$active) {
                return [false, 'DONATION_INACTIVE', null];
            }
            if (!in_array($senderId, $participants, true) || !in_array($receiverId, $participants, true)) {
                return [false, 'NOT_INVOLVED_IN_DONATION', null];
            }
            $requestId = (int)$donation['request_id'];
            return [true, 'ALLOWED_DONATION', ['request_id' => $requestId, 'donation_id' => $donationId]];
        }

        // Request context (optional validation)
        if ($requestId) {
            $request = fetchRequest($conn, $requestId);
            if (!$request) {
                return [false, 'REQUEST_NOT_FOUND', null];
            }
            $participants = getRequestParticipants($conn, $request);
            $active = isRequestActiveForSend($request['status'], $isSenderAdmin);
            if (!$active) {
                return [false, 'REQUEST_INACTIVE', null];
            }
            if (!in_array($senderId, $participants, true) || !in_array($receiverId, $participants, true)) {
                return [false, 'NOT_INVOLVED_IN_REQUEST', null];
            }
            return [true, 'ALLOWED_REQUEST', ['request_id' => $requestId]];
        }

        // Voluntary donation context (optional validation)
        if ($voluntaryId) {
            $voluntary = fetchVoluntary($conn, $voluntaryId);
            if (!$voluntary) {
                return [false, 'VOLUNTARY_NOT_FOUND', null];
            }
            $participants = getVoluntaryParticipants($conn, $voluntary);
            $active = isVoluntaryActiveForSend($voluntary['status'], $isSenderAdmin);
            if (!$active) {
                return [false, 'VOLUNTARY_INACTIVE', null];
            }
            if (!in_array($senderId, $participants, true) || !in_array($receiverId, $participants, true)) {
                return [false, 'NOT_INVOLVED_IN_VOLUNTARY', null];
            }
            return [true, 'ALLOWED_VOLUNTARY', ['voluntary_donation_id' => $voluntaryId]];
        }

        // No context needed - different roles can chat freely
        return [true, 'ALLOWED_CROSS_ROLE', null];
    }

    // Fallback: deny
    return [false, 'PERMISSION_DENIED', null];
}

/**
 * Simple per-minute rate limit (50 messages/min)
 */
function enforceRateLimit(int $userId): void
{
    $key = 'chat_rate_' . $userId;
    $now = time();
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'reset_at' => $now + 60];
    }
    if ($now > $_SESSION[$key]['reset_at']) {
        $_SESSION[$key] = ['count' => 0, 'reset_at' => $now + 60];
    }
    if ($_SESSION[$key]['count'] >= 50) {
        jsonResponse(429, ['success' => false, 'error' => 'Rate limit exceeded. Try again later.']);
    }
    $_SESSION[$key]['count']++;
}

/**
 * Update chat_conversations cache (create or bump unread)
 */
function upsertConversationMetadata(PDO $conn, string $conversationId, int $senderId, int $receiverId, int $messageId): void
{
    $user1 = min($senderId, $receiverId);
    $user2 = max($senderId, $receiverId);

    $stmt = $conn->prepare('SELECT id, user_1_id, user_2_id, user_1_unread_count, user_2_unread_count FROM chat_conversations WHERE conversation_id = ? LIMIT 1');
    $stmt->execute([$conversationId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $now = date('Y-m-d H:i:s');
    if ($row) {
        $u1Unread = (int)$row['user_1_unread_count'];
        $u2Unread = (int)$row['user_2_unread_count'];
        if ($receiverId === (int)$row['user_1_id']) {
            $u1Unread++;
        } else {
            $u2Unread++;
        }
        $update = $conn->prepare('UPDATE chat_conversations SET last_message_id = ?, last_message_at = ?, user_1_unread_count = ?, user_2_unread_count = ? WHERE id = ?');
        $update->execute([$messageId, $now, $u1Unread, $u2Unread, $row['id']]);
    } else {
        $u1Unread = $receiverId === $user1 ? 1 : 0;
        $u2Unread = $receiverId === $user2 ? 1 : 0;
        $insert = $conn->prepare('INSERT INTO chat_conversations (conversation_id, user_1_id, user_2_id, last_message_id, last_message_at, user_1_unread_count, user_2_unread_count, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $insert->execute([$conversationId, $user1, $user2, $messageId, $now, $u1Unread, $u2Unread]);
    }
}

/**
 * Reset unread count for current receiver in conversation metadata
 */
function clearConversationUnread(PDO $conn, string $conversationId, int $receiverId): void
{
    $stmt = $conn->prepare('SELECT id, user_1_id, user_2_id FROM chat_conversations WHERE conversation_id = ? LIMIT 1');
    $stmt->execute([$conversationId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return;
    }
    $u1Unread = 'user_1_unread_count';
    $u2Unread = 'user_2_unread_count';
    if ($receiverId === (int)$row['user_1_id']) {
        $update = $conn->prepare('UPDATE chat_conversations SET user_1_unread_count = 0 WHERE id = ?');
        $update->execute([$row['id']]);
    } else {
        $update = $conn->prepare('UPDATE chat_conversations SET user_2_unread_count = 0 WHERE id = ?');
        $update->execute([$row['id']]);
    }
}

/**
 * Create notification for chat message (fallback if trigger missing)
 */
function createChatNotification(PDO $conn, int $receiverId, string $senderName, int $messageId): void
{
    $service = new NotificationService($conn);
    $service->createChatNotification($receiverId, $senderName, $messageId);
}
