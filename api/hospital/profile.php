<?php
/**
 * Hospital Profile & Stats Endpoint
 * GET /api/hospital/profile.php
 * 
 * Normalized Schema: JOINs users + hospitals tables
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

$user = requireAuth(['hospital']);

// Note: Profile endpoint returns status for dashboard to check, so we don't block here
// But we do include status in response

$userId = $_SESSION['user_id'];

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Get hospital profile including status - JOIN users and hospitals tables
    $stmt = $conn->prepare("
        SELECT u.id as user_id, u.name, u.email, u.phone, u.status, u.created_at,
               h.id as hospital_id, h.registration_number, h.address, h.city, 
               h.website, h.contact_person, h.total_requests
        FROM users u
        JOIN hospitals h ON u.id = h.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $hospital = $stmt->fetch();

    if (!$hospital) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Hospital record not found']);
        exit;
    }

    $hospitalId = $hospital['hospital_id'];
    
    // Get account status - return early if pending
    $accountStatus = $hospital['status'] ?? 'pending';
    
    // If account is pending, return limited profile with status only
    if ($accountStatus !== 'approved') {
        echo json_encode([
            'success' => true,
            'profile' => [
                'id' => $hospital['user_id'],
                'hospital_id' => $hospitalId,
                'name' => $hospital['name'],
                'email' => $hospital['email'],
                'status' => $accountStatus
            ],
            'account_status' => $accountStatus,
            'requires_approval' => true,
            'stats' => null
        ]);
        exit;
    }

    // Get request statistics - use requester_id + requester_type
    // Note: requester_id references users.id, not hospitals.id
    $stmt = $conn->prepare("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status IN ('approved', 'in_progress') THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN urgency = 'emergency' AND status NOT IN ('completed', 'rejected', 'cancelled') THEN 1 ELSE 0 END) as emergency
        FROM blood_requests WHERE requester_id = ? AND requester_type = 'hospital'");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch();

    // Get available donors count (approved donors)
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT d.id) as count 
        FROM donors d
        JOIN users u ON d.user_id = u.id
        WHERE u.status = 'approved' 
        AND d.blood_group_id IS NOT NULL
        AND (d.next_eligible_date IS NULL OR d.next_eligible_date <= CURDATE())
    ");
    $stmt->execute();
    $availableDonors = $stmt->fetch()['count'];

    echo json_encode([
        'success' => true,
        'profile' => [
            'id' => $hospital['user_id'],
            'hospital_id' => $hospitalId,
            'name' => $hospital['name'],
            'email' => $hospital['email'],
            'phone' => $hospital['phone'],
            'registration_number' => $hospital['registration_number'],
            'address' => $hospital['address'],
            'website' => $hospital['website'],
            'contact_person' => $hospital['contact_person'],
            'city' => $hospital['city'],
            'status' => $accountStatus,
            'member_since' => $hospital['created_at']
        ],
        'account_status' => $accountStatus,
        'stats' => [
            'total_requests' => (int) ($stats['total'] ?? 0),
            'pending_requests' => (int) ($stats['pending'] ?? 0),
            'active_requests' => (int) ($stats['active'] ?? 0),
            'completed_requests' => (int) ($stats['completed'] ?? 0),
            'emergency_requests' => (int) ($stats['emergency'] ?? 0),
            'available_donors' => (int) $availableDonors
        ]
    ]);

} catch (PDOException $e) {
    error_log("Hospital Profile Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch profile']);
}
