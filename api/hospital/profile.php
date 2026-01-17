<?php
/**
 * Hospital Profile & Stats Endpoint
 * GET /api/hospital/profile.php
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

$hospitalId = $_SESSION['user_id'];

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Get hospital profile including status
    $stmt = $conn->prepare("SELECT id, name, email, phone, registration_number, hospital_address, website, contact_person, city, status, created_at FROM users WHERE id = ?");
    $stmt->execute([$hospitalId]);
    $hospital = $stmt->fetch();

    // Get account status - return early if pending
    $accountStatus = $hospital['status'] ?? 'pending';
    
    // If account is pending, return limited profile with status only
    if ($accountStatus !== 'approved') {
        echo json_encode([
            'success' => true,
            'profile' => [
                'id' => $hospital['id'],
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

    // Get request statistics
    $stmt = $conn->prepare("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status IN ('approved', 'in_progress') THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN urgency = 'emergency' AND status NOT IN ('completed', 'rejected', 'cancelled') THEN 1 ELSE 0 END) as emergency
        FROM blood_requests WHERE requester_id = ? AND requester_type = 'hospital'");
    $stmt->execute([$hospitalId]);
    $stats = $stmt->fetch();

    // Get available donors count (approved donors with matching blood types)
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT id) as count FROM users WHERE role = 'donor' AND status = 'approved' AND blood_group IS NOT NULL");
    $stmt->execute();
    $availableDonors = $stmt->fetch()['count'];

    echo json_encode([
        'success' => true,
        'profile' => [
            'id' => $hospital['id'],
            'name' => $hospital['name'],
            'email' => $hospital['email'],
            'phone' => $hospital['phone'],
            'registration_number' => $hospital['registration_number'],
            'address' => $hospital['hospital_address'],
            'website' => $hospital['website'],
            'contact_person' => $hospital['contact_person'],
            'city' => $hospital['city'],
            'status' => $accountStatus,
            'member_since' => $hospital['created_at']
        ],
        'account_status' => $accountStatus,
        'stats' => [
            'total_requests' => (int) $stats['total'],
            'pending_requests' => (int) $stats['pending'],
            'active_requests' => (int) $stats['active'],
            'completed_requests' => (int) $stats['completed'],
            'emergency_requests' => (int) $stats['emergency'],
            'available_donors' => (int) $availableDonors
        ]
    ]);

} catch (PDOException $e) {
    error_log("Hospital Profile Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch profile']);
}
