<?php
/**
 * Mark Single Notification as Read
 * POST /api/notifications/mark-read.php
 * Body: { notification_id: int }
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

$user = requireAuth();

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['notification_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Notification ID is required']);
    exit;
}

$notificationId = (int)$input['notification_id'];
$userId = $_SESSION['user_id'];

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Verify notification belongs to user (security check)
    $stmt = $conn->prepare("SELECT id, is_read FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$notificationId, $userId]);
    $notification = $stmt->fetch();
    
    if (!$notification) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Notification not found']);
        exit;
    }
    
    if ($notification['is_read']) {
        echo json_encode([
            'success' => true,
            'message' => 'Notification already marked as read',
            'notification_id' => $notificationId
        ]);
        exit;
    }
    
    // Mark as read
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ?");
    $stmt->execute([$notificationId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Notification marked as read',
        'notification_id' => $notificationId
    ]);
    
} catch (PDOException $e) {
    error_log("Mark Notification Read Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
}
