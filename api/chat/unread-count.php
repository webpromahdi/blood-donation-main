<?php
/**
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
}
