<?php
/**
 * Mark All Notifications as Read
 * POST /api/notifications/mark-all-read.php
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

$userId = $_SESSION['user_id'];

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Mark all unread notifications as read for this user
    $stmt = $conn->prepare("
        UPDATE notifications 
        SET is_read = 1, read_at = NOW() 
        WHERE user_id = ? AND is_read = 0
    ");
    $stmt->execute([$userId]);
    
    $affectedRows = $stmt->rowCount();
    
    echo json_encode([
        'success' => true,
        'message' => $affectedRows > 0 
            ? "Marked {$affectedRows} notification(s) as read" 
            : 'No unread notifications',
        'count' => $affectedRows
    ]);
    
} catch (PDOException $e) {
    error_log("Mark All Notifications Read Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to mark notifications as read']);
}
