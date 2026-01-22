<?php
/**
 * Admin List Hospitals Endpoint
 * GET /api/admin/hospitals.php
 * Query params: ?status=active
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

requireAuth(['admin']);

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Query with normalized schema - JOIN users and hospitals tables
    $sql = "SELECT u.id as user_id, h.id as hospital_id, u.name, u.email, u.phone, 
                   h.address, h.registration_number, h.website, h.contact_person, 
                   h.city, u.status, u.created_at, h.total_requests,
                   SUM(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END) as completed_requests,
                   SUM(CASE WHEN r.status = 'pending' THEN 1 ELSE 0 END) as pending_requests
            FROM users u
            JOIN hospitals h ON u.id = h.user_id
            LEFT JOIN blood_requests r ON h.id = r.hospital_id
            WHERE u.role = 'hospital'
            GROUP BY u.id, h.id
            ORDER BY u.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $hospitals = $stmt->fetchAll();
    
    // Format response
    $formattedHospitals = array_map(function($hospital) {
        // Get hospital approval status from database
        $approvalStatus = $hospital['status'] ?? 'pending';
        
        // Map status to display format for backward compatibility
        $displayStatus = $approvalStatus === 'approved' ? 'Approved' : ($approvalStatus === 'rejected' ? 'Rejected' : 'Pending');

        return [
            'id' => $hospital['user_id'],
            'hospital_id' => $hospital['hospital_id'],
            'name' => $hospital['name'],
            'email' => $hospital['email'],
            'phone' => $hospital['phone'],
            'address' => $hospital['address'],
            'city' => $hospital['city'],
            'registration_number' => $hospital['registration_number'],
            'website' => $hospital['website'],
            'contact_person' => $hospital['contact_person'],
            'status' => $approvalStatus,
            'display_status' => $displayStatus,
            'total_requests' => (int) ($hospital['total_requests'] ?? 0),
            'completed_requests' => (int) ($hospital['completed_requests'] ?? 0),
            'pending_requests' => (int) ($hospital['pending_requests'] ?? 0),
            'registered_at' => $hospital['created_at']
        ];
    }, $hospitals);
    
    // Calculate stats by approval status
    $stats = [
        'total' => count($formattedHospitals),
        'pending' => count(array_filter($formattedHospitals, function($h) { return $h['status'] === 'pending'; })),
        'approved' => count(array_filter($formattedHospitals, function($h) { return $h['status'] === 'approved'; })),
        'rejected' => count(array_filter($formattedHospitals, function($h) { return $h['status'] === 'rejected'; })),
        'total_requests' => array_sum(array_column($formattedHospitals, 'total_requests')),
        'total_completed' => array_sum(array_column($formattedHospitals, 'completed_requests'))
    ];
    
    echo json_encode([
        'success' => true,
        'hospitals' => $formattedHospitals,
        'stats' => $stats
    ]);

} catch (PDOException $e) {
    error_log("Admin Hospitals Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch hospitals']);
}
