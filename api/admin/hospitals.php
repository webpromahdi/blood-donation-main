<?php
/**
 * Admin List Hospitals Endpoint
 * GET /api/admin/hospitals.php
 * Query params: ?status=active
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
    $sql = "SELECT u.id, u.name, u.email, u.phone, u.hospital_address, u.registration_number, 
                   u.website, u.contact_person, u.city, u.status, u.created_at,
                   COUNT(DISTINCT r.id) as total_requests,
                   SUM(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END) as completed_requests,
                   SUM(CASE WHEN r.status = 'pending' THEN 1 ELSE 0 END) as pending_requests
            FROM users u
            LEFT JOIN blood_requests r ON u.id = r.requester_id AND r.requester_type = 'hospital'
            WHERE u.role = 'hospital'
            GROUP BY u.id
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
            'id' => $hospital['id'],
            'name' => $hospital['name'],
            'email' => $hospital['email'],
            'phone' => $hospital['phone'],
            'address' => $hospital['hospital_address'],
            'city' => $hospital['city'],
            'registration_number' => $hospital['registration_number'],
            'website' => $hospital['website'],
            'contact_person' => $hospital['contact_person'],
            'status' => $approvalStatus,
            'display_status' => $displayStatus,
            'total_requests' => (int) $hospital['total_requests'],
            'completed_requests' => (int) $hospital['completed_requests'],
            'pending_requests' => (int) $hospital['pending_requests'],
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
