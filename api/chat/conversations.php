<?php
/**
<<<<<<< HEAD
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
=======
 * API: Get Conversations List
 * GET /api/chat/conversations.php
 * 
 * Query params:
 * - sort (string, default 'recent'): 'recent' or 'unread_first'
 * - limit (int, default 20): Conversations to fetch
 * - search (string, optional): Search by user name
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
$sort = isset($_GET['sort']) && $_GET['sort'] === 'unread_first' ? 'unread_first' : 'recent';
$limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 100) : 20;
$search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : null;

try {
    // Use metadata table if available for faster queries
    $useMetadata = true;
    try {
        $testStmt = $conn->prepare('SELECT 1 FROM chat_conversations LIMIT 1');
        $testStmt->execute();
    } catch (PDOException $e) {
        $useMetadata = false;
    }

    if ($useMetadata) {
        // Query using metadata table
        $query = '
            SELECT 
                cc.conversation_id,
                CASE WHEN cc.user_1_id = ? THEN cc.user_2_id ELSE cc.user_1_id END as other_user_id,
                u.name as other_user_name,
                u.role as other_user_role,
                u.phone as other_user_phone,
                cc.last_message_at,
                (SELECT message FROM chat_messages WHERE id = cc.last_message_id LIMIT 1) as last_message,
                (SELECT sender_id FROM chat_messages WHERE id = cc.last_message_id LIMIT 1) as last_message_sender_id,
                CASE WHEN cc.user_1_id = ? THEN cc.user_1_unread_count ELSE cc.user_2_unread_count END as unread_count
            FROM chat_conversations cc
            JOIN users u ON (
                CASE WHEN cc.user_1_id = ? THEN cc.user_2_id ELSE cc.user_1_id END
            ) = u.id
            WHERE cc.user_1_id = ? OR cc.user_2_id = ?
        ';

        if ($search) {
            $query .= ' AND u.name LIKE ?';
        }

        if ($sort === 'unread_first') {
            $query .= ' ORDER BY unread_count DESC, cc.last_message_at DESC';
        } else {
            $query .= ' ORDER BY cc.last_message_at DESC';
        }

        $query .= ' LIMIT ?';

        $params = [$userId, $userId, $userId, $userId, $userId];
        if ($search) {
            $params[] = $search;
        }
        $params[] = $limit;

        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } else {
        // Fallback: Query chat_messages directly (simple GROUP BY approach)
        // First, get distinct conversation partners
        $query = '
            SELECT DISTINCT
                CASE WHEN cm.sender_id = ? THEN cm.receiver_id ELSE cm.sender_id END as other_user_id
            FROM chat_messages cm
            WHERE cm.sender_id = ? OR cm.receiver_id = ?
        ';
        $stmt = $conn->prepare($query);
        $stmt->execute([$userId, $userId, $userId]);
        $partnerIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $conversations = [];
        foreach ($partnerIds as $partnerId) {
            $partnerId = (int)$partnerId;
            $convId = generateConversationId($userId, $partnerId);

            // Get partner info
            $userStmt = $conn->prepare('SELECT id, name, role, phone FROM users WHERE id = ? LIMIT 1');
            $userStmt->execute([$partnerId]);
            $partner = $userStmt->fetch(PDO::FETCH_ASSOC);
            if (!$partner) continue;

            // Filter by search if needed
            if ($search && stripos($partner['name'], trim($search, '%')) === false) {
                continue;
            }

            // Get last message
            $msgStmt = $conn->prepare('
                SELECT message, sender_id, created_at 
                FROM chat_messages 
                WHERE conversation_id = ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ');
            $msgStmt->execute([$convId]);
            $lastMsg = $msgStmt->fetch(PDO::FETCH_ASSOC);

            // Get unread count
            $unreadStmt = $conn->prepare('
                SELECT COUNT(*) 
                FROM chat_messages 
                WHERE conversation_id = ? AND receiver_id = ? AND is_read = 0
            ');
            $unreadStmt->execute([$convId, $userId]);
            $unreadCount = (int)$unreadStmt->fetchColumn();

            $conversations[] = [
                'conversation_id' => $convId,
                'other_user_id' => $partnerId,
                'other_user_name' => $partner['name'],
                'other_user_role' => $partner['role'],
                'other_user_phone' => $partner['phone'],
                'last_message' => $lastMsg['message'] ?? null,
                'last_message_sender_id' => $lastMsg['sender_id'] ?? null,
                'last_message_at' => $lastMsg['created_at'] ?? null,
                'unread_count' => $unreadCount
            ];
        }

        // Sort conversations
        if ($sort === 'unread_first') {
            usort($conversations, function($a, $b) {
                if ($b['unread_count'] !== $a['unread_count']) {
                    return $b['unread_count'] - $a['unread_count'];
                }
                return strtotime($b['last_message_at'] ?? '1970-01-01') - strtotime($a['last_message_at'] ?? '1970-01-01');
            });
        } else {
            usort($conversations, function($a, $b) {
                return strtotime($b['last_message_at'] ?? '1970-01-01') - strtotime($a['last_message_at'] ?? '1970-01-01');
            });
        }

        // Apply limit
        $conversations = array_slice($conversations, 0, $limit);
    }

    // Format response and fetch context
    $formatted = [];
    $totalUnread = 0;

    foreach ($conversations as $conv) {
        $otherUserId = (int)$conv['other_user_id'];
        $unreadCount = (int)$conv['unread_count'];
        $lastMessageSenderId = isset($conv['last_message_sender_id']) ? (int)$conv['last_message_sender_id'] : null;

        $item = [
            'conversation_id' => $conv['conversation_id'],
            'other_user' => [
                'id' => $otherUserId,
                'name' => $conv['other_user_name'],
                'role' => $conv['other_user_role'],
                'phone' => $conv['other_user_phone']
            ],
            'last_message' => $conv['last_message'],
            'last_message_by' => $lastMessageSenderId === $userId ? 'you' : $conv['other_user_role'],
            'last_message_at' => $conv['last_message_at'],
            'unread_count' => $unreadCount,
            'is_unread' => $unreadCount > 0,
            'context' => null
        ];

        // Try to enrich with context (request/donation)
        $ctxStmt = $conn->prepare('
            SELECT 
                request_id, donation_id, voluntary_donation_id
            FROM chat_messages 
            WHERE conversation_id = ?
            ORDER BY created_at DESC
            LIMIT 1
        ');
        $ctxStmt->execute([$conv['conversation_id']]);
        $ctxRow = $ctxStmt->fetch(PDO::FETCH_ASSOC);

        if ($ctxRow && $ctxRow['request_id']) {
            $req = fetchRequest($conn, (int)$ctxRow['request_id']);
            if ($req) {
                $item['context'] = [
                    'request_id' => (int)$req['id'],
                    'request_code' => $req['request_code'],
                    'blood_type' => $req['blood_group_id'] ? 'TBD' : null,
                    'status' => $req['status']
                ];
            }
        }

        $formatted[] = $item;
        $totalUnread += $unreadCount;
    }

    jsonResponse(200, [
        'success' => true,
        'data' => $formatted,
        'summary' => [
            'total_conversations' => count($formatted),
            'total_unread' => $totalUnread
        ]
    ]);

} catch (Exception $e) {
    error_log('Conversations List Error: ' . $e->getMessage());
    jsonResponse(500, ['success' => false, 'error' => 'Failed to fetch conversations']);
>>>>>>> 4a2d98e84ac74c58d418328fd399f3b8c0f065fb
}
