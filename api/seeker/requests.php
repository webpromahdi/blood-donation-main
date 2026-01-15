<?php
/**
 * Seeker List Requests Endpoint
 * GET /api/seeker/requests.php
 * Returns all blood requests for the logged-in seeker with lifecycle status
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
                   donor.name as donor_name, donor.phone as donor_phone
            FROM blood_requests r
            LEFT JOIN donations d ON r.id = d.request_id AND d.status != 'cancelled'
            LEFT JOIN users donor ON d.donor_id = donor.id
            WHERE r.requester_id = ? AND r.requester_type = 'seeker'
            ORDER BY r.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$seekerId]);
    $requests = $stmt->fetchAll();

    // Map to frontend-friendly format with lifecycle status
    $formattedRequests = array_map(function ($req) {
        $lifecycleStatus = getLifecycleStatus($req['status'], $req['donation_status']);

        return [
            'id' => $req['id'],
            'request_code' => $req['request_code'],
            'patient_name' => $req['patient_name'],
            'blood_type' => $req['blood_type'],
            'quantity' => $req['quantity'],
            'hospital_name' => $req['hospital_name'],
            'city' => $req['city'],
            'required_date' => $req['required_date'],
            'urgency' => $req['urgency'],
            'status' => $req['status'],
            'lifecycle_status' => $lifecycleStatus,
            'created_at' => $req['created_at'],
            'donation' => $req['donation_id'] ? [
                'id' => $req['donation_id'],
                'status' => $req['donation_status'],
                'donor_name' => $req['donor_name'],
                'donor_phone' => $req['donation_status'] === 'on_the_way' || $req['donation_status'] === 'reached' ? $req['donor_phone'] : null,
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
        'active' => count(array_filter($formattedRequests, fn($r) => !in_array($r['status'], ['completed', 'rejected', 'cancelled']))),
        'completed' => count(array_filter($formattedRequests, fn($r) => $r['status'] === 'completed')),
        'rejected' => count(array_filter($formattedRequests, fn($r) => $r['status'] === 'rejected'))
    ];

    echo json_encode([
        'success' => true,
        'requests' => $formattedRequests,
        'stats' => $stats
    ]);

} catch (PDOException $e) {
    error_log("Seeker Requests Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch requests']);
}

/**
 * Maps backend statuses to frontend lifecycle status
 */
function getLifecycleStatus($requestStatus, $donationStatus)
{
    if ($requestStatus === 'pending')
        return 'pending';
    if ($requestStatus === 'rejected')
        return 'rejected';
    if ($requestStatus === 'approved' && !$donationStatus)
        return 'approved';
    if ($requestStatus === 'completed')
        return 'completed';

    // In progress - use donation status
    if ($donationStatus === 'accepted')
        return 'donor_assigned';
    if ($donationStatus === 'on_the_way')
        return 'on_the_way';
    if ($donationStatus === 'reached')
        return 'reached';
    if ($donationStatus === 'completed')
        return 'completed';

    return $requestStatus;
}
