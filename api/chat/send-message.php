<?php
/**
 * Send Chat Message
 * POST /api/chat/send-message.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/chat_utils.php';

$user = requireAuth();
requireApprovedStatus($user['id'], $user['role']);

$input = json_decode(file_get_contents('php://input'), true);
$receiverId = isset($input['receiver_id']) ? (int)$input['receiver_id'] : 0;
$message = isset($input['message']) ? trim($input['message']) : '';
$requestId = isset($input['request_id']) ? (int)$input['request_id'] : null;
$donationId = isset($input['donation_id']) ? (int)$input['donation_id'] : null;
$voluntaryId = isset($input['voluntary_donation_id']) ? (int)$input['voluntary_donation_id'] : null;

if ($receiverId <= 0) {
    jsonResponse(400, ['success' => false, 'error' => 'receiver_id is required']);
}

if ($message === '' || strlen($message) > 5000) {
    jsonResponse(400, ['success' => false, 'error' => 'Message must be 1-5000 characters']);
}

enforceRateLimit($user['id']);

$conn = getDbConnection();

try {
    $conn->beginTransaction();

    $receiver = ensureUser($conn, $receiverId);

    // Donation context syncs request id
    if ($donationId && !$requestId) {
        $donationRow = fetchDonation($conn, $donationId);
        if ($donationRow) {
            $requestId = (int)$donationRow['request_id'];
        }
    }

    // Validate donation matches request
    if ($donationId && $requestId) {
        $donationRow = fetchDonation($conn, $donationId);
        if (!$donationRow || (int)$donationRow['request_id'] !== $requestId) {
            jsonResponse(400, ['success' => false, 'error' => 'Donation does not belong to the request']);
        }
    }

    [$allowed, $code, $context] = canUserChat($conn, $user, $receiver, $requestId, $donationId, $voluntaryId);
    if (!$allowed) {
        jsonResponse(403, ['success' => false, 'error' => 'You cannot chat with this user', 'code' => $code]);
    }

    $conversationId = generateConversationId($user['id'], $receiverId);

    $stmt = $conn->prepare('INSERT INTO chat_messages (conversation_id, sender_id, receiver_id, message, message_type, request_id, donation_id, voluntary_donation_id, created_at, updated_at) VALUES (?, ?, ?, ?, "text", ?, ?, ?, NOW(), NOW())');
    $stmt->execute([$conversationId, $user['id'], $receiverId, $message, $requestId, $donationId, $voluntaryId]);
    $messageId = (int)$conn->lastInsertId();

    upsertConversationMetadata($conn, $conversationId, $user['id'], $receiverId, $messageId);

    // Fallback notification (DB trigger should handle primary)
    createChatNotification($conn, $receiverId, $user['name'] ?? 'Someone', $messageId);

    $conn->commit();

    jsonResponse(201, [
        'success' => true,
        'message' => [
            'id' => $messageId,
            'conversation_id' => $conversationId,
            'sender_id' => (int)$user['id'],
            'receiver_id' => $receiverId,
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s'),
            'is_read' => false,
            'request_id' => $requestId,
            'donation_id' => $donationId,
            'voluntary_donation_id' => $voluntaryId
        ]
    ]);

} catch (Exception $e) {
    $conn->rollBack();
    error_log('Send Message Error: ' . $e->getMessage());
    jsonResponse(500, ['success' => false, 'error' => 'Failed to send message']);
}
