<?php
/**
 * Seeker Single Request Details Endpoint
 * GET /api/seeker/request.php?id={id}
 * Returns full details of a single blood request
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

requireAuth(['seeker']);

$seekerId = $_SESSION['user_id'];

// Get request ID from query params
$requestId = $_GET['id'] ?? null;
$requestCode = $_GET['code'] ?? null;

if (!$requestId && !$requestCode) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Request ID or code is required']);
    exit;
}

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Build query based on ID or code
    if ($requestId) {
        $sql = "SELECT r.*, 
                       d.id as donation_id, d.status as donation_status, d.donor_id,
                       d.accepted_at, d.started_at, d.reached_at, d.completed_at,
                       donor.name as donor_name, donor.phone as donor_phone, 
                       donor.blood_group as donor_blood_group, donor.city as donor_city
                FROM blood_requests r
                LEFT JOIN donations d ON r.id = d.request_id AND d.status != 'cancelled'
                LEFT JOIN users donor ON d.donor_id = donor.id
                WHERE r.id = ? AND r.requester_id = ? AND r.requester_type = 'seeker'";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$requestId, $seekerId]);
    } else {
        $sql = "SELECT r.*, 
                       d.id as donation_id, d.status as donation_status, d.donor_id,
                       d.accepted_at, d.started_at, d.reached_at, d.completed_at,
                       donor.name as donor_name, donor.phone as donor_phone,
                       donor.blood_group as donor_blood_group, donor.city as donor_city
                FROM blood_requests r
                LEFT JOIN donations d ON r.id = d.request_id AND d.status != 'cancelled'
                LEFT JOIN users donor ON d.donor_id = donor.id
                WHERE r.request_code = ? AND r.requester_id = ? AND r.requester_type = 'seeker'";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$requestCode, $seekerId]);
    }

    $request = $stmt->fetch();

    if (!$request) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        exit;
    }

    // Map to lifecycle status
    $lifecycleStatus = getLifecycleStatus($request['status'], $request['donation_status']);

    // Format response
    $formattedRequest = [
        'id' => $request['id'],
        'request_code' => $request['request_code'],
        'patient_name' => $request['patient_name'],
        'patient_age' => $request['patient_age'],
        'blood_type' => $request['blood_type'],
        'quantity' => $request['quantity'],
        'hospital_name' => $request['hospital_name'],
        'city' => $request['city'],
        'contact_phone' => $request['contact_phone'],
        'contact_email' => $request['contact_email'],
        'required_date' => $request['required_date'],
        'medical_reason' => $request['medical_reason'],
        'urgency' => $request['urgency'],
        'status' => $request['status'],
        'lifecycle_status' => $lifecycleStatus,
        'created_at' => $request['created_at'],
        'approved_at' => $request['approved_at'],
        'rejected_at' => $request['rejected_at'],
        'rejection_reason' => $request['rejection_reason'],
        'donation' => $request['donation_id'] ? [
            'id' => $request['donation_id'],
            'status' => $request['donation_status'],
            'donor_name' => $request['donor_name'],
            'donor_phone' => in_array($request['donation_status'], ['on_the_way', 'reached', 'completed']) ? $request['donor_phone'] : null,
            'donor_blood_group' => $request['donor_blood_group'],
            'donor_city' => $request['donor_city'],
            'accepted_at' => $request['accepted_at'],
            'started_at' => $request['started_at'],
            'reached_at' => $request['reached_at'],
            'completed_at' => $request['completed_at']
        ] : null
    ];

    echo json_encode([
        'success' => true,
        'request' => $formattedRequest
    ]);

} catch (PDOException $e) {
    error_log("Seeker Request Detail Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch request details']);
}

/**
 * Maps backend statuses to frontend lifecycle status
 */
function getLifecycleStatus($requestStatus, $donationStatus)
{
    // Terminal states
    if ($requestStatus === 'rejected')
        return 'rejected';
    if ($requestStatus === 'completed')
        return 'completed';
    if ($requestStatus === 'cancelled')
        return 'cancelled';
    
    // Pending admin approval
    if ($requestStatus === 'pending')
        return 'pending';
    
    // Approved but no donor yet
    if ($requestStatus === 'approved' && !$donationStatus)
        return 'approved';
    
    // In progress - derive from donation status
    if ($requestStatus === 'in_progress' || ($requestStatus === 'approved' && $donationStatus)) {
        if ($donationStatus === 'accepted')
            return 'donor_assigned';
        if ($donationStatus === 'on_the_way')
            return 'on_the_way';
        if ($donationStatus === 'reached')
            return 'reached';
        if ($donationStatus === 'completed')
            return 'completed';
        // Fallback for in_progress without specific donation status
        return 'donor_assigned';
    }

    return $requestStatus;
}
