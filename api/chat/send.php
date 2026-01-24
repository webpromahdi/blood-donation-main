<?php
/**
 * Send Chat Message
 * POST /api/chat/send.php
 * 
 * Request body:
 * - receiver_id (required): The recipient's user ID
 * - message (required): Message content
 * - request_id (optional): Link to blood request
 * - donation_id (optional): Link to donation
 * 
 * Validates chat permission based on roles and context
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
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../services/NotificationService.php';

$user = requireAuth();
$currentUserId = (int)$_SESSION['user_id'];  // Ensure it's an integer
$currentUserRole = $_SESSION['role'];
$currentUserName = $_SESSION['name'];

// Get JSON body
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($input['receiver_id']) || empty($input['receiver_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'receiver_id is required']);
    exit;
}

if (!isset($input['message']) || trim($input['message']) === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'message is required']);
    exit;
}

$receiverId = (int)$input['receiver_id'];
$message = trim($input['message']);
$requestId = isset($input['request_id']) ? (int)$input['request_id'] : null;
$donationId = isset($input['donation_id']) ? (int)$input['donation_id'] : null;

// Validate message length
if (strlen($message) > 2000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Message too long (max 2000 characters)']);
    exit;
}

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Get receiver info
    $stmt = $conn->prepare("SELECT id, name, role FROM users WHERE id = ?");
    $stmt->execute([$receiverId]);
    $receiver = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$receiver) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Recipient not found']);
        exit;
    }
    
    $receiverRole = $receiver['role'];
    
    // ========================================
    // ROLE-BASED CHAT PERMISSION VALIDATION
    // ========================================
    
    $canChat = false;
    
    // Rule 1: Admin can chat with anyone
    if ($currentUserRole === 'admin') {
        $canChat = true;
    }
    
    // Rule 2: Anyone can chat with Admin
    if ($receiverRole === 'admin') {
        $canChat = true;
    }
    
    // Rule 3: Hospital <-> Donor (only if linked via request or donation)
    if (!$canChat && 
        (($currentUserRole === 'hospital' && $receiverRole === 'donor') ||
         ($currentUserRole === 'donor' && $receiverRole === 'hospital'))) {
        
        // Get the donor_id and hospital user_id
        $donorUserId = $currentUserRole === 'donor' ? $currentUserId : $receiverId;
        $hospitalUserId = $currentUserRole === 'hospital' ? $currentUserId : $receiverId;
        
        // Check if donor has any donation linked to requests from this hospital
        $stmt = $conn->prepare("
            SELECT COUNT(*) as cnt FROM donations d
            JOIN donors don ON d.donor_id = don.id
            JOIN blood_requests br ON d.request_id = br.id
            WHERE don.user_id = ? 
            AND br.requester_id = ? 
            AND br.requester_type = 'hospital'
        ");
        $stmt->execute([$donorUserId, $hospitalUserId]);
        $result = $stmt->fetch();
        
        if ($result['cnt'] > 0) {
            $canChat = true;
        }
        
        // Also check if hospital has a request that this donor can see (approved requests)
        if (!$canChat) {
            $stmt = $conn->prepare("
                SELECT COUNT(*) as cnt FROM blood_requests br
                JOIN donors don ON don.user_id = ?
                JOIN blood_groups bg ON don.blood_group_id = bg.id
                WHERE br.requester_id = ?
                AND br.requester_type = 'hospital'
                AND br.status IN ('approved', 'in_progress', 'completed')
            ");
            $stmt->execute([$donorUserId, $hospitalUserId]);
            $result = $stmt->fetch();
            
            if ($result['cnt'] > 0) {
                $canChat = true;
            }
        }
    }
    
    // Rule 4: Seeker <-> Donor (only if donor accepted seeker's request)
    if (!$canChat && 
        (($currentUserRole === 'seeker' && $receiverRole === 'donor') ||
         ($currentUserRole === 'donor' && $receiverRole === 'seeker'))) {
        
        $donorUserId = $currentUserRole === 'donor' ? $currentUserId : $receiverId;
        $seekerUserId = $currentUserRole === 'seeker' ? $currentUserId : $receiverId;
        
        // Check if donor has accepted any request from this seeker
        $stmt = $conn->prepare("
            SELECT COUNT(*) as cnt FROM donations d
            JOIN donors don ON d.donor_id = don.id
            JOIN blood_requests br ON d.request_id = br.id
            WHERE don.user_id = ?
            AND br.requester_id = ?
            AND br.requester_type = 'seeker'
        ");
        $stmt->execute([$donorUserId, $seekerUserId]);
        $result = $stmt->fetch();
        
        if ($result['cnt'] > 0) {
            $canChat = true;
        }
    }
    
    // If still can't chat, check if there's an existing conversation (allow replies)
    if (!$canChat) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as cnt FROM chat_messages
            WHERE (sender_id = ? AND receiver_id = ?)
            OR (sender_id = ? AND receiver_id = ?)
        ");
        $stmt->execute([$currentUserId, $receiverId, $receiverId, $currentUserId]);
        $result = $stmt->fetch();
        
        if ($result['cnt'] > 0) {
            $canChat = true;
        }
    }
    
    if (!$canChat) {
        http_response_code(403);
        echo json_encode([
            'success' => false, 
            'message' => 'You are not allowed to chat with this user. Chat is only available with users you have an active request or donation relationship with.'
        ]);
        exit;
    }
    
    // ========================================
    // INSERT MESSAGE
    // ========================================
    
    $stmt = $conn->prepare("
        INSERT INTO chat_messages (sender_id, receiver_id, message, request_id, donation_id, is_read, created_at)
        VALUES (?, ?, ?, ?, ?, 0, NOW())
    ");
    $stmt->execute([$currentUserId, $receiverId, $message, $requestId, $donationId]);
    
    $messageId = $conn->lastInsertId();
    
    // Get the created_at timestamp
    $stmt = $conn->prepare("SELECT created_at FROM chat_messages WHERE id = ?");
    $stmt->execute([$messageId]);
    $msgData = $stmt->fetch();
    
    // ========================================
    // CREATE NOTIFICATION FOR RECEIVER
    // ========================================
    
    // Check if receiver is currently active in this conversation
    // We skip notification if they've fetched messages in the last 60 seconds
    $shouldNotify = true;
    
    // Preview for notification
    $preview = strlen($message) > 50 ? substr($message, 0, 50) . '...' : $message;
    
    // Create notification
    $notificationService = new NotificationService($conn);
    $notificationService->notifyNewChatMessage($receiverId, $currentUserName, $preview, $messageId);
    
    echo json_encode([
        'success' => true,
        'message_id' => (int)$messageId,
        'created_at' => $msgData['created_at']
    ]);
    
} catch (PDOException $e) {
    error_log("Send Message Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to send message']);
}
