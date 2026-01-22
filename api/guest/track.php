<?php
/**
 * Guest Track Blood Request Endpoint
 * GET /api/guest/track.php
 * Query params: ?code=REQ00001 OR ?phone=01700000001
 * Returns limited public information about request status
 * 
 * Normalized Schema: Uses blood_groups.blood_type instead of blood_type column
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

// No auth required - public endpoint

$code = isset($_GET['code']) ? trim($_GET['code']) : null;
$phone = isset($_GET['phone']) ? trim($_GET['phone']) : null;

if (!$code && !$phone) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please provide request code or phone number']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    $requests = [];

    if ($code) {
        // Search by request code - using normalized schema
        $sql = "SELECT r.request_code, r.patient_name, bg.blood_type, r.quantity, 
                       r.hospital_name, r.urgency, r.status, r.created_at,
                       dn.status as donation_status
                FROM blood_requests r
                JOIN blood_groups bg ON r.blood_group_id = bg.id
                LEFT JOIN donations dn ON r.id = dn.request_id AND dn.status != 'cancelled'
                WHERE r.request_code = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([strtoupper($code)]);
        $result = $stmt->fetch();

        if ($result) {
            $requests[] = formatGuestRequest($result);
        }
    } else {
        // Search by phone - using normalized schema
        $sql = "SELECT r.request_code, r.patient_name, bg.blood_type, r.quantity, 
                       r.hospital_name, r.urgency, r.status, r.created_at,
                       dn.status as donation_status
                FROM blood_requests r
                JOIN blood_groups bg ON r.blood_group_id = bg.id
                LEFT JOIN donations dn ON r.id = dn.request_id AND dn.status != 'cancelled'
                WHERE r.contact_phone = ?
                ORDER BY r.created_at DESC
                LIMIT 10";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$phone]);
        $results = $stmt->fetchAll();

        foreach ($results as $result) {
            $requests[] = formatGuestRequest($result);
        }
    }

    if (empty($requests)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'No requests found']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'requests' => $requests,
        'total' => count($requests)
    ]);

} catch (PDOException $e) {
    error_log("Guest Track Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch request']);
}

/**
 * Formats request for public/guest view (limited info)
 */
function formatGuestRequest($req)
{
    $lifecycleStatus = getGuestLifecycleStatus($req['status'], $req['donation_status']);

    return [
        'request_code' => $req['request_code'],
        'patient_name' => maskName($req['patient_name']),
        'blood_type' => $req['blood_type'],
        'quantity' => $req['quantity'],
        'hospital_name' => $req['hospital_name'],
        'urgency' => $req['urgency'],
        'status' => $lifecycleStatus['status'],
        'status_label' => $lifecycleStatus['label'],
        'status_message' => $lifecycleStatus['message'],
        'created_at' => $req['created_at']
    ];
}

/**
 * Masks patient name for privacy (e.g., "John Doe" -> "J*** D**")
 */
function maskName($name)
{
    $parts = explode(' ', $name);
    $masked = array_map(function ($part) {
        if (strlen($part) <= 1)
            return $part;
        return $part[0] . str_repeat('*', strlen($part) - 1);
    }, $parts);
    return implode(' ', $masked);
}

/**
 * Maps statuses to guest-friendly labels
 */
function getGuestLifecycleStatus($requestStatus, $donationStatus)
{
    $statusMap = [
        'pending' => ['status' => 'pending', 'label' => 'Under Review', 'message' => 'Request is being reviewed by admin.'],
        'approved' => ['status' => 'approved', 'label' => 'Searching for Donor', 'message' => 'Request approved. Looking for compatible donors.'],
        'rejected' => ['status' => 'rejected', 'label' => 'Request Rejected', 'message' => 'Request was rejected. Contact support for details.'],
        'in_progress' => ['status' => 'in_progress', 'label' => 'Donor Assigned', 'message' => 'A donor has been assigned to this request.'],
        'completed' => ['status' => 'completed', 'label' => 'Completed', 'message' => 'Donation completed successfully.'],
        'cancelled' => ['status' => 'cancelled', 'label' => 'Cancelled', 'message' => 'Request was cancelled.']
    ];

    // Use donation status for more specific in_progress states
    if ($requestStatus === 'in_progress' && $donationStatus) {
        $donationMap = [
            'accepted' => ['status' => 'donor_assigned', 'label' => 'Donor Assigned', 'message' => 'A donor has accepted the request.'],
            'on_the_way' => ['status' => 'on_the_way', 'label' => 'Donor On the Way', 'message' => 'Donor is on their way to the hospital.'],
            'reached' => ['status' => 'reached', 'label' => 'Donor Arrived', 'message' => 'Donor has arrived. Donation in progress.'],
            'completed' => ['status' => 'completed', 'label' => 'Completed', 'message' => 'Donation completed successfully.']
        ];
        return $donationMap[$donationStatus] ?? $statusMap['in_progress'];
    }

    return $statusMap[$requestStatus] ?? ['status' => 'unknown', 'label' => 'Unknown', 'message' => 'Status unknown.'];
}
