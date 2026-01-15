<?php
/**
 * Hospital List Requests Endpoint
 * GET /api/hospital/requests.php
 * Returns all blood requests for the logged-in hospital
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

requireAuth(['hospital']);

$hospitalId = $_SESSION['user_id'];

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    $sql = "SELECT r.*, 
                   d.id as donation_id, d.status as donation_status, d.donor_id,
                   d.accepted_at, d.started_at, d.reached_at, d.completed_at,
                   donor.name as donor_name, donor.phone as donor_phone, donor.blood_group
            FROM blood_requests r
            LEFT JOIN donations d ON r.id = d.request_id AND d.status != 'cancelled'
            LEFT JOIN users donor ON d.donor_id = donor.id
            WHERE r.requester_id = ? AND r.requester_type = 'hospital'
            ORDER BY r.urgency DESC, r.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$hospitalId]);
    $requests = $stmt->fetchAll();

    $formattedRequests = array_map(function ($req) {
        return [
            'id' => $req['id'],
            'request_code' => $req['request_code'],
            'patient_name' => $req['patient_name'],
            'patient_age' => $req['patient_age'],
            'blood_type' => $req['blood_type'],
            'quantity' => $req['quantity'],
            'city' => $req['city'],
            'contact_phone' => $req['contact_phone'],
            'required_date' => $req['required_date'],
            'medical_reason' => $req['medical_reason'],
            'urgency' => $req['urgency'],
            'status' => $req['status'],
            'created_at' => $req['created_at'],
            'approved_at' => $req['approved_at'],
            'donation' => $req['donation_id'] ? [
                'id' => $req['donation_id'],
                'status' => $req['donation_status'],
                'donor_name' => $req['donor_name'],
                'donor_phone' => $req['donor_phone'],
                'donor_blood_group' => $req['blood_group'],
                'accepted_at' => $req['accepted_at'],
                'started_at' => $req['started_at'],
                'reached_at' => $req['reached_at'],
                'completed_at' => $req['completed_at']
            ] : null
        ];
    }, $requests);

    // Calculate stats
    $stats = [
        'total' => count($formattedRequests),
        'pending' => count(array_filter($formattedRequests, fn($r) => $r['status'] === 'pending')),
        'active' => count(array_filter($formattedRequests, fn($r) => in_array($r['status'], ['approved', 'in_progress']))),
        'completed' => count(array_filter($formattedRequests, fn($r) => $r['status'] === 'completed')),
        'emergency' => count(array_filter($formattedRequests, fn($r) => $r['urgency'] === 'emergency' && !in_array($r['status'], ['completed', 'rejected'])))
    ];

    echo json_encode([
        'success' => true,
        'requests' => $formattedRequests,
        'stats' => $stats
    ]);

} catch (PDOException $e) {
    error_log("Hospital Requests Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch requests']);
}
