<?php
/**
 * API: Check Chat Permission (Pre-flight)
 * GET /api/chat/check-permission.php
 * 
 * Query params:
 * - target_user_id (int, required): User to chat with
 * - request_id (int, optional): Blood request context
 * - donation_id (int, optional): Donation context
 * - voluntary_donation_id (int, optional): Voluntary donation context
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
$targetUserId = isset($_GET['target_user_id']) ? (int)$_GET['target_user_id'] : 0;
$requestId = isset($_GET['request_id']) ? (int)$_GET['request_id'] : null;
$donationId = isset($_GET['donation_id']) ? (int)$_GET['donation_id'] : null;
$voluntaryId = isset($_GET['voluntary_donation_id']) ? (int)$_GET['voluntary_donation_id'] : null;

if (!$targetUserId) {
    jsonResponse(400, ['success' => false, 'error' => 'target_user_id is required']);
}

try {
    $targetUser = ensureUser($conn, $targetUserId);

    // Check permission
    list($allowed, $code, $context) = canUserChat($conn, $user, $targetUser, $requestId, $donationId, $voluntaryId);

    if ($allowed) {
        jsonResponse(200, [
            'success' => true,
            'can_chat' => true,
            'reason' => 'Permission granted',
            'code' => $code
        ]);
    } else {
        // Map codes to user-friendly reasons
        $reasons = [
            'SELF_CHAT_NOT_ALLOWED' => 'Cannot message yourself',
            'ADMIN_TO_ADMIN_BLOCKED' => 'Admins cannot message each other',
            'SAME_ROLE_PROHIBITED' => 'Users of the same role cannot chat',
            'MISSING_CONTEXT' => 'A blood request or donation is required for this chat',
            'REQUEST_NOT_FOUND' => 'Blood request not found or invalid',
            'REQUEST_INACTIVE' => 'Blood request is not active for messaging',
            'REQUEST_PENDING_BLOCK' => 'Cannot chat during pending status (admin only)',
            'NOT_INVOLVED_IN_REQUEST' => 'You are not involved in this blood request',
            'DONATION_NOT_FOUND' => 'Donation not found or invalid',
            'DONATION_INACTIVE' => 'Donation is completed or cancelled',
            'NOT_INVOLVED_IN_DONATION' => 'You are not involved in this donation',
            'VOLUNTARY_NOT_FOUND' => 'Voluntary donation not found',
            'VOLUNTARY_INACTIVE' => 'Voluntary donation is not active',
            'NOT_INVOLVED_IN_VOLUNTARY' => 'You are not involved in this voluntary donation',
            'UNKNOWN_CONTEXT' => 'Unable to verify chat context'
        ];

        jsonResponse(200, [
            'success' => true,
            'can_chat' => false,
            'reason' => $reasons[$code] ?? 'Permission denied',
            'code' => $code
        ]);
    }

} catch (Exception $e) {
    error_log('Check Permission Error: ' . $e->getMessage());
    jsonResponse(500, ['success' => false, 'error' => 'Failed to check permission']);
}
