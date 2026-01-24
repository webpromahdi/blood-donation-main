<?php
/**
<<<<<<< HEAD
 * Get Unread Chat Count
 * GET /api/chat/unread-count.php
 * 
 * Returns total unread message count and per-conversation breakdown
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

$user = requireAuth();
$currentUserId = (int)$_SESSION['user_id'];  // Ensure it's an integer

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Get total unread count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_unread
        FROM chat_messages
        WHERE receiver_id = ? AND is_read = 0
    ");
    $stmt->execute([$currentUserId]);
    $totalResult = $stmt->fetch();
    
    // Get per-conversation unread counts
    $stmt = $conn->prepare("
        SELECT 
            sender_id AS user_id,
            u.name AS user_name,
            COUNT(*) AS unread
        FROM chat_messages cm
        JOIN users u ON u.id = cm.sender_id
        WHERE cm.receiver_id = ? AND cm.is_read = 0
        GROUP BY sender_id
    ");
    $stmt->execute([$currentUserId]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format conversations
    $formattedConversations = array_map(function($conv) {
        return [
            'user_id' => (int)$conv['user_id'],
            'user_name' => $conv['user_name'],
            'unread' => (int)$conv['unread']
        ];
    }, $conversations);
    
    echo json_encode([
        'success' => true,
        'total_unread' => (int)$totalResult['total_unread'],
        'conversations' => $formattedConversations
    ]);
    
} catch (PDOException $e) {
    error_log("Get Unread Count Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch unread count']);
=======
 * API: Get Unread Count
 * GET /api/chat/unread-count.php
 * 
 * Query params:
 * - filter (string, default 'total'): 'total' or 'per_user'
 * - user_id (int, optional): Get unread from specific user
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
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'total';
$specificUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

try {
    if ($filter === 'per_user') {
        // Get unread breakdown by sender
        $stmt = $conn->prepare('
            SELECT 
                u.id as user_id,
                u.name as user_name,
                u.role as user_role,
                COUNT(cm.id) as unread_count,
                MAX(cm.created_at) as last_message_at,
                (SELECT message FROM chat_messages 
                 WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0
                 ORDER BY created_at DESC LIMIT 1) as last_message,
                CONCAT("CONV_", LEAST(u.id, ?), "_", GREATEST(u.id, ?)) as conversation_id
            FROM chat_messages cm
            JOIN users u ON cm.sender_id = u.id
            WHERE cm.receiver_id = ? AND cm.is_read = 0
            GROUP BY cm.sender_id
            ORDER BY last_message_at DESC
        ');
        $stmt->execute([$userId, $userId, $userId, $userId]);
        $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formatted = array_map(function ($conv) {
            return [
                'user_id' => (int)$conv['user_id'],
                'user_name' => $conv['user_name'],
                'user_role' => $conv['user_role'],
                'unread_count' => (int)$conv['unread_count'],
                'last_message' => $conv['last_message'],
                'last_message_at' => $conv['last_message_at'],
                'conversation_id' => $conv['conversation_id']
            ];
        }, $conversations);

        jsonResponse(200, [
            'success' => true,
            'data' => $formatted
        ]);

    } else {
        // Total unread count
        $stmt = $conn->prepare('
            SELECT COUNT(*) as total FROM chat_messages 
            WHERE receiver_id = ? AND is_read = 0
        ');
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = (int)$result['total'];

        // Count conversations with unread
        $convStmt = $conn->prepare('
            SELECT COUNT(DISTINCT sender_id) as count FROM chat_messages 
            WHERE receiver_id = ? AND is_read = 0
        ');
        $convStmt->execute([$userId]);
        $convResult = $convStmt->fetch(PDO::FETCH_ASSOC);
        $convCount = (int)$convResult['count'];

        // Last unread time
        $lastStmt = $conn->prepare('
            SELECT created_at FROM chat_messages 
            WHERE receiver_id = ? AND is_read = 0
            ORDER BY created_at DESC LIMIT 1
        ');
        $lastStmt->execute([$userId]);
        $lastResult = $lastStmt->fetch(PDO::FETCH_ASSOC);
        $lastTime = $lastResult ? $lastResult['created_at'] : null;

        jsonResponse(200, [
            'success' => true,
            'data' => [
                'total_unread' => $total,
                'conversations_with_unread' => $convCount,
                'last_unread_at' => $lastTime,
                'unread_count' => $total  // Alias for badge display
            ]
        ]);
    }

} catch (Exception $e) {
    error_log('Unread Count Error: ' . $e->getMessage());
    jsonResponse(500, ['success' => false, 'error' => 'Failed to fetch unread count']);
>>>>>>> 4a2d98e84ac74c58d418328fd399f3b8c0f065fb
}
