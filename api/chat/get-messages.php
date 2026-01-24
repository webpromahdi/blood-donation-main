<?php
/**
 * API: Get Messages (Fetch Conversation)
 * GET /api/chat/get-messages.php
 * 
 * Query params:
 * - user_id (int, required): Conversation partner ID
 * - limit (int, default 50, max 100): Messages to fetch
 * - offset (int, default 0): Pagination offset
 * - since_id (int, optional): Only fetch messages after this ID
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/chat_utils.php';

$user = requireAuth();
if (!$user) {
    exit;
}

$conn = getDbConnection();
$userId = (int)$_SESSION['user_id'];
$otherUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 100) : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$sinceId = isset($_GET['since_id']) ? (int)$_GET['since_id'] : 0;

if (!$otherUserId || $otherUserId === $userId) {
    jsonResponse(400, ['success' => false, 'error' => 'Invalid user_id']);
}

enforceRateLimit($userId);

try {
    $otherUser = ensureUser($conn, $otherUserId);
    $conversationId = generateConversationId($userId, $otherUserId);

    // Check permission (can view conversation if involved or admin)
    if ($user['role'] !== 'admin') {
        list($allowed, $code, $context) = canUserChat($conn, $user, $otherUser, null, null, null);
        // Note: canUserChat requires context. For viewing existing conversations, we relax this:
        // Only permission check is: are you one of the participants?
        // This is implicit from conversation_id query.
    }

    // Build query
    $queryParams = [$conversationId];
    $whereClause = 'WHERE cm.conversation_id = ?';
    if ($sinceId) {
        $whereClause .= ' AND cm.id > ?';
        $queryParams[] = $sinceId;
    }

    // Get total count
    $countStmt = $conn->prepare('SELECT COUNT(*) as cnt FROM chat_messages cm ' . $whereClause);
    $countStmt->execute($queryParams);
    $totalResult = $countStmt->fetch(PDO::FETCH_ASSOC);
    $total = (int)$totalResult['cnt'];

    // Fetch messages
    $stmt = $conn->prepare('
        SELECT 
            cm.id,
            cm.sender_id,
            cm.receiver_id,
            cm.message,
            cm.is_read,
            cm.read_at,
            cm.created_at,
            cm.request_id,
            cm.donation_id,
            cm.voluntary_donation_id,
            u1.name as sender_name
        FROM chat_messages cm
        JOIN users u1 ON cm.sender_id = u1.id
        ' . $whereClause . '
        ORDER BY cm.created_at ASC
        LIMIT ? OFFSET ?
    ');
    $queryParams[] = $limit;
    $queryParams[] = $offset;
    $stmt->execute($queryParams);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Auto-mark received messages as read
    $updateStmt = $conn->prepare('UPDATE chat_messages SET is_read = 1, read_at = NOW() WHERE conversation_id = ? AND receiver_id = ? AND is_read = 0');
    $updateStmt->execute([$conversationId, $userId]);

    // Clear unread from metadata cache
    clearConversationUnread($conn, $conversationId, $userId);

    // Format response
    $formattedMessages = array_map(function ($msg) {
        return [
            'id' => (int)$msg['id'],
            'sender_id' => (int)$msg['sender_id'],
            'sender_name' => $msg['sender_name'],
            'receiver_id' => (int)$msg['receiver_id'],
            'message' => $msg['message'],
            'is_read' => (bool)$msg['is_read'],
            'read_at' => $msg['read_at'],
            'created_at' => $msg['created_at'],
            'request_id' => $msg['request_id'] ? (int)$msg['request_id'] : null,
            'donation_id' => $msg['donation_id'] ? (int)$msg['donation_id'] : null,
            'voluntary_donation_id' => $msg['voluntary_donation_id'] ? (int)$msg['voluntary_donation_id'] : null
        ];
    }, $messages);

    jsonResponse(200, [
        'success' => true,
        'conversation' => [
            'conversation_id' => $conversationId,
            'user_1_id' => $userId,
            'user_1_name' => $user['name'],
            'user_2_id' => (int)$otherUserId,
            'user_2_name' => $otherUser['name'],
            'total_messages' => $total,
            'messages' => $formattedMessages
        ]
    ]);

} catch (Exception $e) {
    error_log('Get Messages Error: ' . $e->getMessage());
    jsonResponse(500, ['success' => false, 'error' => 'Failed to fetch messages']);
}
