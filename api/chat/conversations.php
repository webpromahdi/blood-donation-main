<?php
/**
 * Get Chat Conversations List
 * GET /api/chat/conversations.php
 * 
 * Returns list of all conversations for the current user
 * with last message preview and unread count
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
$currentUserRole = $_SESSION['role'];

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Get all unique conversations for this user
    // A conversation is identified by the other user they've chatted with
    $stmt = $conn->prepare("
        SELECT 
            CASE 
                WHEN cm.sender_id = ? THEN cm.receiver_id
                ELSE cm.sender_id
            END AS other_user_id,
            u.name AS user_name,
            u.role AS user_role,
            cm.message AS last_message,
            cm.created_at AS last_message_time,
            cm.request_id,
            br.request_code,
            (
                SELECT COUNT(*) 
                FROM chat_messages cm2 
                WHERE cm2.sender_id = other_user_id 
                AND cm2.receiver_id = ? 
                AND cm2.is_read = 0
            ) AS unread_count
        FROM chat_messages cm
        JOIN users u ON u.id = CASE 
            WHEN cm.sender_id = ? THEN cm.receiver_id
            ELSE cm.sender_id
        END
        LEFT JOIN blood_requests br ON cm.request_id = br.id
        WHERE cm.sender_id = ? OR cm.receiver_id = ?
        AND cm.id IN (
            SELECT MAX(id) FROM chat_messages
            WHERE sender_id = ? OR receiver_id = ?
            GROUP BY 
                CASE 
                    WHEN sender_id = ? THEN receiver_id 
                    ELSE sender_id 
                END
        )
        GROUP BY other_user_id
        ORDER BY cm.created_at DESC
    ");
    
    $stmt->execute([
        $currentUserId, $currentUserId, $currentUserId, 
        $currentUserId, $currentUserId, $currentUserId, 
        $currentUserId, $currentUserId
    ]);
    
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format conversations
    $formattedConversations = [];
    foreach ($conversations as $conv) {
        // Format time - show actual time for today, date for older messages
        $createdAt = new DateTime($conv['last_message_time']);
        $now = new DateTime();
        $today = new DateTime('today');
        $yesterday = new DateTime('yesterday');
        
        if ($createdAt >= $today) {
            // Today - show time only (e.g., "12:54 AM")
            $timeDisplay = $createdAt->format('g:i A');
        } elseif ($createdAt >= $yesterday) {
            // Yesterday - show "Yesterday"
            $timeDisplay = 'Yesterday';
        } elseif ($createdAt->format('Y') === $now->format('Y')) {
            // This year - show date without year (e.g., "Jan 25")
            $timeDisplay = $createdAt->format('M j');
        } else {
            // Older - show full date (e.g., "Jan 25, 2025")
            $timeDisplay = $createdAt->format('M j, Y');
        }
        
        // Truncate message preview
        $preview = strlen($conv['last_message']) > 50 
            ? substr($conv['last_message'], 0, 50) . '...' 
            : $conv['last_message'];
        
        $formattedConversations[] = [
            'user_id' => (int)$conv['other_user_id'],
            'user_name' => $conv['user_name'],
            'user_role' => $conv['user_role'],
            'last_message' => $preview,
            'last_message_time' => $timeDisplay,
            'unread_count' => (int)$conv['unread_count'],
            'request_id' => $conv['request_id'] ? (int)$conv['request_id'] : null,
            'request_code' => $conv['request_code']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'conversations' => $formattedConversations
    ]);
    
} catch (PDOException $e) {
    error_log("Get Conversations Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch conversations']);
}
