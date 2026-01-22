<?php
/**
 * Admin Dashboard Stats Endpoint
 * GET /api/admin/stats.php
 * 
 * Normalized Schema: Uses separate donors, hospitals, seekers tables
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
    // Total donors (from donors table)
    $stmt = $conn->query("SELECT COUNT(*) as count FROM donors");
    $totalDonors = $stmt->fetch()['count'];

    // Total hospitals (from hospitals table)
    $stmt = $conn->query("SELECT COUNT(*) as count FROM hospitals");
    $totalHospitals = $stmt->fetch()['count'];

    // Total seekers (from seekers table)
    $stmt = $conn->query("SELECT COUNT(*) as count FROM seekers");
    $totalSeekers = $stmt->fetch()['count'];

    // Pending approvals (blood requests)
    $stmt = $conn->query("SELECT COUNT(*) as count FROM blood_requests WHERE status = 'pending'");
    $pendingApprovals = $stmt->fetch()['count'];

    // Pending donor approvals
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users u JOIN donors d ON u.id = d.user_id WHERE u.status = 'pending'");
    $pendingDonors = $stmt->fetch()['count'];

    // Pending hospital approvals
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users u JOIN hospitals h ON u.id = h.user_id WHERE u.status = 'pending'");
    $pendingHospitals = $stmt->fetch()['count'];

    // Emergency requests (active)
    $stmt = $conn->query("SELECT COUNT(*) as count FROM blood_requests WHERE urgency = 'emergency' AND status IN ('pending', 'approved', 'in_progress')");
    $emergencyRequests = $stmt->fetch()['count'];

    // Total requests this month
    $stmt = $conn->query("SELECT COUNT(*) as count FROM blood_requests WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    $requestsThisMonth = $stmt->fetch()['count'];

    // Completed donations this month
    $stmt = $conn->query("SELECT COUNT(*) as count FROM donations WHERE status = 'completed' AND MONTH(completed_at) = MONTH(CURRENT_DATE()) AND YEAR(completed_at) = YEAR(CURRENT_DATE())");
    $completedThisMonth = $stmt->fetch()['count'];

    // Blood group distribution (from donors table)
    $stmt = $conn->query("
        SELECT bg.blood_type, COUNT(d.id) as count 
        FROM blood_groups bg
        LEFT JOIN donors d ON bg.id = d.blood_group_id
        LEFT JOIN users u ON d.user_id = u.id AND u.status = 'approved'
        GROUP BY bg.id, bg.blood_type
        ORDER BY bg.id
    ");
    $bloodGroupStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_donors' => (int) $totalDonors,
            'total_hospitals' => (int) $totalHospitals,
            'total_seekers' => (int) $totalSeekers,
            'pending_approvals' => (int) $pendingApprovals,
            'pending_donors' => (int) $pendingDonors,
            'pending_hospitals' => (int) $pendingHospitals,
            'emergency_requests' => (int) $emergencyRequests,
            'requests_this_month' => (int) $requestsThisMonth,
            'completed_this_month' => (int) $completedThisMonth,
            'blood_groups' => $bloodGroupStats
        ]
    ]);

} catch (PDOException $e) {
    error_log("Admin Stats Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch stats']);
}
