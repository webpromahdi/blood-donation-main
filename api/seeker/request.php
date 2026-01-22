<?php
/**
 * Seeker Single Request Details Endpoint
 * GET /api/seeker/request.php?id={id}
 * Returns full details of a single blood request
 * 
 * Normalized Schema: Uses seeker_id FK, blood_groups.blood_type, 
 *                    donations -> donors -> users for donor info
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

$userId = $_SESSION['user_id'];

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
    // Get seeker_id from seekers table
    $stmt = $conn->prepare("SELECT id FROM seekers WHERE user_id = ?");
    $stmt->execute([$userId]);
    $seeker = $stmt->fetch();
    
    if (!$seeker) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Seeker record not found']);
        exit;
    }
    
    $seekerId = $seeker['id'];

    // Build query based on ID or code - using normalized schema
    $baseSelect = "SELECT r.*, bg.blood_type, 
                       dn.id as donation_id, dn.status as donation_status, dn.donor_id,
                       dn.accepted_at, dn.started_at, dn.reached_at, dn.completed_at,
                       donor_user.name as donor_name, donor_user.phone as donor_phone, 
                       donor_bg.blood_type as donor_blood_group, d.city as donor_city
                FROM blood_requests r
                JOIN blood_groups bg ON r.blood_group_id = bg.id
                LEFT JOIN donations dn ON r.id = dn.request_id AND dn.status != 'cancelled'
                LEFT JOIN donors d ON dn.donor_id = d.id
                LEFT JOIN users donor_user ON d.user_id = donor_user.id
                LEFT JOIN blood_groups donor_bg ON d.blood_group_id = donor_bg.id";
                
    if ($requestId) {
        $sql = $baseSelect . " WHERE r.id = ? AND r.seeker_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$requestId, $seekerId]);
    } else {
        $sql = $baseSelect . " WHERE r.request_code = ? AND r.seeker_id = ?";
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
