<?php
/**
 * Get Notifications List
 * GET /api/notifications/list.php
 * 
 * Query params:
 * - limit (int): Number of notifications to return (default: 20, max: 50)
 * - offset (int): Offset for pagination (default: 0)
 * - unread_only (bool): If true, only return unread notifications
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

$userId = $_SESSION['user_id'];
$limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 50) : 20;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Build query based on filters
    $whereClause = "user_id = ?";
    $params = [$userId];
    
    if ($unreadOnly) {
        $whereClause .= " AND is_read = 0";
    }
    
    // Get total count
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM notifications WHERE {$whereClause}");
    $stmt->execute($params);
    $totalResult = $stmt->fetch();
    $total = $totalResult['total'];
    
    // Get notifications
    $stmt = $conn->prepare("
        SELECT 
            id,
            title,
            message,
            type,
            related_type,
            related_id,
            is_read,
            read_at,
            created_at
        FROM notifications 
        WHERE {$whereClause}
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $notifications = $stmt->fetchAll();
    
    // Format notifications for frontend
    $formattedNotifications = array_map(function($n) {
        // Determine icon and colors based on type
        $typeConfig = [
            'info' => ['icon' => 'info', 'iconBg' => 'bg-blue-100', 'iconColor' => 'text-blue-600'],
            'success' => ['icon' => 'check-circle', 'iconBg' => 'bg-green-100', 'iconColor' => 'text-green-600'],
            'warning' => ['icon' => 'alert-triangle', 'iconBg' => 'bg-yellow-100', 'iconColor' => 'text-yellow-600'],
            'error' => ['icon' => 'x-circle', 'iconBg' => 'bg-red-100', 'iconColor' => 'text-red-600'],
            'request' => ['icon' => 'droplet', 'iconBg' => 'bg-red-100', 'iconColor' => 'text-red-600'],
            'donation' => ['icon' => 'heart', 'iconBg' => 'bg-pink-100', 'iconColor' => 'text-pink-600'],
            'announcement' => ['icon' => 'megaphone', 'iconBg' => 'bg-purple-100', 'iconColor' => 'text-purple-600']
        ];
        
        $config = $typeConfig[$n['type']] ?? $typeConfig['info'];
        
        // Check if emergency (title starts with emoji or contains URGENT)
        $isEmergency = strpos($n['title'], '🚨') !== false || strpos($n['message'], 'URGENT') !== false;
        if ($isEmergency) {
            $config = ['icon' => 'alert-octagon', 'iconBg' => 'bg-red-200', 'iconColor' => 'text-red-700'];
        }
        
        // Format time ago
        $createdAt = new DateTime($n['created_at']);
        $now = new DateTime();
        $diff = $now->diff($createdAt);
        
        if ($diff->days > 7) {
            $timeAgo = $createdAt->format('M j, Y');
        } elseif ($diff->days > 0) {
            $timeAgo = $diff->days . ' day' . ($diff->days > 1 ? 's' : '') . ' ago';
        } elseif ($diff->h > 0) {
            $timeAgo = $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        } elseif ($diff->i > 0) {
            $timeAgo = $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        } else {
            $timeAgo = 'Just now';
        }
        
        return [
            'id' => (int)$n['id'],
            'title' => $n['title'],
            'message' => $n['message'],
            'type' => $n['type'],
            'icon' => $config['icon'],
            'iconBg' => $config['iconBg'],
            'iconColor' => $config['iconColor'],
            'relatedType' => $n['related_type'],
            'relatedId' => $n['related_id'] ? (int)$n['related_id'] : null,
            'read' => (bool)$n['is_read'],
            'time' => $timeAgo,
            'createdAt' => $n['created_at'],
            'isEmergency' => $isEmergency
        ];
    }, $notifications);
    
    echo json_encode([
        'success' => true,
        'notifications' => $formattedNotifications,
        'pagination' => [
            'total' => (int)$total,
            'limit' => $limit,
            'offset' => $offset,
            'hasMore' => ($offset + $limit) < $total
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Get Notifications Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch notifications']);
}
