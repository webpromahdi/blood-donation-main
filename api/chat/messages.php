<?php
/**
 * Get Chat Messages
 * GET /api/chat/messages.php
 * 
 * Query params:
 * - user_id (required): The other user's ID
 * - since (optional): Timestamp for incremental fetch
 * - limit (optional): Max messages (default 50)
 * 
 * Also marks received messages as read
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
$currentUserId = (int)$_SESSION['user_id'];  // Ensure it's an integer for comparison

// Validate required params
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'user_id is required']);
    exit;
}

$otherUserId = (int)$_GET['user_id'];
$since = isset($_GET['since']) ? $_GET['since'] : null;
$limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 100) : 50;

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Get the other user's info
    $stmt = $conn->prepare("SELECT id, name, role FROM users WHERE id = ?");
    $stmt->execute([$otherUserId]);
    $otherUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$otherUser) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Build query with optional since filter
    // Parameters: sender1, receiver1, sender2, receiver2
    // First pair: messages I sent to them (sender=me, receiver=them)
    // Second pair: messages they sent to me (sender=them, receiver=me)
    $params = [$currentUserId, $otherUserId, $otherUserId, $currentUserId];
    $sinceClause = "";
    
    if ($since) {
        $sinceClause = " AND cm.created_at > ?";
        $params[] = $since;
    }
    
    $params[] = $limit;
    
    // Get messages between the two users
    $stmt = $conn->prepare("
        SELECT 
            cm.id,
            cm.sender_id,
            u.name AS sender_name,
            cm.message,
            cm.request_id,
            cm.donation_id,
            cm.is_read,
            cm.created_at
        FROM chat_messages cm
        JOIN users u ON u.id = cm.sender_id
        WHERE (
            (cm.sender_id = ? AND cm.receiver_id = ?)
            OR (cm.sender_id = ? AND cm.receiver_id = ?)
        ) {$sinceClause}
        ORDER BY cm.created_at ASC
        LIMIT ?
    ");
    
    $stmt->execute($params);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mark messages FROM other user as read
    $stmt = $conn->prepare("
        UPDATE chat_messages 
        SET is_read = 1, read_at = NOW() 
        WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
    ");
    $stmt->execute([$otherUserId, $currentUserId]);
    
    // Update last active timestamp for this conversation (for notification deduplication)
    $_SESSION['chat_active_' . $otherUserId] = time();
    
    // Format messages
    $formattedMessages = [];
    $lastTimestamp = null;
    
    foreach ($messages as $msg) {
        $createdAt = new DateTime($msg['created_at']);
        $timeFormatted = $createdAt->format('g:i A');
        
        $formattedMessages[] = [
            'id' => (int)$msg['id'],
            'sender_id' => (int)$msg['sender_id'],
            'sender_name' => $msg['sender_name'],
            'is_mine' => (int)$msg['sender_id'] === $currentUserId,
            'message' => $msg['message'],
            'created_at' => $msg['created_at'],
            'time' => $timeFormatted,
            'is_read' => (bool)$msg['is_read'],
            'request_id' => $msg['request_id'] ? (int)$msg['request_id'] : null,
            'donation_id' => $msg['donation_id'] ? (int)$msg['donation_id'] : null
        ];
        
        $lastTimestamp = $msg['created_at'];
    }
    
    echo json_encode([
        'success' => true,
        'messages' => $formattedMessages,
        'last_timestamp' => $lastTimestamp,
        'current_user_id' => $currentUserId,  // DEBUG: Help trace is_mine issues
        'other_user' => [
            'id' => (int)$otherUser['id'],
            'name' => $otherUser['name'],
            'role' => $otherUser['role']
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Get Messages Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch messages']);
}
