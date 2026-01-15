<?php
/**
 * Admin Dashboard Stats Endpoint
 * GET /api/admin/stats.php
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

requireAuth(['admin']);

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Total donors
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'donor'");
    $totalDonors = $stmt->fetch()['count'];

    // Total hospitals
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'hospital'");
    $totalHospitals = $stmt->fetch()['count'];

    // Pending approvals
    $stmt = $conn->query("SELECT COUNT(*) as count FROM blood_requests WHERE status = 'pending'");
    $pendingApprovals = $stmt->fetch()['count'];

    // Emergency requests (active)
    $stmt = $conn->query("SELECT COUNT(*) as count FROM blood_requests WHERE urgency = 'emergency' AND status IN ('pending', 'approved', 'in_progress')");
    $emergencyRequests = $stmt->fetch()['count'];

    // Total requests this month
    $stmt = $conn->query("SELECT COUNT(*) as count FROM blood_requests WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    $requestsThisMonth = $stmt->fetch()['count'];

    // Completed donations this month
    $stmt = $conn->query("SELECT COUNT(*) as count FROM donations WHERE status = 'completed' AND MONTH(completed_at) = MONTH(CURRENT_DATE()) AND YEAR(completed_at) = YEAR(CURRENT_DATE())");
    $completedThisMonth = $stmt->fetch()['count'];

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_donors' => (int) $totalDonors,
            'total_hospitals' => (int) $totalHospitals,
            'pending_approvals' => (int) $pendingApprovals,
            'emergency_requests' => (int) $emergencyRequests,
            'requests_this_month' => (int) $requestsThisMonth,
            'completed_this_month' => (int) $completedThisMonth
        ]
    ]);

} catch (PDOException $e) {
    error_log("Admin Stats Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch stats']);
}
