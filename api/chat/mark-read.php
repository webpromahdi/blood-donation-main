<?php
/**
 * API: Mark as Read
 * POST /api/chat/mark-read.php
 * 
 * Request body:
 * {
 *   "action": "single" | "batch" | "conversation",
 *   "message_ids": [1, 2, 3]  (for single/batch),
 *   "user_id": 5              (for conversation marking)
 * }
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
$data = json_decode(file_get_contents('php://input'), true);

$action = isset($data['action']) ? $data['action'] : 'single';

try {
    if (in_array($action, ['single', 'batch'], true)) {
        $messageIds = isset($data['message_ids']) ? (array)$data['message_ids'] : [];
        if (empty($messageIds)) {
            jsonResponse(400, ['success' => false, 'error' => 'message_ids required']);
        }

        $messageIds = array_map('intval', $messageIds);
        $placeholders = implode(',', array_fill(0, count($messageIds), '?'));

        // Verify all messages belong to receiver
        $stmt = $conn->prepare('SELECT COUNT(*) as cnt FROM chat_messages WHERE id IN (' . $placeholders . ') AND receiver_id = ?');
        $params = $messageIds;
        $params[] = $userId;
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ((int)$result['cnt'] !== count($messageIds)) {
            jsonResponse(403, ['success' => false, 'error' => 'One or more messages do not belong to you']);
        }

        // Mark as read
        $updateParams = $messageIds;
        $updateParams[] = $userId;
        $updateStmt = $conn->prepare('UPDATE chat_messages SET is_read = 1, read_at = NOW() WHERE id IN (' . $placeholders . ') AND receiver_id = ?');
        $updateStmt->execute($updateParams);
        $count = $updateStmt->rowCount();

        jsonResponse(200, [
            'success' => true,
            'data' => [
                'marked_count' => $count,
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ]);

    } elseif ($action === 'conversation') {
        $otherUserId = isset($data['user_id']) ? (int)$data['user_id'] : 0;
        if (!$otherUserId) {
            jsonResponse(400, ['success' => false, 'error' => 'user_id required for conversation action']);
        }

        $conversationId = generateConversationId($userId, $otherUserId);

        // Mark all messages from otherUserId to userId as read
        $stmt = $conn->prepare('UPDATE chat_messages SET is_read = 1, read_at = NOW() WHERE conversation_id = ? AND sender_id = ? AND receiver_id = ? AND is_read = 0');
        $stmt->execute([$conversationId, $otherUserId, $userId]);
        $count = $stmt->rowCount();

        // Clear unread from metadata
        clearConversationUnread($conn, $conversationId, $userId);

        jsonResponse(200, [
            'success' => true,
            'data' => [
                'marked_count' => $count,
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ]);

    } else {
        jsonResponse(400, ['success' => false, 'error' => 'Invalid action']);
    }

} catch (Exception $e) {
    error_log('Mark Read Error: ' . $e->getMessage());
    jsonResponse(500, ['success' => false, 'error' => 'Failed to mark as read']);
}
