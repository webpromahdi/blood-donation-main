<?php
/**
 * Admin Announcements Endpoint
 * GET /api/admin/announcements.php - List all announcements
 * POST /api/admin/announcements.php - Create new announcement
 * DELETE /api/admin/announcements.php?id=X - Delete announcement
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../services/NotificationService.php';

requireAuth(['admin']);

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGet($conn);
            break;
        case 'POST':
            handlePost($conn);
            break;
        case 'DELETE':
            handleDelete($conn);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (PDOException $e) {
    error_log("Announcements Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}

/**
 * GET - List all announcements
 * Automatically publishes scheduled announcements when their time has come
 */
function handleGet($conn) {
    // First, find scheduled announcements that need to be published and send notifications
    $findScheduledSql = "SELECT id, title, message, target_audience, priority 
                         FROM announcements 
                         WHERE status = 'scheduled' 
                         AND scheduled_at IS NOT NULL 
                         AND scheduled_at <= NOW()";
    $scheduledStmt = $conn->query($findScheduledSql);
    $scheduledAnnouncements = $scheduledStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Send notifications for each announcement being auto-published
    if (!empty($scheduledAnnouncements)) {
        $notificationService = new NotificationService($conn);
        foreach ($scheduledAnnouncements as $announcement) {
            $notificationService->notifyAnnouncementPublished(
                $announcement['id'],
                $announcement['title'],
                $announcement['message'],
                $announcement['target_audience'],
                $announcement['priority']
            );
        }
        
        // Now update the status to published
        $updateSql = "UPDATE announcements 
                      SET status = 'published' 
                      WHERE status = 'scheduled' 
                      AND scheduled_at IS NOT NULL 
                      AND scheduled_at <= NOW()";
        $conn->exec($updateSql);
    }
    
    // Fetch all announcements for admin view (including scheduled and archived)
    $sql = "SELECT a.*, u.name as admin_name 
            FROM announcements a 
            LEFT JOIN users u ON a.admin_id = u.id 
            ORDER BY 
                CASE a.status 
                    WHEN 'scheduled' THEN 1 
                    WHEN 'published' THEN 2 
                    WHEN 'draft' THEN 3 
                    WHEN 'archived' THEN 4 
                END,
                a.scheduled_at ASC,
                a.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $announcements = $stmt->fetchAll();
    
    // Format response with computed status for display
    $formatted = array_map(function($a) {
        $status = $a['status'];
        $scheduledAt = $a['scheduled_at'];
        
        // Compute display status
        $displayStatus = $status;
        $isLive = ($status === 'published');
        $isScheduled = ($status === 'scheduled' && $scheduledAt !== null);
        
        return [
            'id' => (int) $a['id'],
            'title' => $a['title'],
            'message' => $a['message'],
            'target_audience' => $a['target_audience'],
            'priority' => $a['priority'],
            'status' => $status,
            'display_status' => $displayStatus,
            'is_live' => $isLive,
            'is_scheduled' => $isScheduled,
            'scheduled_at' => $scheduledAt,
            'admin_name' => $a['admin_name'] ?? 'System',
            'created_at' => $a['created_at'],
            'updated_at' => $a['updated_at']
        ];
    }, $announcements);
    
    echo json_encode([
        'success' => true,
        'announcements' => $formatted,
        'total' => count($formatted)
    ]);
}

/**
 * POST - Create new announcement
 */
function handlePost($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (empty($input['title']) || empty($input['message'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Title and message are required']);
        return;
    }
    
    $title = trim($input['title']);
    $message = trim($input['message']);
    $targetAudience = $input['target_audience'] ?? 'all';
    $priority = $input['priority'] ?? 'normal';
    $scheduledAt = !empty($input['scheduled_at']) ? $input['scheduled_at'] : null;
    $status = $scheduledAt ? 'scheduled' : 'published';
    $adminId = $_SESSION['user_id'];
    
    // Validate enums
    $validAudiences = ['all', 'donors', 'hospitals', 'seekers'];
    $validPriorities = ['normal', 'high', 'urgent'];
    
    if (!in_array($targetAudience, $validAudiences)) {
        $targetAudience = 'all';
    }
    if (!in_array($priority, $validPriorities)) {
        $priority = 'normal';
    }
    
    $sql = "INSERT INTO announcements (title, message, target_audience, priority, scheduled_at, status, admin_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$title, $message, $targetAudience, $priority, $scheduledAt, $status, $adminId]);
    
    $newId = $conn->lastInsertId();
    
    // Send notifications to users if announcement is published immediately (not scheduled)
    $notifiedCount = 0;
    if ($status === 'published') {
        $notificationService = new NotificationService($conn);
        $notifiedCount = $notificationService->notifyAnnouncementPublished($newId, $title, $message, $targetAudience, $priority);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Announcement created successfully',
        'announcement' => [
            'id' => $newId,
            'title' => $title,
            'message' => $message,
            'target_audience' => $targetAudience,
            'priority' => $priority,
            'status' => $status,
            'scheduled_at' => $scheduledAt
        ],
        'notified_users' => $notifiedCount
    ]);
}

/**
 * DELETE - Delete announcement by ID
 */
function handleDelete($conn) {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Valid announcement ID is required']);
        return;
    }
    
    $id = (int) $_GET['id'];
    
    // Check if announcement exists
    $checkSql = "SELECT id FROM announcements WHERE id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute([$id]);
    
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Announcement not found']);
        return;
    }
    
    // Delete
    $sql = "DELETE FROM announcements WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Announcement deleted successfully'
    ]);
}
