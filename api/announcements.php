<?php
/**
 * Public Announcements Endpoint
 * GET /api/announcements.php - Fetch published announcements for the current user's role
 * 
 * This endpoint returns only announcements that:
 * - Have status = 'published' OR (status = 'scheduled' AND scheduled_at <= NOW())
 * - Match the user's role or target 'all' users
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();
require_once __DIR__ . '/config/database.php';

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get user role from session (default to 'guest' if not logged in)
$userRole = $_SESSION['user_role'] ?? 'guest';

// Map user roles to target_audience values
$roleMap = [
    'donor' => 'donors',
    'hospital' => 'hospitals',
    'seeker' => 'seekers',
    'admin' => 'all' // Admin sees all
];

$targetAudience = $roleMap[$userRole] ?? 'all';

try {
    // First, auto-publish any scheduled announcements whose time has come
    $updateSql = "UPDATE announcements 
                  SET status = 'published' 
                  WHERE status = 'scheduled' 
                  AND scheduled_at IS NOT NULL 
                  AND scheduled_at <= NOW()";
    $conn->exec($updateSql);

    // Build query based on user role
    if ($userRole === 'admin') {
        // Admin sees all published announcements
        $sql = "SELECT a.*, u.name as admin_name 
                FROM announcements a 
                LEFT JOIN users u ON a.admin_id = u.id 
                WHERE a.status = 'published'
                ORDER BY a.priority = 'urgent' DESC, 
                         a.priority = 'high' DESC, 
                         a.created_at DESC
                LIMIT 50";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
    } else {
        // Regular users see announcements for their role or 'all'
        $sql = "SELECT a.*, u.name as admin_name 
                FROM announcements a 
                LEFT JOIN users u ON a.admin_id = u.id 
                WHERE a.status = 'published'
                AND (a.target_audience = 'all' OR a.target_audience = :target)
                ORDER BY a.priority = 'urgent' DESC, 
                         a.priority = 'high' DESC, 
                         a.created_at DESC
                LIMIT 50";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['target' => $targetAudience]);
    }

    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format response
    $formatted = array_map(function($a) {
        return [
            'id' => (int) $a['id'],
            'title' => $a['title'],
            'message' => $a['message'],
            'target_audience' => $a['target_audience'],
            'priority' => $a['priority'],
            'admin_name' => $a['admin_name'] ?? 'System',
            'created_at' => $a['created_at']
        ];
    }, $announcements);

    echo json_encode([
        'success' => true,
        'announcements' => $formatted,
        'total' => count($formatted)
    ]);

} catch (PDOException $e) {
    error_log("Announcements API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch announcements']);
}
